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
    $sql = "SELECT file_name, file_path FROM files WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $file_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $file = $result->fetch_assoc();
        $file_name = $file['file_name'];
        $file_path = $file['file_path'];
        $file_dir = dirname($file_path); // Mendapatkan direktori (uploads/id_user/)

        // Hapus tautan publik lama jika ada
        $sql = "DELETE FROM public_links WHERE file_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $file_id);
        $stmt->execute();

        // Generate UUID baru
        $uuid = uniqid() . '-' . bin2hex(random_bytes(4)); // UUID sederhana untuk contoh
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        $public_link = $base_url . '/cloud-storage/download_public.php?uuid=' . $uuid; // Menggunakan query string untuk akses

        // Simpan tautan publik ke database
        $sql = "INSERT INTO public_links (file_id, uuid, file_path, created_at, expires_at) VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 24 HOUR))";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $file_id, $uuid, $file_path);
        if ($stmt->execute()) {
            log_activity($conn, $user_id, 'generate_public_link', "Public link generated for $file_name");
            echo json_encode(['success' => true, 'link' => $public_link]);
        } else {
            error_log("Failed to save public link for file_id $file_id: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan tautan publik']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'File tidak ditemukan atau Anda tidak memiliki akses']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Permintaan tidak valid']);
}
?>