<?php
session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['organization', 'admin'])) {
    echo json_encode(['success' => false]);
    exit;
}

$app_id = (int)($_POST['app_id'] ?? 0);
$action = $_POST['action'] ?? '';
$hours  = $_POST['hours'] ?? 0;

if ($app_id < 1) {
    echo json_encode(['success' => false]);
    exit;
}

try {
    if ($action === 'confirm') {
        $sql = "UPDATE applications a 
                JOIN opportunities o ON a.opportunity_id = o.id 
                SET a.status = 'confirmed' 
                WHERE a.id = ? AND o.organization_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$app_id, $_SESSION['user_id']]);
    }

    elseif ($action === 'cancel') {
        $sql = "UPDATE applications a 
                JOIN opportunities o ON a.opportunity_id = o.id 
                SET a.status = 'cancelled' 
                WHERE a.id = ? AND o.organization_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$app_id, $_SESSION['user_id']]);
    }

    elseif ($action === 'complete' && $hours > 0) {
        $sql = "UPDATE applications a 
                JOIN opportunities o ON a.opportunity_id = o.id 
                SET a.status = 'completed', a.hours_worked = ?, a.hours_approved = 1 
                WHERE a.id = ? AND o.organization_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$hours, $app_id, $_SESSION['user_id']]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false]);
}
?>