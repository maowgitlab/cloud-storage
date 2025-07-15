
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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['files'])) {
    $files = $_FILES['files'];
    $responses = [];
    
    // Mengatur ulang array file untuk mempermudah pengolahan
    $file_count = count($files['name']);
    $uploaded_files = [];
    
    for ($i = 0; $i < $file_count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $responses[] = ['status' => 'error', 'message' => "Gagal mengunggah file {$files['name'][$i]}: Error code {$files['error'][$i]}"];
            continue;
        }
        
        $file = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'size' => $files['size'][$i]
        ];
        
        $result = upload_file($conn, $_SESSION['user_id'], $file);
        $responses[] = $result;
        
        if ($result['success']) {
            $uploaded_files[] = $result['message'];
        }
    }
    
    $success_count = count(array_filter($responses, fn($r) => $r['success']));
    $error_count = $file_count - $success_count;
    
    if ($success_count === $file_count) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Semua file berhasil diunggah:<br>' . implode('<br>', $uploaded_files)
        ]);
    } elseif ($success_count > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => "Berhasil mengunggah $success_count file:<br>" . implode('<br>', $uploaded_files) . '<br>Gagal: ' . implode('<br>', array_column(array_filter($responses, fn($r) => !$r['success']), 'message'))
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Gagal mengunggah semua file:<br>' . implode('<br>', array_column($responses, 'message'))
        ]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Permintaan tidak valid']);
}

ob_end_flush();
exit;
?>