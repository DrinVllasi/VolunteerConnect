<?php
// login.php
session_start();
include_once '../config/config.php';

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - VolunteerConnect</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;600;800&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">

<style>
    :root {
        --earth-1: #f2efe9;
        --accent-1: #6a8e3a;
        --accent-2: #b27a4b;
        --muted: #6b6b6b;
    }
    body {
        font-family: 'Manrope', 'Inter', sans-serif;
        background: var(--earth-1);
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
    }
    .login-container {
        background: #fff;
        padding: 3rem 2rem;
        border-radius: 18px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.05);
        width: 100%;
        max-width: 420px;
    }
    .login-container h1 {
        font-weight: 700;
        margin-bottom: 1.5rem;
        text-align: center;
        color: var(--accent-1);
    }
    .login-container form input {
        width: 100%;
        padding: 0.75rem 1rem;
        margin-bottom: 1rem;
        border-radius: 10px;
        border: 1px solid #ccc;
        font-size: 1rem;
    }
    .login-container form button {
        width: 100%;
        padding: 0.75rem;
        border-radius: 10px;
        border: none;
        background: var(--accent-1);
        color: white;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .login-container form button:hover {
        background: #5a7d32;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(106,142,58,0.35);
    }
    .message {
        text-align: center;
        margin-bottom: 1rem;
    }
    .message p {
        margin: 0;
        padding: 0.5rem 0;
    }
    .message .error {
        color: #e53e3e;
    }
    .message .success {
        color: var(--accent-1);
    }
    .login-container a {
        display: block;
        text-align: center;
        margin-top: 1rem;
        color: var(--accent-2);
        text-decoration: none;
        font-weight: 500;
    }
    .login-container a:hover {
        text-decoration: underline;
    }
    .logo {
        width: 80px;
        height: 80px;
        background: var(--accent-1);
        border-radius: 50%;
        margin: 0 auto 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 36px;
        font-weight: bold;
    }
</style>
</head>
<body>

<div class="login-container">
    <div class="text-center mb-4">
        <div class="logo">VC</div>
        <h1>VolunteerConnect</h1>
        <p class="text-muted">Sign in to continue</p>
    </div>

    <div class="message">
        <?php if (!empty($_SESSION['login_errors'])): ?>
            <?php foreach ($_SESSION['login_errors'] as $error) echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; ?>
            <?php unset($_SESSION['login_errors']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['register_success'])): ?>
            <p class="success"><?= htmlspecialchars($_SESSION['register_success']); unset($_SESSION['register_success']); ?></p>
        <?php endif; ?>
    </div>

    <form action="loginLogic.php" method="post" autocomplete="off" novalidate>
        <input type="email" name="email" placeholder="Email Address" required autofocus>
        <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
        <button type="submit" name="submit">Login</button>
    </form>

    <a href="register.php">Don't have an account? Register here</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
