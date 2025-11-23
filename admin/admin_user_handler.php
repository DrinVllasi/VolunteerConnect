<?php
session_start();
require_once '../config/config.php';

if(!isset($_SESSION['logged_in']) || $_SESSION['role']!=='admin'){
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'),true);
$action = $input['action'] ?? '';
$id = (int)($input['id'] ?? 0);
$name = trim($input['name'] ?? '');

if(!$id){
    echo json_encode(['success'=>false,'message'=>'Invalid ID']);
    exit();
}

try{
    if($action==='delete'){
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(['success'=>true]);
        exit();
    }
    if($action==='edit' && $name!==''){
        $stmt = $conn->prepare("UPDATE users SET name=? WHERE id=?");
        $stmt->execute([$name,$id]);
        echo json_encode(['success'=>true]);
        exit();
    }
    echo json_encode(['success'=>false,'message'=>'Invalid action']);
}catch(Exception $e){
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
?>
