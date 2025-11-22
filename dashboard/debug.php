<?php
session_start();
require_once '../config/config.php';

echo "<pre>";
echo "Your current session data:\n";
print_r($_SESSION);

echo "\n\nYour role column in DB for this user:\n";
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id'] ?? 0]);
echo $stmt->fetchColumn();

echo "\n\nAll users with role:\n";
foreach ($conn->query("SELECT id, name, email, role FROM users")->fetchAll() as $u) {
    echo "{$u['id']} | {$u['name']} | {$u['email']} | role = '{$u['role']}'\n";
}
echo "</pre>";
?>