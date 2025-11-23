<?php
session_start();
require_once 'config/config.php';

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['organization', 'admin'])) {
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$app_id = $data['app_id'] ?? 0;
$action = $data['action'] ?? '';
$hours = floatval($data['hours_worked'] ?? 0);

if (!$app_id || !$action) {
    exit(json_encode(['success' => false]));
}

try {
    if ($action === 'approve') {
        $conn->prepare("UPDATE applications SET status = 'confirmed' WHERE id = ?")->execute([$app_id]);
    }
    elseif ($action === 'reject') {
        $conn->prepare("UPDATE applications SET status = 'cancelled' WHERE id = ?")->execute([$app_id]);
    }
    elseif ($action === 'complete_and_approve') {
        $conn->prepare("
            UPDATE applications 
            SET status = 'completed', hours_worked = ?, hours_approved = 1 
            WHERE id = ?
        ")->execute([$hours, $app_id]);
    }

    echo json_encode(['success' => true]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}