<?php
// includes/apply_handler.php
// THIS FILE BLOCKS ADMINS & ORGS FROM APPLYING — EVERYWHERE

if (!isset($_SESSION)) session_start();
require_once __DIR__.'/../config/config.php';

// Check if this is an AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

function sendResponse($success, $message, $is_ajax) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    } else {
        // Fallback for non-AJAX requests
        echo '<script>alert("' . addslashes($message) . '"); location.reload();</script>';
        exit;
    }
}

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    sendResponse(false, 'Please log in first.', $is_ajax);
}

if (in_array($_SESSION['role'], ['admin', 'organization'])) {
    sendResponse(false, 'Admins and organizations cannot apply to volunteer events.', $is_ajax);
}

if (!isset($_POST['opp_id'])) {
    sendResponse(false, 'Invalid request.', $is_ajax);
}

$opp_id = (int)$_POST['opp_id'];
$user_id = $_SESSION['user_id'];

// Check if already applied
$check_applied = $conn->prepare("SELECT id FROM applications WHERE opportunity_id = ? AND volunteer_id = ?");
$check_applied->execute([$opp_id, $user_id]);
if ($check_applied->fetch()) {
    sendResponse(false, 'You have already applied to this event.', $is_ajax);
}

// Check spots left
$stmt = $conn->prepare("SELECT (o.slots - COUNT(a.id)) as spots_left
                        FROM opportunities o
                        LEFT JOIN applications a ON o.id = a.opportunity_id AND a.status='confirmed'
                        WHERE o.id = ?");
$stmt->execute([$opp_id]);
$spots_left = $stmt->fetchColumn();

if ($spots_left <= 0) {
    sendResponse(false, 'This event is now full.', $is_ajax);
}

// Apply
try {
    $conn->prepare("INSERT INTO applications (opportunity_id, volunteer_id, status) VALUES (?, ?, 'pending')")
         ->execute([$opp_id, $user_id]);
    sendResponse(true, 'Applied successfully! Waiting for approval.', $is_ajax);
} catch (Exception $e) {
    sendResponse(false, 'An error occurred. Please try again.', $is_ajax);
}
?>