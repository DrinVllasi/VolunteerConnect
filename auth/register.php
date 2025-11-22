<?php
// register.php
session_start();
include_once '../config/config.php';

// Security headers (same as login)
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' https://cdn.jsdelivr.net; style-src \'self\' \'unsafe-inline\' https://cdn.jsdelivr.net;');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VolunteerConnect - Register</title>
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
        .form-register {
            width: 100%;
            max-width: 440px;
            padding: 40px;
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

<div class="form-register">
    <div class="text-center mb-4">
        <div class="logo">VC</div>
        <h3>VolunteerConnect</h3>
        <p class="text-muted">Join us and make a difference</p>
    </div>

    <!-- Error Messages -->
    <?php if (!empty($_SESSION['register_errors'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php foreach ($_SESSION['register_errors'] as $error): ?>
                <div><?= htmlspecialchars($error) ?></div>
            <?php endforeach; unset($_SESSION['register_errors']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Success Message -->
    <?php if (!empty($_SESSION['register_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['register_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['register_success']); ?>
    <?php endif; ?>

    <form action="registerLogic.php" method="post" autocomplete="off" novalidate>
        <div style="position: absolute; left: -9999px;">
            <input type="text" name="website" tabindex="-1" autocomplete="off">
        </div>
        <div class="mb-3">
            <input type="text" class="form-control form-control-lg" name="name" placeholder="Full Name" 
                   value="<?= htmlspecialchars($_SESSION['old_name'] ?? '') ?>" required autofocus>
        </div>
        <div class="mb-3">
            <input type="email" class="form-control form-control-lg" name="email" placeholder="Email Address" 
                   value="<?= htmlspecialchars($_SESSION['old_email'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <input type="password" class="form-control form-control-lg" name="password" placeholder="Password" required autocomplete="new-password">
        </div>
        <div class="mb-3">
            <input type="password" class="form-control form-control-lg" name="confirm_password" placeholder="Confirm Password" required autocomplete="new-password">
        </div>

        <button type="submit" name="submit" class="btn btn-primary btn-lg w-100">Create Account</button>
    </form>

    <p class="text-center mt-4 text-muted">
        Already have an account? <a href="login.php" class="text-decoration-none fw-bold">Login here</a>
    </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>