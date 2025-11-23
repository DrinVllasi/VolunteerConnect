<?php
session_start();
require_once '../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['user','volunteer'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$opp_id = (int)($_POST['opp_id'] ?? 0);
$user_id = $_SESSION['user_id'];

$check = $conn->prepare("SELECT 1 FROM applications WHERE opportunity_id = ? AND volunteer_id = ?");
$check->execute([$opp_id, $user_id]);
if ($check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Already applied']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO applications (opportunity_id, volunteer_id, status, applied_at) VALUES (?, ?, 'pending', NOW())");
echo json_encode(['success' => $stmt->execute([$opp_id, $user_id])]);
?>