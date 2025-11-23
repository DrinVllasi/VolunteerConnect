<?php
/**
 * Two-Way Interest System Handler
 * Handles volunteer interests and organization invites
 */

// Only process if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }
    return; // Exit early if not a POST request with action
}

if (!isset($_SESSION)) session_start();
require_once 'config/config.php';

// Check if this is an AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

function sendResponse($success, $message, $is_ajax, $data = null) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
        exit;
    } else {
        $_SESSION['interest_message'] = $message;
        $_SESSION['interest_success'] = $success;
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'public_browse.php'));
        exit;
    }
}

// Volunteer expressing interest
if (isset($_POST['action']) && $_POST['action'] === 'express_interest') {
    if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['user', 'volunteer'])) {
        sendResponse(false, 'Only volunteers can express interest.', $is_ajax);
    }
    
    $volunteer_id = $_SESSION['user_id'];
    $opportunity_id = (int)($_POST['opportunity_id'] ?? 0);
    
    if (!$opportunity_id) {
        sendResponse(false, 'Invalid opportunity.', $is_ajax);
    }
    
    // Check if already interested
    $check = $conn->prepare("SELECT id FROM volunteer_interests WHERE volunteer_id = ? AND opportunity_id = ?");
    $check->execute([$volunteer_id, $opportunity_id]);
    
    if ($check->fetch()) {
        sendResponse(false, 'You have already expressed interest.', $is_ajax);
    }
    
    // Check if already applied
    $check_app = $conn->prepare("SELECT id FROM applications WHERE volunteer_id = ? AND opportunity_id = ?");
    $check_app->execute([$volunteer_id, $opportunity_id]);
    if ($check_app->fetch()) {
        sendResponse(false, 'You have already applied to this opportunity.', $is_ajax);
    }
    
    try {
        // Check if table exists, if not return error
        $table_check = $conn->query("SHOW TABLES LIKE 'volunteer_interests'");
        if ($table_check->rowCount() == 0) {
            error_log('volunteer_interests table does not exist');
            sendResponse(false, 'Interest system not set up. Please run the database migration.', $is_ajax);
        }
        
        $stmt = $conn->prepare("INSERT INTO volunteer_interests (volunteer_id, opportunity_id) VALUES (?, ?)");
        $stmt->execute([$volunteer_id, $opportunity_id]);
        
        // Check if organization already invited this volunteer (mutual match!)
        $check_invite = $conn->prepare("SELECT id FROM organization_invites WHERE volunteer_id = ? AND opportunity_id = ? AND status = 'pending'");
        $check_invite->execute([$volunteer_id, $opportunity_id]);
        $has_invite = $check_invite->fetch();
        
        if ($has_invite) {
            sendResponse(true, 'Mutual match! The organization has also invited you. Consider applying!', $is_ajax, ['mutual_match' => true]);
        } else {
            sendResponse(true, 'Interest expressed! The organization will be notified.', $is_ajax);
        }
    } catch (PDOException $e) {
        error_log('Interest handler error: ' . $e->getMessage());
        sendResponse(false, 'Database error. Please try again later. Error: ' . $e->getMessage(), $is_ajax);
    } catch (Exception $e) {
        error_log('Interest handler error: ' . $e->getMessage());
        sendResponse(false, 'An error occurred. Please try again. Error: ' . $e->getMessage(), $is_ajax);
    }
}

// Organization inviting volunteer
if (isset($_POST['action']) && $_POST['action'] === 'invite_volunteer') {
    if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['organization', 'admin'])) {
        sendResponse(false, 'Only organizations can invite volunteers.', $is_ajax);
    }
    
    $organization_id = $_SESSION['user_id'];
    $volunteer_id = (int)($_POST['volunteer_id'] ?? 0);
    $opportunity_id = (int)($_POST['opportunity_id'] ?? 0);
    
    if (!$volunteer_id || !$opportunity_id) {
        sendResponse(false, 'Invalid request.', $is_ajax);
    }
    
    // Verify organization owns the opportunity
    $check_opp = $conn->prepare("SELECT id FROM opportunities WHERE id = ? AND organization_id = ?");
    $check_opp->execute([$opportunity_id, $organization_id]);
    if (!$check_opp->fetch()) {
        sendResponse(false, 'You do not own this opportunity.', $is_ajax);
    }
    
    // Check if already invited
    $check = $conn->prepare("SELECT id, status FROM organization_invites WHERE organization_id = ? AND volunteer_id = ? AND opportunity_id = ?");
    $check->execute([$organization_id, $volunteer_id, $opportunity_id]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        if ($existing['status'] === 'pending') {
            sendResponse(false, 'You have already invited this volunteer.', $is_ajax);
        } else {
            // Resend invite if declined
            $stmt = $conn->prepare("UPDATE organization_invites SET status = 'pending', invited_at = NOW() WHERE id = ?");
            $stmt->execute([$existing['id']]);
            sendResponse(true, 'Invitation resent!', $is_ajax);
        }
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO organization_invites (organization_id, volunteer_id, opportunity_id) VALUES (?, ?, ?)");
            $stmt->execute([$organization_id, $volunteer_id, $opportunity_id]);
            
            // Check if volunteer already expressed interest (mutual match!)
            $check_interest = $conn->prepare("SELECT id FROM volunteer_interests WHERE volunteer_id = ? AND opportunity_id = ?");
            $check_interest->execute([$volunteer_id, $opportunity_id]);
            $has_interest = $check_interest->fetch();
            
            if ($has_interest) {
                sendResponse(true, 'Mutual match! This volunteer has also expressed interest.', $is_ajax, ['mutual_match' => true]);
            } else {
                sendResponse(true, 'Invitation sent!', $is_ajax);
            }
        } catch (Exception $e) {
            sendResponse(false, 'An error occurred. Please try again.', $is_ajax);
        }
    }
}

// Remove interest
if (isset($_POST['action']) && $_POST['action'] === 'remove_interest') {
    if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['user', 'volunteer'])) {
        sendResponse(false, 'Unauthorized.', $is_ajax);
    }
    
    $volunteer_id = $_SESSION['user_id'];
    $opportunity_id = (int)($_POST['opportunity_id'] ?? 0);
    
    $stmt = $conn->prepare("DELETE FROM volunteer_interests WHERE volunteer_id = ? AND opportunity_id = ?");
    $stmt->execute([$volunteer_id, $opportunity_id]);
    
    sendResponse(true, 'Interest removed.', $is_ajax);
}

// Accept/decline invite (volunteer)
if (isset($_POST['action']) && in_array($_POST['action'], ['accept_invite', 'decline_invite'])) {
    if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['user', 'volunteer'])) {
        sendResponse(false, 'Unauthorized.', $is_ajax);
    }
    
    $volunteer_id = $_SESSION['user_id'];
    $invite_id = (int)($_POST['invite_id'] ?? 0);
    
    $status = $_POST['action'] === 'accept_invite' ? 'accepted' : 'declined';
    
    $stmt = $conn->prepare("UPDATE organization_invites SET status = ? WHERE id = ? AND volunteer_id = ?");
    $stmt->execute([$status, $invite_id, $volunteer_id]);
    
    if ($status === 'accepted') {
        sendResponse(true, 'Invitation accepted! Consider applying to the opportunity.', $is_ajax);
    } else {
        sendResponse(true, 'Invitation declined.', $is_ajax);
    }
}

