<?php
session_start();
require_once 'config/config.php';

// Only organizations can confirm hours
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'organization') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

$app_id = (int)($_POST['app_id'] ?? 0);
$hours  = (int)($_POST['hours'] ?? 0);

if ($app_id <= 0 || $hours < 1 || $hours > 20) {
    echo json_encode(['success' => false]);
    exit;
}

// Update application
$stmt = $conn->prepare("UPDATE applications SET status = 'confirmed', hours_logged = ? WHERE id = ? AND status = 'pending'");
$updated = $stmt->execute([$hours, $app_id]);

if (!$updated || $stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'Already confirmed']);
    exit;
}

// Add hours to volunteer's total
$conn->prepare("
    UPDATE users u 
    JOIN applications a ON u.id = a.volunteer_id 
    SET u.total_hours = u.total_hours + ? 
    WHERE a.id = ?
")->execute([$hours, $app_id]);

echo json_encode(['success' => true]);
exit;