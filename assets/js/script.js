// Konfigurasi SweetAlert2 untuk tema modern
const swalConfig = {
    customClass: {
        popup: 'swal-modern',
        title: 'swal-title',
        content: 'swal-content',
        confirmButton: 'btn btn-primary',
        cancelButton: 'btn btn-secondary'
    },
    buttonsStyling: false
};

// Drag-and-Drop
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');

dropzone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropzone.classList.add('dragover');
});

dropzone.addEventListener('dragleave', () => {
    dropzone.classList.remove('dragover');
});

dropzone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzone.classList.remove('dragover');
    fileInput.files = e.dataTransfer.files;
    document.getElementById('uploadForm').dispatchEvent(new Event('submit'));
});

dropzone.addEventListener('click', () => {
    fileInput.click();
});

fileInput.addEventListener('change', () => {
    if (fileInput.files.length > 0) {
        document.getElementById('uploadForm').dispatchEvent(new Event('submit'));
    }
});

document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    let formData = new FormData(this);
    let progressBar = document.querySelector('.progress');
    let progressBarInner = document.getElementById('progressBar');

    progressBar.style.display = 'block';
    progressBarInner.style.width = '0%';
    progressBarInner.textContent = '0%';

    let xhr = new XMLHttpRequest();
    xhr.open('POST', 'upload.php', true);

    // Hitung total ukuran file untuk progress bar
    let files = fileInput.files;
    let totalSize = 0;
    for (let i = 0; i < files.length; i++) {
        totalSize += files[i].size;
    }

    let loaded = 0;
    xhr.upload.onprogress = function(e) {
        if (e.lengthComputable) {
            loaded = e.loaded;
            let percent = (loaded / totalSize) * 100;
            progressBarInner.style.width = percent + '%';
            progressBarInner.textContent = Math.round(percent) + '%';
        }
    };

    xhr.onload = function() {
        try {
            let response = JSON.parse(xhr.responseText);
            progressBar.style.display = 'none';
            console.log('Upload response:', response);
            Swal.fire({
                ...swalConfig,
                icon: response.status,
                title: response.status === 'success' ? 'Berhasil' : 'Gagal',
                html: response.message
            }).then(() => {
                if (response.status === 'success') {
                    console.log('Reloading page after upload');
                    location.reload();
                } else if (response.message.includes('Penyimpanan penuh')) {
                    console.log('Storage full alert triggered');
                    Swal.fire({
                        ...swalConfig,
                        icon: 'warning',
                        title: 'Penyimpanan Penuh',
                        text: 'Anda telah mencapai batas penyimpanan 100MB. Hapus beberapa file untuk mengunggah yang baru.',
                        confirmButtonText: 'OK'
                    });
                }
            });
        } catch (e) {
            console.error('Error parsing upload response:', e, 'Raw response:', xhr.responseText);
            Swal.fire({
                ...swalConfig,
                icon: 'error',
                title: 'Gagal',
                text: 'Terjadi kesalahan saat memproses respons server.'
            });
        }
    };

    xhr.onerror = function() {
        console.error('Upload request failed');
        Swal.fire({
            ...swalConfig,
            icon: 'error',
            title: 'Gagal',
            text: 'Gagal mengunggah file. Silakan coba lagi.'
        });
    };

    xhr.send(formData);
});

function deleteFile(fileId) {
    Swal.fire({
        ...swalConfig,
        title: 'Apakah Anda yakin?',
        text: 'File akan dihapus permanen!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + fileId
            })
            .then(response => response.text())
            .then(text => {
                console.log('Delete response text:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Delete parsed response:', data);
                    Swal.fire({
                        ...swalConfig,
                        icon: data.status,
                        title: data.status === 'success' ? 'Berhasil' : 'Gagal',
                        text: data.message
                    }).then(() => {
                        if (data.status === 'success') {
                            console.log('Reloading page after delete');
                            location.reload();
                        }
                    });
                } catch (e) {
                    console.error('Error parsing delete response:', e, 'Raw response:', text);
                    Swal.fire({
                        ...swalConfig,
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Terjadi kesalahan saat memproses respons server.'
                    });
                }
            })
            .catch(error => {
                console.error('Error during delete:', error);
                Swal.fire({
                    ...swalConfig,
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Terjadi kesalahan saat menghapus file.'
                });
            });
        }
    });
}

