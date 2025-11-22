<?php
// login.php
session_start();
include_once '../config/config.php';

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' https://cdn.jsdelivr.net; style-src \'self\' \'unsafe-inline\' https://cdn.jsdelivr.net;');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VolunteerConnect - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
        }
        .form-login {
            width: 100%;
            max-width: 420px;
            padding: 30px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        .logo {
            width: 80px;
            height: 80px;
            background: #667eea;
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

<div class="form-login">
    <div class="text-center mb-4">
        <div class="logo">VC</div>
        <h3>VolunteerConnect</h3>
        <p class="text-muted">Sign in to continue</p>
    </div>

    <?php if (isset($_SESSION['login_errors']) && !empty($_SESSION['login_errors'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php foreach ($_SESSION['login_errors'] as $error): ?>
                <div><?= htmlspecialchars($error) ?></div>
            <?php endforeach; unset($_SESSION['login_errors']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['register_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['register_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['register_success']); ?>
    <?php endif; ?>

    <form action="loginLogic.php" method="post" autocomplete="off" novalidate>
        <div class="mb-3">
            <input type="email" class="form-control form-control-lg" name="email" placeholder="Email address" required autofocus>
        </div>
        <div class="mb-3">
            <input type="password" class="form-control form-control-lg" name="password" placeholder="Password" required autocomplete="current-password">
        </div>
        <button type="submit" name="submit" class="btn btn-primary btn-lg w-100">Login</button>
    </form>

    <p class="text-center mt-4 text-muted">
        Don't have an account? <a href="register.php" class="text-decoration-none">Register here</a>
    </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>