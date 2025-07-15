<?php
ob_start();
session_start();
require_once 'config.php';
require_once 'functions/file_manager.php';
require_once 'functions/log.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json', true, 401);
    echo json_encode(['status' => 'error', 'message' => 'Anda harus login untuk menghapus file sharing']);
    ob_end_flush();
    exit;
}

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['share_id'])) {
    $share_id = (int)$_POST['share_id'];
    
    $result = delete_file_share($conn, $share_id, $user_id);
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $result['success'] ? 'success' : 'error',
        'message' => $result['message']
    ]);
    ob_end_flush();
    exit;
}

header('Content-Type: application/json', true, 400);
echo json_encode(['status' => 'error', 'message' => 'Permintaan tidak valid']);
ob_end_flush();
exit;
?>