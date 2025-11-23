<?php
session_start();
require_once 'config/config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$app_id = (int)($data['app_id'] ?? 0);
$action = $data['action'] ?? '';

if ($app_id && in_array($action, ['confirm','reject'])) {
    $status = $action === 'confirm' ? 'confirmed' : 'rejected';
    $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
    $stmt->execute([$status, $app_id]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>