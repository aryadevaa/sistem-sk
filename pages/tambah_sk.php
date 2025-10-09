<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check login
requireLogin();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

$error = '';
$success = '';

// Proses form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_sk = clean($_POST['no_sk']);
    $hal = clean($_POST['hal']);
    $tgl = clean($_POST['tgl']);
    
    // Generate no_reg otomatis
    $no_reg = generateNoRegister($conn);
    
    // Hitung tanggal expired otomatis (5 bulan dari tanggal SK)
    $tanggal_expired = calculateExpiredDate($tgl);
    
    // Upload file PDF
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadPDF($_FILES['file'], $no_reg);
        
        if ($upload_result['success']) {
            $filename = $upload_result['filename'];
            
            // Insert ke database
            $query = "INSERT INTO surat_keputusan (no_reg, no_sk, hal, tgl, tanggal_expired, file, status, created_by, created_at, updated_at) 
                      VALUES ('$no_reg', '$no_sk', '$hal', '$tgl', '$tanggal_expired', '$filename', 'Draft', '$user_id', NOW(), NOW())";
            
            if (mysqli_query($conn, $query)) {
                header("Location: data_sk.php?success=created");
                exit;
            } else {
                $error = 'Gagal menyimpan data: ' . mysqli_error($conn);
                // Hapus file yang sudah diupload
                deletePDF($filename);
            }
        } else {
            $error = $upload_result['message'];
        }
    } else {
        $error = 'File PDF harus diupload!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Surat Keputusan - Sistem Informasi SK</title>
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
                    <h1>Tambah Surat Keputusan</h1>
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
                <div class="card-title">📝 Form Surat Keputusan Baru</div>
            </div>

            <form method="POST" action="" enctype="multipart/form-data" onsubmit="return validateForm()">
                <div class="form-group">
                    <label class="form-label">Nomor Surat Keputusan <span style="color: #e53e3e;">*</span></label>
                    <input type="text" name="no_sk" class="form-input" 
                           placeholder="Contoh: 001/SK/X/2025" required
                           value="<?php echo isset($_POST['no_sk']) ? htmlspecialchars($_POST['no_sk']) : ''; ?>">
                    <small style="color: #718096; font-size: 13px;">Format: [nomor]/SK/[bulan]/[tahun]</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Perihal / Hal <span style="color: #e53e3e;">*</span></label>
                    <textarea name="hal" class="form-textarea" rows="4" 
                              placeholder="Masukkan perihal surat keputusan" required><?php echo isset($_POST['hal']) ? htmlspecialchars($_POST['hal']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Tanggal Surat Keputusan <span style="color: #e53e3e;">*</span></label>
                    <input type="date" name="tgl" class="form-input" required
                           value="<?php echo isset($_POST['tgl']) ? $_POST['tgl'] : date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">File PDF <span style="color: #e53e3e;">*</span></label>
                    <input type="file" name="file" id="file" class="form-input" 
                           accept=".pdf" required onchange="previewFile(this, 'preview')">
                    <small style="color: #718096; font-size: 13px;">Format: PDF, Maksimal 5MB</small>
                    <div id="preview" style="margin-top: 15px;"></div>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 30px;">
                    <button type="submit" class="btn-add" style="flex: 1;">
                        💾 Simpan Surat Keputusan
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

            if (!file) {
                alert('File PDF harus diupload!');
                return false;
            }

            if (file.type !== 'application/pdf') {
                alert('Hanya file PDF yang diizinkan!');
                return false;
            }

            if (file.size > 5000000) {
                alert('Ukuran file maksimal 5MB!');
                return false;
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