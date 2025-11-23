<?php
/**
 * Utility script to recalculate total_hours for all users
 * Run this once to fix any data inconsistencies
 */
session_start();
require_once 'config/config.php';

// Only allow admin or run from command line
if (php_sapi_name() !== 'cli') {
    require_once 'includes/auth_guard.php';
    if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
        die('Access denied. Admin only.');
    }
}

echo "Recalculating hours for all users...\n\n";

// Get all volunteers
$users = $conn->query("SELECT id, name FROM users WHERE role IN ('user', 'volunteer')")->fetchAll(PDO::FETCH_ASSOC);

$updated = 0;
$total_hours_found = 0;

foreach ($users as $user) {
    // Calculate actual hours from confirmed applications
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(hours_logged), 0) as total
        FROM applications
        WHERE volunteer_id = ? AND status = 'confirmed' AND hours_logged > 0
    ");
    $stmt->execute([$user['id']]);
    $calculated_hours = (int)$stmt->fetchColumn();
    
    // Get current total_hours from users table
    $current_stmt = $conn->prepare("SELECT total_hours FROM users WHERE id = ?");
    $current_stmt->execute([$user['id']]);
    $current_hours = (int)$current_stmt->fetchColumn();
    
    if ($calculated_hours != $current_hours) {
        // Update the users table
        $update_stmt = $conn->prepare("UPDATE users SET total_hours = ? WHERE id = ?");
        $update_stmt->execute([$calculated_hours, $user['id']]);
        
        echo "Updated {$user['name']} (ID: {$user['id']}): {$current_hours} -> {$calculated_hours} hours\n";
        $updated++;
    }
    
    $total_hours_found += $calculated_hours;
}

echo "\n";
echo "Recalculation complete!\n";
echo "Users updated: {$updated}\n";
echo "Total hours across all users: {$total_hours_found}\n";

if (php_sapi_name() !== 'cli') {
    echo "<br><br><a href='admin/admin_dashboard.php'>Back to Admin Dashboard</a>";
}
?>

