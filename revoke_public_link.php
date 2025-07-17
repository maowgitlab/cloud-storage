<?php
require_once 'config.php';
require_once 'functions/file_manager.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_id'])) {
    $file_id = (int)$_POST['file_id'];
    $user_id = $_SESSION['user_id'];

    // Verifikasi kepemilikan file
    $sql = "SELECT file_name FROM files WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $file_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $file = $result->fetch_assoc();
        $file_name = $file['file_name'];

        // Hapus tautan publik
        $sql = "DELETE FROM public_links WHERE file_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $file_id);
        if ($stmt->execute()) {
            log_activity($conn, $user_id, 'revoke_public_link', "Public link revoked for $file_name");
            echo json_encode(['success' => true]);
        } else {
            error_log("Failed to revoke public link for file_id $file_id: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Gagal mencabut tautan publik']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'File tidak ditemukan atau Anda tidak memiliki akses']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Permintaan tidak valid']);
}
?>