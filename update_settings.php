<?php
session_start();
require_once 'config.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $new_username = $conn->real_escape_string($_POST['newUsername'] ?? $_SESSION['username']);
    $new_email = $conn->real_escape_string($_POST['newEmail'] ?? '');
    $current_password = $_POST['currentPassword'] ?? '';
    $new_password = $_POST['newPassword'] ?? '';

    // Ambil data pengguna
    $sql = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // Verifikasi password lama jika ada perubahan
    if ($new_username !== $_SESSION['username'] || $new_email || $new_password) {
        if (!$current_password || !password_verify($current_password, $user['password'])) {
            $response['message'] = 'Password lama salah atau tidak diisi saat ada perubahan.';
        } else {
            // Periksa apakah email baru sudah digunakan oleh pengguna lain
            if ($new_email) {
                $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $new_email, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $response['message'] = 'Email sudah digunakan oleh pengguna lain.';
                    echo json_encode($response);
                    exit;
                }
                $stmt->close();
            }

            // Update data
            $update_sql = "UPDATE users SET username = ?";
            $params = [$new_username];
            $types = "s";
            if ($new_email) {
                $update_sql .= ", email = ?";
                $params[] = $new_email;
                $types .= "s";
            }
            if ($new_password) {
                $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
                $update_sql .= ", password = ?";
                $params[] = $new_password_hash;
                $types .= "s";
            }
            $update_sql .= " WHERE id = ?";
            $params[] = $user_id;
            $types .= "i";

            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                $_SESSION['username'] = $new_username;
                if ($new_email) $_SESSION['email'] = $new_email;
                $response['success'] = true;
                $response['message'] = 'Pengaturan berhasil diperbarui.';
            } else {
                $response['message'] = 'Gagal memperbarui pengaturan.';
            }
            $stmt->close();
        }
    } else {
        $response['success'] = true;
        $response['message'] = 'Tidak ada perubahan yang dilakukan.';
    }
    echo json_encode($response);
    exit;
}
?>