<?php
// auth/loginLogic.php
require_once '../config/config.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submit'])) {
    header('Location: login.php');
    exit();
}

/* =============== RATE LIMITING =============== */
$ip          = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$attempt_key = "login_attempts_$ip";

if (!isset($_SESSION[$attempt_key])) {
    $_SESSION[$attempt_key] = ['count' => 0, 'time' => 0];
}

if ($_SESSION[$attempt_key]['count'] >= 5 && (time() - $_SESSION[$attempt_key]['time']) < 900) {
    $_SESSION['login_errors'] = ["Too many failed attempts. Try again in 15 minutes."];
    header('Location: login.php');
    exit();
}

/* =============== INPUT =============== */
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$errors = [];

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid email required.";
}
if ($password === '') {
    $errors[] = "Password required.";
}

if (!empty($errors)) {
    $_SESSION['login_errors'] = $errors;
    header('Location: login.php');
    exit();
}

/* =============== FIND USER =============== */
$stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* =============== CHECK PASSWORD =============== */
if ($user && password_verify($password, $user['password'])) {

    // SUCCESS → log the user in
    session_regenerate_id(true);
    unset($_SESSION[$attempt_key]);

    // Clean role
    $db_role = trim(strtolower($user['role'] ?? 'volunteer'));
    if ($db_role === 'admin') {
        $final_role = 'admin';
        $redirect_to = '../admin/admin_dashboard.php';
    } else {
        // volunteers and organizations go to public_browse
        $final_role = $db_role === 'organization' ? 'organization' : 'volunteer';
        $redirect_to = '../public_browse.php';
    }

    // Save session
    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['name']       = htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8');
    $_SESSION['email']      = $user['email'];
    $_SESSION['role']       = $final_role;
    $_SESSION['logged_in']  = true;
    $_SESSION['login_time'] = time();

    // REDIRECT based on role
    header("Location: $redirect_to");
    exit();

} else {
    // FAILED LOGIN
    $_SESSION[$attempt_key]['count']++;
    $_SESSION[$attempt_key]['time'] = time();

    $_SESSION['login_errors'] = ["Invalid email or password."];
    header('Location: login.php');
    exit();
}
?>
