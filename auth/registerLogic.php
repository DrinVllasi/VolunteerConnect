<?php
// registerLogic.php
declare(strict_types=1);

require_once '../config/config.php';  // session + secure DB already started

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submit'])) {
    header('Location: register.php');
    exit();
}

/* ==================== 1. RATE LIMITING (stop bots & abuse) ==================== */
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$attempt_key = "reg_attempts_$ip";

if (!isset($_SESSION[$attempt_key])) {
    $_SESSION[$attempt_key] = 0;
}

if ($_SESSION[$attempt_key] >= 5) {
    // More than 5 registrations from same IP in this session → block
    $_SESSION['register_errors'] = ["Too many registration attempts. Please try again later."];
    header('Location: register.php');
    exit();
}

/* ==================== 2. HONEYPOT (simple anti-bot) ==================== */
// Add this hidden field in your register.php form later:
// <input type="text" name="website" style="display:none">
// Bots fill it → we block them
if (!empty($_POST['website'] ?? '')) {
    // Bot detected
    $_SESSION['register_errors'] = ["Spam detected."];
    header('Location: register.php');
    exit();
}

/* ==================== 3. GET & SANITIZE INPUT ==================== */
$name            = trim($_POST['name'] ?? '');
$email           = trim($_POST['email'] ?? '');
$password        = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

$errors = [];

/* ==================== 4. VALIDATION ==================== */
if ($name === '' || strlen($name) < 2 || strlen($name) > 60) {
    $errors[] = "Name must be 2–60 characters.";
} elseif (!preg_match("/^[\p{L}\p{M}'\s-]+$/u", $name)) {
    $errors[] = "Name contains invalid characters.";
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
    $errors[] = "Valid email is required.";
}

if ($password === '') {
    $errors[] = "Password is required.";
} elseif (strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters.";
} elseif (!preg_match("/[A-Za-z]/", $password) || !preg_match("/\d/", $password)) {
    $errors[] = "Password must contain letters and numbers.";
}

if ($password !== $confirm_password) {
    $errors[] = "Passwords do not match.";
}

/* ==================== 5. CHECK EMAIL UNIQUENESS (case-insensitive) ==================== */
$stmt = $conn->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
$stmt->execute([$email]);
if ($stmt->rowCount() > 0) {
    $errors[] = "This email is already registered. Please login.";
}

/* ==================== 6. IF ERRORS → SAVE INPUT & REDIRECT ==================== */
if (!empty($errors)) {
    $_SESSION['register_errors'] = $errors;
    $_SESSION['old_name']  = $name;
    $_SESSION['old_email'] = $email;
    unset($_SESSION['old_password']); // Never save password!
    header('Location: register.php');
    exit();
}

/* ==================== 7. SUCCESS: CREATE USER ==================== */
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Default role = 'volunteer' (you can change later)
$role = 'volunteer';

try {
    $stmt = $conn->prepare("
        INSERT INTO users (name, email, password, role, created_at) 
        VALUES (:name, :email, :password, :role, NOW())
    ");
    $stmt->execute([
        ':name'     => $name,
        ':email'    => $email,
        ':password' => $hashedPassword,
        ':role'     => $role
    ]);

    // Increment attempt counter
    $_SESSION[$attempt_key]++;

    // Optional: Auto-login after register
    $user_id = $conn->lastInsertId();
    session_regenerate_id(true);
    $_SESSION['user_id']    = (int)$user_id;
    $_SESSION['name']       = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $_SESSION['email']      = $email;
    $_SESSION['role']       = $role;
    $_SESSION['logged_in']  = true;
    $_SESSION['login_time'] = time();

    // Success message (in case you want to show it somewhere)
    $_SESSION['register_success'] = "Welcome, $name! Your account has been created.";

    header('Location: ../public_browse.php');
    exit();

} catch (Exception $e) {
    error_log("Registration failed for $email: " . $e->getMessage());
    $_SESSION['register_errors'] = ["Something went wrong. Please try again later."];
    header('Location: register.php');
    exit();
}