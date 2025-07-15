<?php
session_start();
require_once 'config.php';
require_once 'functions/auth.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Cloud Storage</title>
    <!-- Bootstrap 5 CSS -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome untuk ikon -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <!-- CSS Kustom -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- SweetAlert2 -->
    <script src="assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>
</head>
<body>
    <!-- Konten Utama -->
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card shadow-sm border-0 p-4" style="max-width: 400px; width: 100%;">
            <div class="text-center mb-4">
                <i class="fas fa-user-plus fa-2x text-primary"></i>
                <h2 class="fw-semibold mt-2">Registrasi</h2>
                <p class="text-muted">Buat akun baru untuk Cloud Storage</p>
            </div>
            <?php
            // Inisialisasi variabel error
            $error = '';
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $username = $_POST['username'] ?? '';
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';

                error_log("Received POST data: username=$username, email=$email, password=****");

                $result = register($conn, $username, $email, $password);
                if ($result === true) {
                    $_SESSION['registration_success'] = true;
                    error_log("Registration successful for username: $username");
                    ?>
                    <script>
                        Swal.fire({
                            customClass: {
                                popup: 'swal-modern',
                                title: 'swal-title',
                                content: 'swal-content',
                                confirmButton: 'btn btn-primary'
                            },
                            buttonsStyling: false,
                            icon: 'success',
                            title: 'Berhasil',
                            text: 'Registrasi berhasil! Anda akan diarahkan ke halaman login.',
                            timer: 2000,
                            timerProgressBar: true,
                            willClose: () => {
                                window.location.href = 'login.php';
                            }
                        });
                    </script>
                    <?php
                    exit;
                } else {
                    $error = $result; // Simpan pesan error dari fungsi register
                    error_log("Registration failed: $error");
                }
            }
            ?>
            <?php if (!empty($error)): ?>
                <script>
                    console.log('Error alert triggered: <?php echo addslashes($error); ?>');
                    Swal.fire({
                        customClass: {
                            popup: 'swal-modern',
                            title: 'swal-title',
                            content: 'swal-content',
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false,
                        icon: 'error',
                        title: 'Error',
                        text: '<?php echo addslashes($error); ?>',
                        confirmButtonText: 'OK'
                    });
                </script>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label"><i class="fas fa-user me-2"></i> Username</label>
                    <input type="text" class="form-control" id="username" name="username" required value="<?php echo htmlspecialchars($username ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label"><i class="fas fa-envelope me-2"></i> Email</label>
                    <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label"><i class="fas fa-lock me-2"></i> Kata Sandi</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-user-plus me-2"></i> Daftar</button>
                <p class="mt-3 text-center text-muted">Sudah punya akun? <a href="login.php" class="text-primary">Login</a></p>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>