function deleteShare(shareId, fileName) {
    Swal.fire({
        ...swalConfig,
        title: 'Apakah Anda yakin?',
        text: `File sharing untuk "${fileName}" akan dihapus!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('delete_share.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'share_id=' + encodeURIComponent(shareId)
            })
            .then(response => response.text())
            .then(text => {
                console.log('Delete share response text:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Delete share parsed response:', data);
                    Swal.fire({
                        ...swalConfig,
                        icon: data.status,
                        title: data.status === 'success' ? 'Berhasil' : 'Gagal',
                        text: data.message
                    }).then(() => {
                        if (data.status === 'success') {
                            console.log('Reloading page after delete share');
                            location.reload();
                        }
                    });
                } catch (e) {
                    console.error('Error parsing delete share response:', e, 'Raw response:', text);
                    Swal.fire({
                        ...swalConfig,
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Terjadi kesalahan saat memproses respons server.'
                    });
                }
            })
            .catch(error => {
                console.error('Error during delete share:', error);
                Swal.fire({
                    ...swalConfig,
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Terjadi kesalahan saat menghapus file sharing.'
                });
            });
        }
    });
}

// Penghapusan Masal
const checkboxes = document.querySelectorAll('.file-checkbox');
const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
const shareSelectedBtn = document.getElementById('shareSelectedBtn');
const backupSelectedBtn = document.getElementById('backupSelectedBtn');

checkboxes.forEach(checkbox => {
    checkbox.addEventListener('change', () => {
        const checkedCount = document.querySelectorAll('.file-checkbox:checked').length;
        deleteSelectedBtn.style.display = checkedCount > 0 ? 'inline-block' : 'none';
        shareSelectedBtn.style.display = checkedCount > 0 ? 'inline-block' : 'none';
        backupSelectedBtn.style.display = checkedCount > 0 ? 'inline-block' : 'none';
    });
});

deleteSelectedBtn.addEventListener('click', () => {
    const selectedIds = Array.from(document.querySelectorAll('.file-checkbox:checked')).map(cb => cb.value);
    if (selectedIds.length === 0) {
        Swal.fire({
            ...swalConfig,
            icon: 'warning',
            title: 'Peringatan',
            text: 'Pilih setidaknya satu file untuk dihapus.'
        });
        return;
    }

    Swal.fire({
        ...swalConfig,
        title: 'Apakah Anda yakin?',
        text: `Anda akan menghapus ${selectedIds.length} file secara permanen!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'ids=' + encodeURIComponent(selectedIds.join(','))
            })
            .then(response => response.text())
            .then(text => {
                console.log('Bulk delete response text:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Bulk delete parsed response:', data);
                    Swal.fire({
                        ...swalConfig,
                        icon: data.status,
                        title: data.status === 'success' ? 'Berhasil' : 'Gagal',
                        text: data.message
                    }).then(() => {
                        if (data.status === 'success') {
                            console.log('Reloading page after bulk delete');
                            location.reload();
                        }
                    });
                } catch (e) {
                    console.error('Error parsing bulk delete response:', e, 'Raw response:', text);
                    Swal.fire({
                        ...swalConfig,
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Terjadi kesalahan saat memproses respons server.'
                    });
                }
            })
            .catch(error => {
                console.error('Error during bulk delete:', error);
                Swal.fire({
                    ...swalConfig,
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Terjadi kesalahan saat menghapus file.'
                });
            });
        }
    });
});

function renameFile(fileId, currentName) {
    // Pisahkan nama file dan ekstensi
    const lastDotIndex = currentName.lastIndexOf('.');
    const fileNameWithoutExt = lastDotIndex !== -1 ? currentName.substring(0, lastDotIndex) : currentName;
    const fileExt = lastDotIndex !== -1 ? currentName.substring(lastDotIndex) : '';

    Swal.fire({
        ...swalConfig,
        title: 'Ubah Nama File',
        input: 'text',
        inputValue: fileNameWithoutExt,
        showCancelButton: true,
        confirmButtonText: 'Simpan',
        cancelButtonText: 'Batal',
        inputValidator: (value) => {
            if (!value) {
                return 'Nama file tidak boleh kosong!';
            }
            if (value.includes('.')) {
                return 'Nama file tidak boleh mengandung titik!';
            }
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            fetch('rename.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + fileId + '&new_name=' + encodeURIComponent(result.value) + '&ext=' + encodeURIComponent(fileExt)
            })
            .then(response => response.text())
            .then(text => {
                console.log('Rename response text:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Rename parsed response:', data);
                    Swal.fire({
                        ...swalConfig,
                        icon: data.status,
                        title: data.status === 'success' ? 'Berhasil' : 'Gagal',
                        text: data.message
                    }).then(() => {
                        if (data.status === 'success') {
                            console.log('Reloading page after rename');
                            location.reload();
                        }
                    });
                } catch (e) {
                    console.error('Error parsing rename response:', e, 'Raw response:', text);
                    Swal.fire({
                        ...swalConfig,
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Terjadi kesalahan saat memproses respons server.'
                    });
                }
            })
            .catch(error => {
                console.error('Error during rename:', error);
                Swal.fire({
                    ...swalConfig,
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Terjadi kesalahan saat mengubah nama file.'
                });
            });
        }
    });
}

