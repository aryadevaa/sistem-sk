<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check login
requireLogin();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Ambil ID dari URL
$no_reg = isset($_GET['id']) ? clean($_GET['id']) : '';
if (empty($no_reg)) {
    header("Location: data_sk.php");
    exit;
}

// Ambil data SK
$sk_data = getSKData($no_reg, $conn);
if (!$sk_data) {
    header("Location: data_sk.php?error=notfound");
    exit;
}

// Cek permission: user hanya bisa edit SK miliknya, admin bisa edit semua
if ($role !== 'admin' && $sk_data['created_by'] != $user_id) {
    header("Location: data_sk.php?error=forbidden");
    exit;
}

// User tidak bisa edit SK yang sudah disetujui
if ($sk_data['status'] === 'Disetujui' && $role !== 'admin') {
    header("Location: view_sk.php?id=$no_reg&error=approved");
    exit;
}

$error = '';
$success = '';

// Proses form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_sk = clean($_POST['no_sk']);
    $hal = clean($_POST['hal']);
    $tgl = clean($_POST['tgl']);
    
    $filename = $sk_data['file']; // Keep old file by default
    
    // Cek apakah ada file baru diupload
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadPDF($_FILES['file'], $no_reg);
        
        if ($upload_result['success']) {
            // Hapus file lama
            if (!empty($sk_data['file'])) {
                deletePDF($sk_data['file']);
            }
            $filename = $upload_result['filename'];
        } else {
            $error = $upload_result['message'];
        }
    }
    
    if (empty($error)) {
        // Update database
        $query = "UPDATE surat_keputusan 
                  SET no_sk='$no_sk', hal='$hal', tgl='$tgl', file='$filename', 
                      status='Draft', updated_at=NOW() 
                  WHERE no_reg='$no_reg'";
        
        if (mysqli_query($conn, $query)) {
            header("Location: data_sk.php?success=updated");
            exit;
        } else {
            $error = 'Gagal mengupdate data: ' . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Surat Keputusan - <?php echo $sk_data['no_reg']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-wrapper">
        <!-- Header -->
         <div class="header">
            <div class="header-content">
                <div class="header-left">
                    <a href="data_sk.php" class="btn-back">
                        ← Kembali
                    </a>
                    <h1>Edit Surat Keputusan</h1>
                </div>
                <div class="header-actions">
                    <div class="date-badge">📅 <?php echo formatTanggal(date('Y-m-d')); ?></div>
                    <button class="btn-logout" onclick="logout()">Logout</button>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error">
            ⚠️ <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <!-- Form Content -->
        <div class="content-card">
            <div class="card-header">
                <div class="card-title">✏️ Edit Data Surat Keputusan</div>
            </div>

            <div style="background: #f7fafc; padding: 15px; border-radius: 8px; margin-bottom: 25px;">
                <div style="display: flex; gap: 10px; align-items: center; color: #4a5568;">
                    <span style="font-size: 24px;">ℹ️</span>
                    <div>
                        <strong>No Register:</strong> <?php echo $sk_data['no_reg']; ?><br>
                        <strong>Status:</strong> 
                        <span class="status-badge status-<?php echo strtolower($sk_data['status']); ?>">
                            <?php echo $sk_data['status']; ?>
                        </span>
                    </div>
                </div>
            </div>

            <form method="POST" action="" enctype="multipart/form-data" onsubmit="return validateForm()">
                <div class="form-group">
                    <label class="form-label">Nomor Surat Keputusan <span style="color: #e53e3e;">*</span></label>
                    <input type="text" name="no_sk" class="form-input" 
                           placeholder="Contoh: 001/SK/X/2025" required
                           value="<?php echo htmlspecialchars($sk_data['no_sk']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Perihal / Hal <span style="color: #e53e3e;">*</span></label>
                    <textarea name="hal" class="form-textarea" rows="4" 
                              placeholder="Masukkan perihal surat keputusan" required><?php echo htmlspecialchars($sk_data['hal']); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Tanggal Surat Keputusan <span style="color: #e53e3e;">*</span></label>
                    <input type="date" name="tgl" class="form-input" required
                           value="<?php echo $sk_data['tgl']; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">File PDF</label>
                    <div style="background: #f7fafc; padding: 12px; border-radius: 8px; margin-bottom: 10px;">
                        <small style="color: #4a5568;">
                            📄 File saat ini: <strong><?php echo $sk_data['file']; ?></strong>
                            <?php if (!empty($sk_data['file'])): ?>
                            <a href="<?php echo UPLOAD_URL . $sk_data['file']; ?>" target="_blank" 
                               style="color: #667eea; margin-left: 10px;">Lihat File</a>
                            <?php endif; ?>
                        </small>
                    </div>
                    <input type="file" name="file" id="file" class="form-input" 
                           accept=".pdf" onchange="previewFile(this, 'preview')">
                    <small style="color: #718096; font-size: 13px;">Biarkan kosong jika tidak ingin mengubah file. Format: PDF, Maksimal 5MB</small>
                    <div id="preview" style="margin-top: 15px;"></div>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 30px;">
                    <button type="submit" class="btn-add" style="flex: 1;">
                        💾 Simpan Perubahan
                    </button>
                    <a href="data_sk.php" class="btn-back" style="flex: 0; padding: 12px 24px; text-decoration: none;">
                        ✕ Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function validateForm() {
            const noSk = document.querySelector('[name="no_sk"]').value;
            const hal = document.querySelector('[name="hal"]').value;
            const tgl = document.querySelector('[name="tgl"]').value;
            const file = document.querySelector('[name="file"]').files[0];

            if (!noSk || !hal || !tgl) {
                alert('Semua field harus diisi!');
                return false;
            }

            if (file) {
                if (file.type !== 'application/pdf') {
                    alert('Hanya file PDF yang diizinkan!');
                    return false;
                }

                if (file.size > 5000000) {
                    alert('Ukuran file maksimal 5MB!');
                    return false;
                }
            }

            // Show loading
            showLoading();
            return true;
        }

        function logout() {
            if (confirm('Apakah Anda yakin ingin logout?')) {
                window.location.href = '../auth/logout.php';
            }
        }
    </script>
</body>
</html>