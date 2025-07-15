<?php
session_start();
require_once 'config.php';
require_once 'functions/file_manager.php';

// Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['status' => 'error', 'message' => 'Anda harus login untuk mengakses fitur ini.']);
    exit;
}

$file_ids = isset($_POST['file_ids']) ? explode(',', $_POST['file_ids']) : [];
if (empty($file_ids)) {
    echo json_encode(['status' => 'error', 'message' => 'Tidak ada file yang dipilih untuk backup.']);
    exit;
}

// Logging untuk debugging
error_log("Backup requested at " . date('Y-m-d H:i:s') . " for user_id: " . $_SESSION['user_id'] . ", file_ids: " . implode(',', $file_ids));

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="backup_' . date('Ymd_His') . '.zip"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Pastikan ekstensi ZipArchive aktif
if (!class_exists('ZipArchive')) {
    error_log("ZipArchive extension is not enabled.");
    echo json_encode(['status' => 'error', 'message' => 'Ekstensi ZipArchive tidak aktif di server. Hubungi administrator.']);
    exit;
}

$zip = new ZipArchive();
$temp_zip_path = sys_get_temp_dir() . '/backup_' . uniqid() . '.zip';

if ($zip->open($temp_zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === FALSE) {
    error_log("Failed to open/create ZIP file at: $temp_zip_path");
    echo json_encode(['status' => 'error', 'message' => 'Gagal menginisialisasi file ZIP.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$files = get_personal_files($conn, $user_id);
$added_files = 0;

foreach ($files as $file) {
    if (in_array($file['id'], $file_ids)) {
        if (file_exists($file['file_path'])) {
            $relative_path = $file['file_name']; // Gunakan nama file asli sebagai nama di ZIP
            if ($zip->addFile($file['file_path'], $relative_path)) {
                $added_files++;
                error_log("Added file to ZIP: " . $file['file_path'] . " as " . $relative_path);
            } else {
                error_log("Failed to add file to ZIP: " . $file['file_path']);
            }
        } else {
            error_log("File not found during backup: " . $file['file_path']);
        }
    }
}

if ($added_files === 0) {
    $zip->close();
    unlink($temp_zip_path);
    echo json_encode(['status' => 'error', 'message' => 'Tidak ada file yang dapat dibackup.']);
    exit;
}

$zip->close();

if (file_exists($temp_zip_path)) {
    $zip_size = filesize($temp_zip_path);
    if ($zip_size > 0) {
        error_log("ZIP file created successfully, size: $zip_size bytes at $temp_zip_path");
        // Kirim file ke klien
        $fp = fopen($temp_zip_path, 'rb');
        if ($fp) {
            while (!feof($fp)) {
                echo fread($fp, 8192); // Baca dan kirim dalam potongan 8KB
                flush(); // Pastikan data dikirim segera
            }
            fclose($fp);
            unlink($temp_zip_path); // Hapus file sementara setelah pengiriman
        } else {
            error_log("Failed to open temp ZIP file for reading: $temp_zip_path");
            echo json_encode(['status' => 'error', 'message' => 'Gagal membaca file ZIP untuk pengunduhan.']);
            unlink($temp_zip_path);
            exit;
        }
    } else {
        error_log("ZIP file is empty: $temp_zip_path");
        unlink($temp_zip_path);
        echo json_encode(['status' => 'error', 'message' => 'File ZIP yang dihasilkan kosong.']);
        exit;
    }
} else {
    error_log("ZIP file was not created at: $temp_zip_path");
    echo json_encode(['status' => 'error', 'message' => 'Gagal membuat file ZIP.']);
    exit;
}
?>