
<?php
ob_start();
session_start();
require_once 'config.php';
require_once 'functions/file_manager.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    ob_end_flush();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id']) && isset($_POST['new_name']) && isset($_POST['ext'])) {
    $file_id = (int)$_POST['id'];
    $new_name = trim($_POST['new_name']);
    $ext = $_POST['ext'];

    // Validasi nama baru
    if (empty($new_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama file tidak boleh kosong']);
        ob_end_flush();
        exit;
    }

    // Gabungkan nama baru dengan ekstensi asli
    $full_new_name = $new_name . $ext;

    if (rename_file($conn, $file_id, $_SESSION['user_id'], $full_new_name)) {
        echo json_encode(['status' => 'success', 'message' => 'File berhasil diubah namanya menjadi ' . htmlspecialchars($full_new_name)]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengubah nama file']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Permintaan tidak valid']);
}

ob_end_flush();
exit;
?>