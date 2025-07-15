<?php
// Mulai sesi dan pastikan tidak ada output sebelum header
ob_start();
session_start();
require_once 'config.php';
require_once 'functions/file_manager.php';
require_once 'functions/log.php';

// Periksa apakah sesi user_id ada
if (!isset($_SESSION['user_id'])) {
    error_log("Download failed: No user session found");
    header('Location: login.php');
    ob_end_flush();
    exit;
}

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['file_id']) && isset($_POST['password'])) {
    // Validasi password untuk file sharing
    $file_id = (int)$_POST['file_id'];
    $password = $_POST['password'];

    // Query untuk mendapatkan informasi file dan status kepemilikan
    $sql = "SELECT f.id, f.user_id, f.file_path, f.file_name, f.file_type, fs.password
            FROM files f
            LEFT JOIN file_shares fs ON f.id = fs.file_id
            WHERE f.id = $file_id AND (f.user_id = $user_id OR fs.to_user_id = $user_id)";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $file = $result->fetch_assoc();
        $file_path = $file['file_path'];
        $file_name = $file['file_name'];
        $file_type = $file['file_type'];

        // Periksa integritas file
        if (!file_exists($file_path) || !is_readable($file_path)) {
            error_log("Download failed: File not found or not readable on server: $file_path");
            header('Content-Type: application/json', true, 404);
            echo json_encode(['status' => 'error', 'message' => 'File tidak ditemukan atau tidak dapat dibaca di server']);
            ob_end_flush();
            exit;
        }

        // Jika file milik pengguna, lewati validasi password
        if ($file['user_id'] == $user_id) {
            log_activity($conn, $user_id, 'download', "File $file_name downloaded by owner");
            send_file($file_path, $file_name, $file_type);
            exit;
        }

        // Validasi password untuk file yang dibagikan
        if ($file['password'] && !password_verify($password, $file['password'])) {
            error_log("Download failed: Incorrect password for file $file_id by user $user_id");
            header('Content-Type: application/json', true, 403);
            echo json_encode(['status' => 'error', 'message' => 'Password salah']);
            ob_end_flush();
            exit;
        }

        // Password benar atau tidak ada password, lanjutkan unduhan
        log_activity($conn, $user_id, 'download', "File $file_name downloaded");
        send_file($file_path, $file_name, $file_type);
        exit;
    } else {
        error_log("Download failed: File $file_id not found or no access for user $user_id");
        header('Content-Type: application/json', true, 403);
        echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki akses ke file ini']);
        ob_end_flush();
        exit;
    }
}

if (isset($_GET['id'])) {
    $file_id = (int)$_GET['id'];

    // Query untuk mendapatkan informasi file dan status kepemilikan
    $sql = "SELECT f.id, f.user_id, f.file_path, f.file_name, f.file_type, fs.password
            FROM files f
            LEFT JOIN file_shares fs ON f.id = fs.file_id
            WHERE f.id = $file_id AND (f.user_id = $user_id OR fs.to_user_id = $user_id)";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $file = $result->fetch_assoc();
        $file_path = $file['file_path'];
        $file_name = $file['file_name'];
        $file_type = $file['file_type'];

        // Periksa integritas file
        if (!file_exists($file_path) || !is_readable($file_path)) {
            error_log("Download failed: File not found or not readable on server: $file_path");
            header('Content-Type: application/json', true, 404);
            echo json_encode(['status' => 'error', 'message' => 'File tidak ditemukan atau tidak dapat dibaca di server']);
            ob_end_flush();
            exit;
        }

        // Jika file milik pengguna, lewati validasi password
        if ($file['user_id'] == $user_id) {
            log_activity($conn, $user_id, 'download', "File $file_name downloaded by owner");
            send_file($file_path, $file_name, $file_type);
            exit;
        }

        // Jika file memiliki password dan bukan milik pengguna, minta password
        if ($file['password']) {
            header('Content-Type: application/json', true);
            echo json_encode(['status' => 'password_required', 'file_id' => $file_id]);
            ob_end_flush();
            exit;
        }

        // File tidak memiliki password, lanjutkan unduhan
        log_activity($conn, $user_id, 'download', "File $file_name downloaded");
        send_file($file_path, $file_name, $file_type);
        exit;
    } else {
        error_log("Download failed: File $file_id not found or no access for user $user_id");
        header('Content-Type: application/json', true, 403);
        echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki akses ke file ini']);
        ob_end_flush();
        exit;
    }
} else {
    error_log("Download failed: Invalid file ID");
    header('Content-Type: application/json', true, 400);
    echo json_encode(['status' => 'error', 'message' => 'Permintaan tidak valid']);
    ob_end_flush();
    exit;
}

// Fungsi untuk mengirim file dengan header yang benar
function send_file($file_path, $file_name, $file_type) {
    // Pastikan tidak ada output sebelum header
    ob_end_clean();

    // Set header untuk unduhan
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $file_type);
    header('Content-Disposition: attachment; filename="' . basename($file_name) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));
    
    // Bersihkan buffer dan kirim file
    flush();
    readfile($file_path);
    exit;
}
?>