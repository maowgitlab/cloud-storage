<?php
require_once 'config.php';
require_once 'functions/file_manager.php';

header('Content-Type: application/json');

if (isset($_SERVER['PATH_INFO']) && preg_match('/^\/([a-f0-9\-]+)\/(.+)$/', $_SERVER['PATH_INFO'], $matches)) {
    $uuid = $matches[1];
    $file_name = urldecode($matches[2]);

    $sql = "SELECT f.file_path, f.file_type FROM public_links pl
            INNER JOIN files f ON pl.file_id = f.id
            WHERE pl.uuid = ? AND pl.expires_at > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $uuid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $file = $result->fetch_assoc();
        $file_path = $file['file_path'];
        $file_type = $file['file_type'];

        if (file_exists($file_path)) {
            header('Content-Type: ' . $file_type);
            header('Content-Disposition: attachment; filename="' . basename($file_name) . '"');
            readfile($file_path);
            exit;
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'File tidak ditemukan']);
            exit;
        }
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tautan tidak valid atau telah kedaluwarsa']);
        exit;
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Permintaan tidak valid']);
    exit;
}
?>