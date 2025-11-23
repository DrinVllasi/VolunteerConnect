<?php
session_start();
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $errors = [];

    if ($name === '') $errors[] = "Name is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email required";
    if ($password === '') $errors[] = "Password required";

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name,email,password,role,status,created_at) VALUES (?,?,?,?, 'pending', NOW())");
        $stmt->execute([$name,$email,$hashed_password,'organization']);

        $_SESSION['success'] = "Registration submitted! Please wait for admin approval.";
        header('Location: register_org.php');
        exit();
    } else {
        $_SESSION['errors'] = $errors;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Registration - VolunteerConnect</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;600;800&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root{
            --earth-1: #f2efe9;
            --accent-1: #6a8e3a;
            --accent-2: #b27a4b;
            --muted: #6b6b6b;
        }
        body {
            font-family: 'Manrope', 'Inter', sans-serif;
            background: var(--earth-1);
            color: #2b2b2b;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
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
        <h1>Organization Registration</h1>

        <div class="message">
            <?php if(!empty($_SESSION['errors'])): ?>
                <?php foreach($_SESSION['errors'] as $e) echo "<p class='error'>$e</p>"; ?>
                <?php unset($_SESSION['errors']); ?>
            <?php endif; ?>
            <?php if(!empty($_SESSION['success'])): ?>
                <p class="success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
            <?php endif; ?>
        </div>

        <form method="POST" action="">
            <input type="text" name="name" placeholder="Organization Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Register</button>
        </form>

        <a href="register.php">Back to Volunteer Registration</a>
    </div>
</body>
</html>