// Preview File
const previewLinks = document.querySelectorAll('.preview-link');
const previewModal = document.getElementById('previewModal');
const previewContent = document.getElementById('previewContent');
let currentFilePath = '';
let currentFileType = '';

previewLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const fileId = link.getAttribute('data-file-id');
        const filePath = link.getAttribute('data-file-path');
        const fileType = link.getAttribute('data-file-type');

        if (link.getAttribute('data-bs-target') === '#passwordModal') {
            document.getElementById('passwordFileId').value = fileId;
            currentFilePath = filePath;
            currentFileType = fileType;
            document.getElementById('passwordForm').setAttribute('data-action', 'preview');
        } else {
            if (fileType === 'image/jpeg' || fileType === 'image/png') {
                previewContent.innerHTML = `<img src="${filePath}" alt="Preview" style="max-width: 100%; max-height: 500px; object-fit: contain;">`;
            } else if (fileType === 'video/mp4') {
                previewContent.innerHTML = `
                    <video controls style="max-width: 100%; max-height: 500px;">
                        <source src="${filePath}" type="video/mp4">
                        Browser Anda tidak mendukung pemutaran video.
                    </video>`;
            }
        }
    });
});

// Bersihkan konten preview saat modal ditutup
previewModal.addEventListener('hidden.bs.modal', () => {
    previewContent.innerHTML = '';
});

// Berbagi File
function shareFile(fileId) {
    document.getElementById('fileIds').value = fileId;
    document.getElementById('shareModalLabel').textContent = 'Bagikan File';
    document.getElementById('sharePassword').value = '';
    document.getElementById('shareNote').value = '';
    new bootstrap.Modal(document.getElementById('shareModal')).show();
}

shareSelectedBtn.addEventListener('click', () => {
    const selectedIds = Array.from(document.querySelectorAll('.file-checkbox:checked')).map(cb => cb.value);
    if (selectedIds.length === 0) {
        Swal.fire({
            ...swalConfig,
            icon: 'warning',
            title: 'Peringatan',
            text: 'Pilih setidaknya satu file untuk dibagikan.'
        });
        return;
    }

    document.getElementById('fileIds').value = selectedIds.join(',');
    document.getElementById('shareModalLabel').textContent = `Bagikan ${selectedIds.length} File`;
    document.getElementById('sharePassword').value = '';
    document.getElementById('shareNote').value = '';
    new bootstrap.Modal(document.getElementById('shareModal')).show();
});

document.getElementById('submitShare').addEventListener('click', () => {
    const form = document.getElementById('shareForm');
    const formData = new FormData(form);

    fetch('share.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        console.log('Share response text:', text);
        try {
            const data = JSON.parse(text);
            console.log('Share parsed response:', data);
            Swal.fire({
                ...swalConfig,
                icon: data.status,
                title: data.status === 'success' ? 'Berhasil' : 'Gagal',
                html: data.message
            }).then(() => {
                if (data.status === 'success') {
                    console.log('Reloading page after share');
                    location.reload();
                }
            });
            bootstrap.Modal.getInstance(document.getElementById('shareModal')).hide();
        } catch (e) {
            console.error('Error parsing share response:', e, 'Raw response:', text);
            Swal.fire({
                ...swalConfig,
                icon: 'error',
                title: 'Gagal',
                text: 'Terjadi kesalahan saat memproses respons server.'
            });
        }
    })
    .catch(error => {
        console.error('Error during share:', error);
        Swal.fire({
            ...swalConfig,
            icon: 'error',
            title: 'Gagal',
            text: 'Terjadi kesalahan saat membagikan file.'
        });
    });
});

