<?php
require_once 'config.php';
require_once 'functions/file_manager.php';

if (isset($_GET['uuid'])) {
    $uuid = $_GET['uuid'];

    $sql = "SELECT f.file_path, f.file_name, f.file_type 
            FROM public_links pl
            INNER JOIN files f ON pl.file_id = f.id
            WHERE pl.uuid = ? AND pl.expires_at > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $uuid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $file = $result->fetch_assoc();
        $file_path = $file['file_path'];
        $file_name = $file['file_name'];
        $file_type = $file['file_type'];

        if (file_exists($file_path)) {
            header('Content-Type: ' . $file_type);
            header('Content-Disposition: attachment; filename="' . basename($file_name) . '"');
            readfile($file_path);
            exit;
        } else {
            http_response_code(404);
            echo "File tidak ditemukan.";
            exit;
        }
    } else {
        http_response_code(404);
        echo "Tautan tidak valid atau telah kedaluwarsa.";
        exit;
    }
} else {
    http_response_code(400);
    echo "Permintaan tidak valid.";
    exit;
}
?>