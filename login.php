
<?php
ob_start();
session_start();
require_once 'config.php';
require_once 'functions/auth.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    if (login($conn, $email, $password)) {
        ob_end_clean();
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "Email atau kata sandi salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cloud Storage</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card shadow-sm border-0 p-4" style="max-width: 400px; width: 100%;">
            <div class="text-center mb-4">
                <i class="fas fa-cloud fa-2x text-primary"></i>
                <h2 class="fw-semibold mt-2">Login</h2>
                <p class="text-muted">Masuk ke akun Cloud Storage Anda</p>
            </div>
            <?php if (isset($error)): ?>
                <script>
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
                        text: '<?php echo $error; ?>'
                    });
                </script>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label"><i class="fas fa-envelope me-2"></i> Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label"><i class="fas fa-lock me-2"></i> Kata Sandi</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-sign-in-alt me-2"></i> Login</button>
                <p class="mt-3 text-center text-muted">Belum punya akun? <a href="register.php" class="text-primary">Daftar</a></p>
            </form>
        </div>
    </div>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>