// Validasi Password untuk File Sharing
document.getElementById('submitPassword').addEventListener('click', () => {
    const form = document.getElementById('passwordForm');
    const formData = new FormData(form);
    const fileId = formData.get('file_id');
    const action = form.getAttribute('data-action');
    const fileName = form.getAttribute('data-file-name') || `file_${fileId}`; // Ambil nama file dari atribut

    if (action === 'download') {
        fetch('download.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Download response headers:', response.headers.get('content-type'));
            if (response.headers.get('content-type').includes('application/json')) {
                return response.json().then(data => {
                    console.log('Download JSON response:', data);
                    return { status: data.status, message: data.message, blob: null };
                });
            } else {
                return response.blob().then(blob => {
                    console.log('Download file received, size:', blob.size);
                    return { status: 'success', message: 'File diunduh', blob: blob, contentType: response.headers.get('content-type'), fileName: response.headers.get('content-disposition')?.match(/filename="(.+)"/)?.[1] || fileName };
                });
            }
        })
        .then(data => {
            console.log('Download processed response:', data);
            if (data.status === 'success' && data.blob) {
                const url = window.URL.createObjectURL(data.blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = data.fileName; // Gunakan nama file dari header atau fallback
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
                Swal.fire({
                    ...swalConfig,
                    icon: 'success',
                    title: 'Berhasil',
                    text: data.message
                }).then(() => {
                    bootstrap.Modal.getInstance(document.getElementById('passwordModal')).hide();
                });
            } else {
                Swal.fire({
                    ...swalConfig,
                    icon: data.status,
                    title: data.status === 'success' ? 'Berhasil' : 'Gagal',
                    text: data.message
                });
            }
        })
        .catch(error => {
            console.error('Error during download:', error);
            Swal.fire({
                ...swalConfig,
                icon: 'error',
                title: 'Gagal',
                text: 'Terjadi kesalahan saat mengunduh file.'
            });
        });
    } else if (action === 'preview') {
        fetch('verify_password.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Verify password response:', data);
            if (data.status === 'success') {
                bootstrap.Modal.getInstance(document.getElementById('passwordModal')).hide();
                const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
                if (currentFileType === 'image/jpeg' || currentFileType === 'image/png') {
                    previewContent.innerHTML = `<img src="${currentFilePath}" alt="Preview" style="max-width: 100%; max-height: 500px; object-fit: contain;">`;
                } else if (currentFileType === 'video/mp4') {
                    previewContent.innerHTML = `
                        <video controls style="max-width: 100%; max-height: 500px;">
                            <source src="${currentFilePath}" type="video/mp4">
                            Browser Anda tidak mendukung pemutaran video.
                        </video>`;
                }
                previewModal.show();
            } else {
                Swal.fire({
                    ...swalConfig,
                    icon: 'error',
                    title: 'Gagal',
                    text: data.message
                });
            }
        })
        .catch(error => {
            console.error('Error during password verification:', error);
            Swal.fire({
                ...swalConfig,
                icon: 'error',
                title: 'Gagal',
                text: 'Terjadi kesalahan saat memverifikasi password.'
            });
        });
    }
});

// Download File dengan Password
const downloadLinks = document.querySelectorAll('.download-link');
downloadLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const fileId = link.getAttribute('data-file-id');
        const hasPassword = link.getAttribute('data-password') === 'true';
        const fileName = link.getAttribute('data-file-name') || `file_${fileId}`;

        if (hasPassword) {
            document.getElementById('passwordFileId').value = fileId;
            document.getElementById('passwordForm').setAttribute('data-action', 'download');
            document.getElementById('passwordForm').setAttribute('data-file-name', fileName); // Simpan nama file
            new bootstrap.Modal(document.getElementById('passwordModal')).show();
        } else {
            window.location.href = `download.php?id=${fileId}`;
        }
    });
});

backupSelectedBtn.addEventListener('click', () => {
    const selectedIds = Array.from(document.querySelectorAll('.file-checkbox:checked')).map(cb => cb.value);
    if (selectedIds.length === 0) {
        Swal.fire({
            ...swalConfig,
            icon: 'warning',
            title: 'Peringatan',
            text: 'Pilih setidaknya satu file untuk dibackup.'
        });
        return;
    }

    Swal.fire({
        ...swalConfig,
        title: 'Apakah Anda yakin?',
        text: `Anda akan membuat backup untuk ${selectedIds.length} file dalam format ZIP.`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Backup',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('backup.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'file_ids=' + encodeURIComponent(selectedIds.join(','))
            })
            .then(response => {
                if (response.ok) {
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/zip')) {
                        return response.blob().then(blob => {
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = response.headers.get('content-disposition')?.match(/filename="(.+)"/)?.[1] || 'backup.zip';
                            document.body.appendChild(a);
                            a.click();
                            a.remove();
                            window.URL.revokeObjectURL(url);
                            Swal.fire({
                                ...swalConfig,
                                icon: 'success',
                                title: 'Berhasil',
                                text: 'Backup telah diunduh.'
                            });
                        });
                    } else {
                        return response.json().then(data => {
                            throw new Error(data.message || 'Gagal memproses backup.');
                        });
                    }
                } else {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Gagal membuat backup.');
                    });
                }
            })
            .catch(error => {
                console.error('Error during backup:', error);
                Swal.fire({
                    ...swalConfig,
                    icon: 'error',
                    title: 'Gagal',
                    text: error.message || 'Terjadi kesalahan saat membuat backup.'
                });
            });
        }
    });
});