
<?php
ob_start();
session_start();
require_once 'config.php';
require_once 'functions/log.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    ob_end_flush();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['file_id']) && isset($_POST['password'])) {
    $file_id = (int)$_POST['file_id'];
    $password = $_POST['password'];
    $user_id = $_SESSION['user_id'];

    $sql = "SELECT fs.password
            FROM file_shares fs
            WHERE fs.file_id = $file_id AND fs.to_user_id = $user_id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $file = $result->fetch_assoc();
        if ($file['password'] && !password_verify($password, $file['password'])) {
            echo json_encode(['status' => 'error', 'message' => 'Password salah']);
            ob_end_flush();
            exit;
        }
        echo json_encode(['status' => 'success', 'message' => 'Password benar']);
    } else {
        error_log("Password verification failed: File $file_id not found or no access for user $user_id");
        echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki akses ke file ini']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Permintaan tidak valid']);
}

ob_end_flush();
exit;
?>