<?php
session_start();
require_once 'config/config.php';
require_once 'includes/auth_guard.php';

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['organization','admin'])) exit;

$app_id = (int)($_POST['app_id'] ?? 0);

// Approve / Reject
if (isset($_POST['action']) && in_array($_POST['action'], ['approve','reject'])){
    $status = $_POST['action']==='approve'?'confirmed':'cancelled';
    $conn->prepare("UPDATE applications SET status=? WHERE id=?")->execute([$status,$app_id]);
    exit;
}

// Save hours
if (isset($_POST['save_hours'])){
    $hours = max(0,min(100,(float)$_POST['hours']));
    $conn->prepare("UPDATE applications SET hours_worked=?, hours_approved=0 WHERE id=?")->execute([$hours,$app_id]);
    echo json_encode(['success'=>true]);
    exit;
}

if (isset($_POST['approve_hours'])){
    $stmt = $conn->prepare("SELECT hours_worked, volunteer_id FROM applications WHERE id=?");
    $stmt->execute([$app_id]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($app && $app['hours_worked']>0){
        $conn->prepare("UPDATE applications SET hours_approved=1 WHERE id=?")->execute([$app_id]);
        $conn->prepare("UPDATE users SET total_verified_hours = total_verified_hours + ? WHERE id=?")
             ->execute([$app['hours_worked'], $app['volunteer_id']]);

        echo json_encode(['success'=>true, 'hours_approved' => (int)$app['hours_worked']]);
    } else {
        echo json_encode(['success'=>false, 'message'=>'No hours to approve']);
    }
    exit;
}

