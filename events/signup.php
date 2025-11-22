<?php
include '../config/config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'])) {
    $user_id = $_SESSION['user_id'];
    $event_id = $_POST['event_id'];


    $stmt = $conn->prepare("SELECT * FROM event_signups WHERE user_id = ? AND event_id = ?");
    $stmt->execute([$user_id, $event_id]);

    if ($stmt->rowCount() === 0) {
        // Insert signup
        $insert = $conn->prepare("INSERT INTO event_signups (user_id, event_id) VALUES (?, ?)");
        $insert->execute([$user_id, $event_id]);
    }

    // Redirect back to homepage
    header("Location: ../index.php");
    exit;
}
