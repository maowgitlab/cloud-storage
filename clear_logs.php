<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Hapus semua log untuk user yang sedang login
    $sql = "DELETE FROM activity_logs WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Log berhasil dihapus.';
    } else {
        $response['message'] = 'Gagal menghapus log.';
    }
    $stmt->close();
} else {
    $response['message'] = 'Akses tidak sah.';
}

echo json_encode($response);
exit;
?>