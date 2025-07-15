
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['ids'])) {
        // Penghapusan masal
        $file_ids = explode(',', $_POST['ids']);
        $success_count = 0;
        $error_messages = [];

        foreach ($file_ids as $file_id) {
            $file_id = (int)$file_id;
            if ($file_id > 0 && delete_file($conn, $file_id, $_SESSION['user_id'])) {
                $success_count++;
            } else {
                $error_messages[] = "Gagal menghapus file ID $file_id";
            }
        }

        if ($success_count === count($file_ids)) {
            echo json_encode(['status' => 'success', 'message' => "Berhasil menghapus $success_count file"]);
        } elseif ($success_count > 0) {
            echo json_encode(['status' => 'success', 'message' => "Berhasil menghapus $success_count file. Gagal: " . implode(', ', $error_messages)]);
        } else {
            echo json_encode(['status' => 'error', 'message' => "Gagal menghapus file: " . implode(', ', $error_messages)]);
        }
    } elseif (isset($_POST['id'])) {
        // Penghapusan satuan
        $file_id = (int)$_POST['id'];
        if (delete_file($conn, $file_id, $_SESSION['user_id'])) {
            echo json_encode(['status' => 'success', 'message' => 'File berhasil dihapus']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus file']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Permintaan tidak valid']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Permintaan tidak valid']);
}

ob_end_flush();
exit;
?>