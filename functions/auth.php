<?php
require_once 'log.php';

function register($conn, $username, $email, $password) {
    $username = $conn->real_escape_string(trim($username));
    $email = $conn->real_escape_string(trim($email));
    
    // Periksa apakah email sudah digunakan
    $sql = "SELECT id FROM users WHERE email = '$email'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        return 'Email sudah digunakan. Silakan gunakan email lain.';
    }
    
    // Validasi input
    if (empty($username) || empty($email) || empty($password)) {
        return 'Semua field harus diisi.';
    }
    if (strlen($password) < 6) {
        return 'Kata sandi minimal 6 karakter.';
    }
    
    $password = password_hash($password, PASSWORD_BCRYPT);
    
    $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";
    if ($conn->query($sql)) {
        $user_id = $conn->insert_id;
        log_activity($conn, $user_id, 'register', "User $username registered");
        return true;
    }
    return 'Gagal mendaftar. Silakan coba lagi.';
}

function login($conn, $email, $password) {
    $email = $conn->real_escape_string($email);
    $sql = "SELECT id, username, password FROM users WHERE email = '$email'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            session_regenerate_id(true);
            log_activity($conn, $user['id'], 'login', "User $user[username] logged in");
            return true;
        }
    }
    return false;
}
?>