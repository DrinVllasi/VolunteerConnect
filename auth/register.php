<?php
session_start();
include_once '../config/config.php';

// Security headers (same as login)
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Volunteer Registration - VolunteerConnect</title>
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
    .register-container {
        background: #fff;
        padding: 3rem 2rem;
        border-radius: 18px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.05);
        width: 100%;
        max-width: 450px;
    }
    .register-container h1 {
        font-weight: 700;
        margin-bottom: 1.5rem;
        text-align: center;
        color: var(--accent-1);
    }
    .register-container form input {
        width: 100%;
        padding: 0.75rem 1rem;
        margin-bottom: 1rem;
        border-radius: 10px;
        border: 1px solid #ccc;
        font-size: 1rem;
    }
    .register-container form button {
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
    .register-container form button:hover {
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
    .register-container a {
        display: block;
        text-align: center;
        margin-top: 1rem;
        color: var(--accent-2);
        text-decoration: none;
        font-weight: 500;
    }
    .register-container a:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>

<div class="register-container">
    <h1>Volunteer Registration</h1>

    <div class="message">
        <?php if (!empty($_SESSION['register_errors'])): ?>
            <?php foreach ($_SESSION['register_errors'] as $error) echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; ?>
            <?php unset($_SESSION['register_errors']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['register_success'])): ?>
            <p class="success"><?= htmlspecialchars($_SESSION['register_success']); unset($_SESSION['register_success']); ?></p>
        <?php endif; ?>
    </div>

    <form action="registerLogic.php" method="post" autocomplete="off" novalidate>
        <div style="position: absolute; left: -9999px;">
            <input type="text" name="website" tabindex="-1" autocomplete="off">
        </div>
        <input type="text" name="name" placeholder="Full Name" value="<?= htmlspecialchars($_SESSION['old_name'] ?? '') ?>" required>
        <input type="email" name="email" placeholder="Email Address" value="<?= htmlspecialchars($_SESSION['old_email'] ?? '') ?>" required>
        <input type="password" name="password" placeholder="Password" required autocomplete="new-password">
        <input type="password" name="confirm_password" placeholder="Confirm Password" required autocomplete="new-password">
        <button type="submit" name="submit">Create Account</button>
    </form>

    <a href="login.php">Already have an account? Login here</a>
    <a href="register_org.php">Are you an organization? Register here</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
