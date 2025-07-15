<?php
ob_start();
session_start();
require_once 'config.php';
require_once 'functions/file_manager.php';
require_once 'functions/log.php';

// Debug: Log session data
error_log("Dashboard.php session: " . print_r($_SESSION, true));

if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header('Location: login.php');
    exit;
}

// Ambil file pribadi, file yang dibagikan ke pengguna, dan file yang dibagikan oleh pengguna
$personal_files = get_personal_files($conn, $_SESSION['user_id']);
$shared_files_received = get_shared_files_received($conn, $_SESSION['user_id']);
$shared_files_sent = get_shared_files_sent($conn, $_SESSION['user_id']);

// Ambil daftar pengguna untuk form berbagi
$users = get_all_users($conn, $_SESSION['user_id']);

// Hitung statistik untuk file pribadi
$total_files = count($personal_files);
$total_size = array_sum(array_column($personal_files, 'file_size')) / 1024 / 1024; // MB
$used_storage = get_used_storage($conn, $_SESSION['user_id']) / 1024 / 1024; // MB
$max_storage = 100; // MB
$storage_percentage = min(($used_storage / $max_storage) * 100, 100);

// Periksa batas penyimpanan
$is_storage_full = ($used_storage >= $max_storage);

// Fungsi untuk memetakan MIME type ke ikon Font Awesome
function get_file_icon($file_type) {
    $icon_map = [
        'application/zip' => 'fa-file-zipper',
        'application/x-7z-compressed' => 'fa-file-archive',
        'application/octet-stream' => 'fa-file-archive', // Fallback untuk 7z, apk, exe
        'image/jpeg' => 'fa-file-image',
        'image/png' => 'fa-file-image',
        'video/mp4' => 'fa-file-video',
        'application/pdf' => 'fa-file-pdf',
        'application/msword' => 'fa-file-word',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'fa-file-word',
        'application/vnd.ms-powerpoint' => 'fa-file-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'fa-file-powerpoint',
        'application/vnd.ms-excel' => 'fa-file-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'fa-file-excel',
        'application/vnd.android.package-archive' => 'fa-file-archive',
        'application/x-msdownload' => 'fa-file-code'
    ];
    
    return isset($icon_map[$file_type]) ? $icon_map[$file_type] : 'fa-file';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Cloud Storage</title>
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
    <style>
        .storage-text {
            text-align: center;
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }
        .storage-text small {
            display: block;
            font-size: 0.9rem;
            font-weight: 400;
            color: #666;
        }
        .storage-text i {
            margin-right: 0.5rem;
        }
        .storage-text.warning {
            color: #ffc107;
        }
        .storage-text.danger {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <i class="fas fa-cloud me-2"></i> Cloud Storage
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#settingsModal"><i class="fas fa-cog me-1"></i> Pengaturan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Konten Utama -->
    <div class="container mt-5">
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h2 class="fw-semibold text-dark">Selamat Datang, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
                <p class="text-muted">Kelola file Anda dengan aman dan mudah.</p>
            </div>
        </div>

        <!-- Statistik -->
        <div class="row g-2 mb-5">
            <div class="col-md-6">
                <div class="card shadow-sm border-0 stats-card">
                    <div class="card-body d-flex align-items-center">
                        <i class="fas fa-file-alt fa-2x text-primary me-3"></i>
                        <div>
                            <h5 class="card-title mb-0">Total File Pribadi</h5>
                            <p class="card-text"><?php echo $total_files; ?> File</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm border-0 stats-card">
                    <div class="card-body d-flex align-items-center">
                        <i class="fas fa-hdd fa-2x text-primary me-3"></i>
                        <div>
                            <h5 class="card-title mb-0">Total Ukuran Pribadi</h5>
                            <p class="card-text"><?php echo number_format($total_size, 2); ?> MB</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 mt-3">
                <div class="card shadow-sm border-0 stats-card">
                    <div class="card-body">
                        <h5 class="card-title mb-2">Penyimpanan Tersedia</h5>
                        <div class="storage-text <?php echo $storage_percentage >= 90 ? 'danger' : ($storage_percentage >= 70 ? 'warning' : ''); ?>">
                            <i class="fas fa-server"></i>
                            <?php echo number_format($used_storage, 2); ?> MB / <?php echo $max_storage; ?> MB<br>
                            <small>(<?php echo number_format($storage_percentage, 1); ?>% digunakan)</small>
                        </div>
                        <?php if ($is_storage_full): ?>
                            <p class="text-danger mt-2">Penyimpanan penuh! Anda tidak dapat mengunggah lebih banyak file.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Upload dengan Drag-and-Drop -->
        <div class="row justify-content-center mb-5">
            <div class="col-lg">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h4 class="card-title fw-semibold mb-4"><i class="fas fa-upload me-2"></i> Unggah File</h4>
                        <form id="uploadForm" enctype="multipart/form-data" method="POST" action="upload.php">
                            <div class="mb-3 dropzone" id="dropzone">
                                <div class="dropzone-content text-center">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                                    <p class="text-muted">Seret dan lepas file di sini atau klik untuk memilih file</p>
                                    <input type="file" class="form-control d-none" id="fileInput" name="files[]" multiple accept=".zip,.7z,.jpg,.png,.mp4,.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.apk,.exe">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" <?php echo $is_storage_full ? 'disabled' : ''; ?>><i class="fas fa-cloud-upload-alt me-2"></i> Unggah</button>
                            <div class="progress mt-3" style="display: none;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 0%;" id="progressBar">0%</div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab untuk File Pribadi dan File Sharing -->
        <div class="row mb-4">
            <div class="col-12">
                <ul class="nav nav-tabs" id="fileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab" aria-controls="personal" aria-selected="true">File Pribadi</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="shared-tab" data-bs-toggle="tab" data-bs-target="#shared" type="button" role="tab" aria-controls="shared" aria-selected="false">File Sharing</button>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Konten Tab -->
        <div class="tab-content" id="fileTabContent">
            <!-- File Pribadi -->
            <div class="tab-pane fade show active" id="personal" role="tabpanel" aria-labelledby="personal-tab">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-semibold"><i class="fas fa-folder-open me-2"></i> Daftar File Pribadi</h4>
                    <div>
                        <button id="backupSelectedBtn" class="btn btn-warning me-2" style="display: none;"><i class="fas fa-download me-2"></i> Backup</button>
                        <button id="shareSelectedBtn" class="btn btn-info me-2" style="display: none;"><i class="fas fa-share-alt me-2"></i> Bagikan Terpilih</button>
                        <button id="deleteSelectedBtn" class="btn btn-danger" style="display: none;"><i class="fas fa-trash me-2"></i> Hapus Terpilih</button>
                    </div>
                </div>
                <?php if (empty($personal_files)): ?>
                    <div class="col-12 text-center">
                        <p class="text-muted">Belum ada file pribadi yang diunggah.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($personal_files as $file): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card shadow-sm border-0 file-card">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <input type="checkbox" class="file-checkbox me-2" value="<?php echo $file['id']; ?>">
                                            <?php if (in_array($file['file_type'], ['image/jpeg', 'image/png', 'video/mp4'])): ?>
                                                <a href="#" class="preview-link" data-bs-toggle="modal" data-bs-target="#previewModal" data-file-path="<?php echo htmlspecialchars($file['file_path']); ?>" data-file-type="<?php echo htmlspecialchars($file['file_type']); ?>">
                                                    <?php if (in_array($file['file_type'], ['image/jpeg', 'image/png'])): ?>
                                                        <img src="<?php echo htmlspecialchars($file['file_path']); ?>" alt="Thumbnail" class="file-preview me-2">
                                                    <?php else: ?>
                                                        <i class="fas <?php echo get_file_icon($file['file_type']); ?> fa-2x text-primary me-2"></i>
                                                    <?php endif; ?>
                                                </a>
                                            <?php else: ?>
                                                <i class="fas <?php echo get_file_icon($file['file_type']); ?> fa-2x text-primary me-2"></i>
                                            <?php endif; ?>
                                            <div class="flex-grow-1 text-truncate">
                                                <h6 class="card-title mb-0 text-truncate"><?php echo htmlspecialchars($file['file_name']); ?></h6>
                                                <small class="text-muted"><?php echo number_format($file['file_size'] / 1024, 2); ?> KB</small>
                                            </div>
                                        </div>
                                        <small class="text-muted d-block mb-2"><?php echo $file['uploaded_at']; ?></small>
                                        <div class="d-flex justify-content-end">
                                            <a href="download.php?id=<?php echo $file['id']; ?>" class="btn btn-sm btn-success me-1" title="Unduh"><i class="fas fa-download"></i></a>
                                            <button onclick="shareFile(<?php echo $file['id']; ?>)" class="btn btn-sm btn-info me-1" title="Bagikan"><i class="fas fa-share-alt"></i></button>
                                            <button onclick="renameFile(<?php echo $file['id']; ?>, '<?php echo htmlspecialchars($file['file_name']); ?>')" class="btn btn-sm btn-warning me-1" title="Ubah Nama"><i class="fas fa-edit"></i></button>
                                            <button onclick="deleteFile(<?php echo $file['id']; ?>)" class="btn btn-sm btn-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- File Sharing -->
            <div class="tab-pane fade" id="shared" role="tabpanel" aria-labelledby="shared-tab">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-semibold"><i class="fas fa-share-alt me-2"></i> Daftar File Sharing</h4>
                </div>
                <!-- File yang Dibagikan ke Anda -->
                <h5 class="fw-semibold mb-3">Diterima dari Pengguna Lain</h5>
                <?php if (empty($shared_files_received)): ?>
                    <div class="col-12 text-center">
                        <p class="text-muted">Belum ada file yang dibagikan kepada Anda.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($shared_files_received as $file): ?>
                            <?php if (isset($file['file_id']) && isset($file['file_path'])): ?>
                                <div class="col-lg-4 col-md-6 mb-4">
                                    <div class="card shadow-sm border-0 file-card">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center mb-2">
                                                <?php if (in_array($file['file_type'], ['image/jpeg', 'image/png', 'video/mp4'])): ?>
                                                    <a href="#" class="preview-link" data-bs-toggle="modal" data-bs-target="<?php echo $file['password'] ? '#passwordModal' : '#previewModal'; ?>" data-file-id="<?php echo $file['file_id']; ?>" data-file-path="<?php echo htmlspecialchars($file['file_path']); ?>" data-file-type="<?php echo htmlspecialchars($file['file_type']); ?>">
                                                        <?php if (in_array($file['file_type'], ['image/jpeg', 'image/png'])): ?>
                                                            <img src="<?php echo htmlspecialchars($file['file_path']); ?>" alt="Thumbnail" class="file-preview me-2">
                                                        <?php else: ?>
                                                            <i class="fas <?php echo get_file_icon($file['file_type']); ?> fa-2x text-primary me-2"></i>
                                                        <?php endif; ?>
                                                    </a>
                                                <?php else: ?>
                                                    <i class="fas <?php echo get_file_icon($file['file_type']); ?> fa-2x text-primary me-2"></i>
                                                <?php endif; ?>
                                                <div class="flex-grow-1 text-truncate">
                                                    <h6 class="card-title mb-0 text-truncate"><?php echo htmlspecialchars($file['file_name']); ?></h6>
                                                    <small class="text-muted"><?php echo number_format($file['file_size'] / 1024, 2); ?> KB | Dibagikan oleh: <?php echo htmlspecialchars($file['from_username']); ?> pada <?php echo $file['shared_at']; ?></small>
                                                    <?php if ($file['note']): ?>
                                                        <small class="text-muted d-block">Catatan: <?php echo htmlspecialchars($file['note']); ?></small>
                                                    <?php endif; ?>
                                                    <?php if ($file['password']): ?>
                                                        <small class="text-muted d-block"><i class="fas fa-lock me-1"></i> Dilindungi dengan password</small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <small class="text-muted d-block mb-2"><?php echo $file['uploaded_at']; ?> (Upload)</small>
                                            <div class="d-flex justify-content-end">
                                                <a href="#" class="btn btn-sm btn-success me-1 download-link" data-file-id="<?php echo $file['file_id']; ?>" data-password="<?php echo $file['password'] ? 'true' : 'false'; ?>" data-file-name="<?php echo htmlspecialchars($file['file_name']); ?>" title="Unduh"><i class="fas fa-download"></i></a>
                                                <button onclick="deleteShare(<?php echo $file['share_id']; ?>, '<?php echo htmlspecialchars($file['file_name']); ?>')" class="btn btn-sm btn-danger" title="Hapus Sharing"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="col-12 text-center">
                                    <p class="text-danger">Error: Data file sharing tidak valid untuk file ID: <?php echo htmlspecialchars($file['file_id'] ?? 'tidak diketahui'); ?>.</p>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- File yang Anda Bagikan -->
                <h5 class="fw-semibold mb-3 mt-5">Dibagikan oleh Anda</h5>
                <?php if (empty($shared_files_sent)): ?>
                    <div class="col-12 text-center">
                        <p class="text-muted">Belum ada file yang Anda bagikan.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($shared_files_sent as $file): ?>
                            <?php if (isset($file['file_id']) && isset($file['file_path'])): ?>
                                <div class="col-lg-4 col-md-6 mb-4">
                                    <div class="card shadow-sm border-0 file-card">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center mb-2">
                                                <?php if (in_array($file['file_type'], ['image/jpeg', 'image/png', 'video/mp4'])): ?>
                                                    <a href="#" class="preview-link" data-bs-toggle="modal" data-bs-target="#previewModal" data-file-path="<?php echo htmlspecialchars($file['file_path']); ?>" data-file-type="<?php echo htmlspecialchars($file['file_type']); ?>">
                                                        <?php if (in_array($file['file_type'], ['image/jpeg', 'image/png'])): ?>
                                                            <img src="<?php echo htmlspecialchars($file['file_path']); ?>" alt="Thumbnail" class="file-preview me-2">
                                                        <?php else: ?>
                                                            <i class="fas <?php echo get_file_icon($file['file_type']); ?> fa-2x text-primary me-2"></i>
                                                        <?php endif; ?>
                                                    </a>
                                                <?php else: ?>
                                                    <i class="fas <?php echo get_file_icon($file['file_type']); ?> fa-2x text-primary me-2"></i>
                                                <?php endif; ?>
                                                <div class="flex-grow-1 text-truncate">
                                                    <h6 class="card-title mb-0 text-truncate"><?php echo htmlspecialchars($file['file_name']); ?></h6>
                                                    <small class="text-muted"><?php echo number_format($file['file_size'] / 1024, 2); ?> KB | Dibagikan ke: <?php echo htmlspecialchars($file['to_username']); ?> pada <?php echo $file['shared_at']; ?></small>
                                                    <?php if ($file['note']): ?>
                                                        <small class="text-muted d-block">Catatan: <?php echo htmlspecialchars($file['note']); ?></small>
                                                    <?php endif; ?>
                                                    <?php if ($file['password']): ?>
                                                        <small class="text-muted d-block"><i class="fas fa-lock me-1"></i> Dilindungi dengan password</small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <small class="text-muted d-block mb-2"><?php echo $file['uploaded_at']; ?> (Upload)</small>
                                            <div class="d-flex justify-content-end">
                                                <a href="download.php?id=<?php echo $file['file_id']; ?>" class="btn btn-sm btn-success me-1" title="Unduh"><i class="fas fa-download"></i></a>
                                                <button onclick="deleteShare(<?php echo $file['share_id']; ?>, '<?php echo htmlspecialchars($file['file_name']); ?>')" class="btn btn-sm btn-danger" title="Hapus Sharing"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="col-12 text-center">
                                    <p class="text-danger">Error: Data file sharing tidak valid untuk file ID: <?php echo htmlspecialchars($file['file_id'] ?? 'tidak diketahui'); ?>.</p>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal untuk Preview -->
    <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="previewModalLabel">Preview File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="previewContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal untuk Berbagi File -->
    <div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shareModalLabel">Bagikan File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="shareForm">
                        <div class="mb-3">
                            <label for="recipientSelect" class="form-label">Pilih Penerima</label>
                            <select class="form-select" id="recipientSelect" name="recipient_id" required>
                                <option value="">Pilih pengguna</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="sharePassword" class="form-label">Password (Opsional)</label>
                            <input type="password" class="form-control" id="sharePassword" name="password" placeholder="Masukkan password untuk file">
                        </div>
                        <div class="mb-3">
                            <label for="shareNote" class="form-label">Catatan (Opsional)</label>
                            <textarea class="form-control" id="shareNote" name="note" rows="4" placeholder="Tambahkan catatan untuk penerima"></textarea>
                        </div>
                        <input type="hidden" id="fileIds" name="file_ids">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="submitShare">Bagikan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal untuk Memasukkan Password -->
    <div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="passwordModalLabel">Masukkan Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="passwordForm">
                        <div class="mb-3">
                            <label for="filePassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="filePassword" name="password" required>
                        </div>
                        <input type="hidden" id="passwordFileId" name="file_id">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="submitPassword">Lanjutkan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal untuk Pengaturan -->
    <div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="settingsModalLabel">Pengaturan Akun</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="settingsForm" method="POST" action="update_settings.php">
                        <div class="mb-3">
                            <label for="newUsername" class="form-label">Nama Pengguna Baru</label>
                            <input type="text" class="form-control" id="newUsername" name="newUsername" value="<?php echo htmlspecialchars($_SESSION['username']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="currentEmail" class="form-label">Email Lama</label>
                            <input type="email" class="form-control" id="currentEmail" name="currentEmail" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="newEmail" class="form-label">Email Baru (Opsional)</label>
                            <input type="email" class="form-control" id="newEmail" name="newEmail" placeholder="Kosongkan jika tidak ingin mengubah">
                        </div>
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Password Lama</label>
                            <input type="password" class="form-control" id="currentPassword" name="currentPassword">
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">Password Baru (Opsional)</label>
                            <input type="password" class="form-control" id="newPassword" name="newPassword" placeholder="Kosongkan jika tidak ingin mengubah">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light text-center py-4 mt-5">
        <p class="text-muted mb-0">© <?php echo date('Y'); ?> Cloud Storage. All rights reserved.</p>
    </footer>

    <!-- Scripts -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        // Tampilkan alert jika penyimpanan penuh saat halaman dimuat
        <?php if ($is_storage_full): ?>
            Swal.fire({
                icon: 'warning',
                title: 'Penyimpanan Penuh',
                text: 'Anda telah mencapai batas penyimpanan 100MB. Hapus beberapa file untuk mengunggah yang baru.',
                confirmButtonText: 'OK'
            });
        <?php endif; ?>

        // Tambahkan logika untuk form pengaturan
        document.getElementById('settingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('update_settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: 'Pengaturan diperbarui! Anda akan logout untuk menerapkan perubahan.',
                        timer: 2000,
                        timerProgressBar: true,
                        willClose: () => {
                            window.location.href = 'logout.php';
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Terjadi kesalahan saat memperbarui pengaturan.',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Terjadi kesalahan jaringan.',
                    confirmButtonText: 'OK'
                });
            });
        });
    </script>
</body>
</html>