<?php
// includes/auth_guard.php
// ONLY protects pages that REALLY need login (like post_opportunity, manage_events, my_applications)
// DO NOT use this on index.php, public_browse.php, leaderboard.php, etc.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// List of pages that REQUIRE login
$protected_pages = [
    'post_opportunity.php',
    'manage_events.php',
    'my_applications.php',
    // add more later if needed
];

$current_page = basename($_SERVER['SCRIPT_NAME']);

if (in_array($current_page, $protected_pages)) {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: ../auth/login.php");
        exit();
    }
}
?>