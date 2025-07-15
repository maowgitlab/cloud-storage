<?php
require_once 'log.php';

function upload_file($conn, $user_id, $file) {
    $target_dir = UPLOAD_DIR . $user_id . '/';
    if (!file_exists($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            error_log("Failed to create directory: $target_dir");
            return ['success' => false, 'message' => "Gagal membuat folder untuk pengguna"];
        }
        // Set izin folder
        if (!chmod($target_dir, 0777)) {
            error_log("Failed to set permissions for directory: $target_dir");
            return ['success' => false, 'message' => "Gagal mengatur izin folder"];
        }
    }
    
    $file_name = basename($file['name']);
    $file_path = $target_dir . time() . '_' . $file_name;
    $file_size = $file['size'];
    $file_type = $file['type'];
    
    // Validasi tipe file berdasarkan MIME type dan ekstensi
    $allowed_types = [
        'application/zip' => ['.zip'],
        'application/x-7z-compressed' => ['.7z'],
        'application/octet-stream' => ['.7z', '.apk', '.exe'], // Fallback untuk 7z, apk, exe
        'image/jpeg' => ['.jpg', '.jpeg'],
        'image/png' => ['.png'],
        'video/mp4' => ['.mp4'],
        'application/pdf' => ['.pdf'],
        'application/msword' => ['.doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['.docx'],
        'application/vnd.ms-powerpoint' => ['.ppt'],
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => ['.pptx'],
        'application/vnd.ms-excel' => ['.xls'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['.xlsx'],
        'application/vnd.android.package-archive' => ['.apk'],
        'application/x-msdownload' => ['.exe']
    ];
    
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $is_valid_type = false;
    foreach ($allowed_types as $mime => $extensions) {
        if ($file_type === $mime || in_array('.' . $file_ext, $extensions)) {
            $is_valid_type = true;
            $file_type = $mime; // Gunakan MIME type standar
            break;
        }
    }
    
    if (!$is_valid_type) {
        error_log("Invalid file type for $file_name: $file_type, extension: $file_ext");
        return ['success' => false, 'message' => "Tipe file tidak diizinkan: $file_name"];
    }
    
    // Validasi ukuran file (maksimum 10MB per file)
    if ($file_size > 10 * 1024 * 1024) {
        error_log("File too large: $file_name ($file_size bytes)");
        return ['success' => false, 'message' => "File terlalu besar: $file_name (maksimum 10MB)"];
    }
    
    // Periksa total penyimpanan yang digunakan
    $used_space = get_used_storage($conn, $user_id);
    $max_space = 100 * 1024 * 1024; // 100MB dalam byte
    if (($used_space + $file_size) > $max_space) {
        return ['success' => false, 'message' => "Penyimpanan penuh. Anda telah mencapai batas 100MB."];
    }
    
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        $file_name = $conn->real_escape_string($file_name);
        $file_path = $conn->real_escape_string($file_path);
        $sql = "INSERT INTO files (user_id, file_name, file_path, file_size, file_type) VALUES ($user_id, '$file_name', '$file_path', $file_size, '$file_type')";
        if ($conn->query($sql)) {
            log_activity($conn, $user_id, 'upload', "File $file_name uploaded");
            return ['success' => true, 'message' => "File $file_name berhasil diunggah"];
        } else {
            error_log("Database error for $file_name: " . $conn->error);
            unlink($file_path); // Hapus file jika gagal menyimpan ke database
            return ['success' => false, 'message' => "Gagal menyimpan ke database: $file_name"];
        }
    } else {
        error_log("Failed to move uploaded file: $file_name");
        return ['success' => false, 'message' => "Gagal memindahkan file: $file_name"];
    }
}

function get_personal_files($conn, $user_id) {
    $sql = "SELECT * FROM files WHERE user_id = $user_id ORDER BY uploaded_at DESC";
    $result = $conn->query($sql);
    if (!$result) {
        error_log("Error fetching personal files for user $user_id: " . $conn->error);
        return [];
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_shared_files_received($conn, $user_id) {
    $sql = "SELECT fs.id AS share_id, f.id AS file_id, f.file_name, f.file_path, f.file_size, f.file_type, f.uploaded_at, fs.shared_at, fs.password, fs.note, u.username AS from_username
            FROM files f
            INNER JOIN file_shares fs ON f.id = fs.file_id
            INNER JOIN users u ON fs.from_user_id = u.id
            WHERE fs.to_user_id = $user_id
            ORDER BY fs.shared_at DESC";
    $result = $conn->query($sql);
    if (!$result) {
        error_log("Error fetching shared files received for user $user_id: " . $conn->error);
        return [];
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_shared_files_sent($conn, $user_id) {
    $sql = "SELECT fs.id AS share_id, f.id AS file_id, f.file_name, f.file_path, f.file_size, f.file_type, f.uploaded_at, fs.shared_at, fs.password, fs.note, u.username AS to_username
            FROM files f
            INNER JOIN file_shares fs ON f.id = fs.file_id
            INNER JOIN users u ON fs.to_user_id = u.id
            WHERE fs.from_user_id = $user_id
            ORDER BY fs.shared_at DESC";
    $result = $conn->query($sql);
    if (!$result) {
        error_log("Error fetching shared files sent for user $user_id: " . $conn->error);
        return [];
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_all_users($conn, $exclude_user_id) {
    $sql = "SELECT id, username FROM users WHERE id != $exclude_user_id ORDER BY username";
    $result = $conn->query($sql);
    if (!$result) {
        error_log("Error fetching users: " . $conn->error);
        return [];
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

function share_file($conn, $from_user_id, $to_user_id, $file_id, $password = null, $note = null) {
    // Validasi file milik pengguna
    $sql = "SELECT file_name FROM files WHERE id = $file_id AND user_id = $from_user_id";
    $result = $conn->query($sql);
    if ($result->num_rows === 0) {
        error_log("Share failed: File $file_id not found or not owned by user $from_user_id");
        return ['success' => false, 'message' => "File tidak ditemukan atau bukan milik Anda"];
    }
    $file = $result->fetch_assoc();
    $file_name = $file['file_name'];

    // Validasi penerima
    $sql = "SELECT id FROM users WHERE id = $to_user_id";
    $result = $conn->query($sql);
    if ($result->num_rows === 0) {
        error_log("Share failed: Recipient user $to_user_id not found");
        return ['success' => false, 'message' => "Penerima tidak ditemukan"];
    }

    // Cek apakah file sudah dibagikan ke penerima
    $sql = "SELECT id FROM file_shares WHERE file_id = $file_id AND to_user_id = $to_user_id";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        error_log("Share failed: File $file_id already shared to user $to_user_id");
        return ['success' => false, 'message' => "File $file_name sudah dibagikan ke penerima"];
    }

    // Hash password jika ada
    $password_hash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;
    $password_sql = $password_hash ? "'$password_hash'" : 'NULL';
    $note = $conn->real_escape_string($note ?? '');

    // Simpan data berbagi
    $sql = "INSERT INTO file_shares (file_id, from_user_id, to_user_id, shared_at, password, note) VALUES ($file_id, $from_user_id, $to_user_id, NOW(), $password_sql, '$note')";
    if ($conn->query($sql)) {
        log_activity($conn, $from_user_id, 'share', "File $file_name shared to user $to_user_id" . ($password_hash ? ' with password' : '') . ($note ? " with note: $note" : ''));
        return ['success' => true, 'message' => "File $file_name berhasil dibagikan"];
    } else {
        error_log("Share failed: Database error for file $file_id to user $to_user_id: " . $conn->error);
        return ['success' => false, 'message' => "Gagal membagikan file $file_name"];
    }
}

function delete_file($conn, $file_id, $user_id) {
    $sql = "SELECT file_path, file_name FROM files WHERE id = $file_id AND user_id = $user_id";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $file = $result->fetch_assoc();
        if (file_exists($file['file_path'])) {
            if (unlink($file['file_path'])) {
                $sql = "DELETE FROM files WHERE id = $file_id";
                if ($conn->query($sql)) {
                    log_activity($conn, $user_id, 'delete', "File $file[file_name] deleted");
                    return true;
                }
            } else {
                error_log("Failed to unlink file: $file[file_path]");
                return false;
            }
        } else {
            error_log("File does not exist: $file[file_path]");
            $sql = "DELETE FROM files WHERE id = $file_id";
            if ($conn->query($sql)) {
                log_activity($conn, $user_id, 'delete', "File $file[file_name] deleted (file not found on server)");
                return true;
            }
        }
    }
    return false;
}

function rename_file($conn, $file_id, $user_id, $new_name) {
    // Validasi nama baru
    if (empty($new_name)) {
        error_log("Rename failed: New name is empty for file_id $file_id");
        return false;
    }

    $new_name = $conn->real_escape_string($new_name);
    $sql = "UPDATE files SET file_name = '$new_name' WHERE id = $file_id AND user_id = $user_id";
    if ($conn->query($sql)) {
        log_activity($conn, $user_id, 'rename', "File renamed to $new_name");
        return true;
    } else {
        error_log("Rename failed: Database error for file_id $file_id: " . $conn->error);
        return false;
    }
}

function delete_file_share($conn, $share_id, $user_id) {
    // Validasi bahwa pengguna adalah pengirim atau penerima
    $sql = "SELECT fs.file_id, f.file_name, fs.from_user_id, fs.to_user_id 
            FROM file_shares fs 
            INNER JOIN files f ON fs.file_id = f.id 
            WHERE fs.id = $share_id AND (fs.from_user_id = $user_id OR fs.to_user_id = $user_id)";
    $result = $conn->query($sql);
    if ($result->num_rows === 0) {
        error_log("Delete share failed: Share $share_id not found or user $user_id not authorized");
        return ['success' => false, 'message' => "File sharing tidak ditemukan atau Anda tidak memiliki akses"];
    }

    $share = $result->fetch_assoc();
    $file_name = $share['file_name'];
    $role = ($share['from_user_id'] == $user_id) ? 'pengirim' : 'penerima';

    // Hapus entri file sharing
    $sql = "DELETE FROM file_shares WHERE id = $share_id";
    if ($conn->query($sql)) {
        log_activity($conn, $user_id, 'delete_share', "File sharing for $file_name deleted by $role");
        return ['success' => true, 'message' => "File sharing untuk $file_name berhasil dihapus"];
    } else {
        error_log("Delete share failed: Database error for share $share_id: " . $conn->error);
        return ['success' => false, 'message' => "Gagal menghapus file sharing"];
    }
}

function get_used_storage($conn, $user_id) {
    $sql = "SELECT SUM(file_size) as total_size FROM files WHERE user_id = $user_id";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['total_size'] ?: 0; // Kembalikan 0 jika NULL
    }
    return 0;
}
?>