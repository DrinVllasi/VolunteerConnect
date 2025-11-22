<?php
// auth/test_login.php  ← create this new file
session_start();
require_once '../config/config.php';

// DELETE EVERYTHING in your current session first
$_SESSION = [];
session_destroy();
session_start();

// Hard-coded test – remove after it works
$email    = "admin@gmail.com";  // ← CHANGE THIS TO YOUR ADMIN EMAIL
$password = "admin123";          // ← CHANGE THIS TO YOUR REAL PASSWORD

$stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['name']      = $user['name'];
    $_SESSION['role']      = strtolower(trim($user['role'] ?? 'volunteer'));
    $_SESSION['logged_in'] = true;
    echo "LOGIN SUCCESS! Your role is: " . $_SESSION['role'];
    echo "<br><a href='../public_browse.php'>Go to Dashboard →</a>";
} else {
    echo "Login failed – wrong password or user not found.";
}
?>