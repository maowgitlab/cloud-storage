
<?php
ob_start();
session_start();
require_once 'config.php';
require_once 'functions/file_manager.php';
require_once 'functions/log.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    ob_end_flush();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $recipient_id = isset($_POST['recipient_id']) ? (int)$_POST['recipient_id'] : 0;
    $file_ids = isset($_POST['file_ids']) ? explode(',', $_POST['file_ids']) : [];
    $password = isset($_POST['password']) && !empty($_POST['password']) ? $_POST['password'] : null;
    $note = isset($_POST['note']) && !empty($_POST['note']) ? $_POST['note'] : null;

    if ($recipient_id <= 0 || empty($file_ids)) {
        echo json_encode(['status' => 'error', 'message' => 'Penerima atau file tidak valid']);
        ob_end_flush();
        exit;
    }

    $success_count = 0;
    $error_messages = [];
    $shared_files = [];

    foreach ($file_ids as $file_id) {
        $file_id = (int)$file_id;
        $result = share_file($conn, $_SESSION['user_id'], $recipient_id, $file_id, $password, $note);
        if ($result['success']) {
            $success_count++;
            $shared_files[] = $result['message'];
        } else {
            $error_messages[] = $result['message'];
        }
    }

    if ($success_count === count($file_ids)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Semua file berhasil dibagikan:<br>' . implode('<br>', $shared_files)
        ]);
    } elseif ($success_count > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => "Berhasil membagikan $success_count file:<br>" . implode('<br>', $shared_files) . '<br>Gagal: ' . implode('<br>', $error_messages)
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Gagal membagikan semua file:<br>' . implode('<br>', $error_messages)
        ]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Permintaan tidak valid']);
}

ob_end_flush();
exit;
?>