<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check login
requireLogin();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Ambil data user lengkap
$user_data = getUserData($user_id, $conn);

$error = '';
$success = '';

// Proses update foto profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_photo'])) {
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        $file_type = $_FILES['profile_photo']['type'];
        $file_size = $_FILES['profile_photo']['size'];
        
        if (!in_array($file_type, $allowed_types)) {
            $error = 'Hanya file JPG, JPEG, dan PNG yang diizinkan!';
        } else if ($file_size > $max_size) {
            $error = 'Ukuran file maksimal 2MB!';
        } else {
            // Generate filename unik
            $extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
            $upload_path = dirname(__DIR__) . '/uploads/profiles/';
            
            // Buat folder jika belum ada
            if (!file_exists($upload_path)) {
                mkdir($upload_path, 0777, true);
            }
            
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path . $filename)) {
                // Hapus foto lama jika ada
                if (!empty($user_data['profile_photo']) && file_exists($upload_path . $user_data['profile_photo'])) {
                    unlink($upload_path . $user_data['profile_photo']);
                }
                
                // Update database
                $filename_escaped = mysqli_real_escape_string($conn, $filename);
                $query = "UPDATE users SET profile_photo='$filename_escaped' WHERE id='$user_id'";
                if (mysqli_query($conn, $query)) {
                    $success = 'Foto profil berhasil diupdate!';
                    $user_data = getUserData($user_id, $conn);
                } else {
                    $error = 'Gagal menyimpan ke database!';
                }
            } else {
                $error = 'Gagal mengupload file!';
            }
        }
    } else {
        $error = 'Silakan pilih foto terlebih dahulu!';
    }
}

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_username = clean($_POST['username']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi
    if (strlen($new_username) < 4) {
        $error = 'Username minimal 4 karakter!';
    } else {
        // Cek apakah username sudah digunakan oleh user lain
        $check_query = "SELECT id FROM users WHERE username = '$new_username' AND id != '$user_id'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Username sudah digunakan oleh user lain!';
        } else {
            if (!empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    $error = 'Password baru dan konfirmasi password tidak cocok!';
                } else if (strlen($new_password) < 6) {
                    $error = 'Password minimal 6 karakter!';
                } else {
                    // Update dengan password baru
                    $password_hash = md5($new_password);
                    $query = "UPDATE users SET username='$new_username', password='$password_hash' WHERE id='$user_id'";
                }
            } else {
                // Update tanpa password
                $query = "UPDATE users SET username='$new_username' WHERE id='$user_id'";
            }
            
            if (empty($error)) {
                if (mysqli_query($conn, $query)) {
                    $_SESSION['username'] = $new_username;
                    $success = 'Profil berhasil diupdate!';
                    $user_data = getUserData($user_id, $conn); // Refresh data
                } else {
                    $error = 'Gagal mengupdate profil: ' . mysqli_error($conn);
                }
            }
        }
    }
}

// Hitung statistik user
$stats_query = "SELECT 
    COUNT(*) as total_sk,
    SUM(CASE WHEN status='Draft' THEN 1 ELSE 0 END) as draft,
    SUM(CASE WHEN status='Revisi' THEN 1 ELSE 0 END) as revisi,
    SUM(CASE WHEN status='Disetujui' THEN 1 ELSE 0 END) as disetujui
    FROM surat_keputusan WHERE created_by='$user_id'";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Sistem Informasi SK</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 25px;
            color: white;
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.2);
            border: 5px solid white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: 700;
            color: white;
            backdrop-filter: blur(10px);
        }

        .profile-info h2 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .profile-role {
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 16px;
            border-radius: 20px;
            display: inline-block;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .profile-email {
            opacity: 0.9;
            font-size: 15px;
        }

        .stats-mini {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-mini-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-left: 4px solid;
        }

        .stat-mini-card.blue { border-left-color: #4299e1; }
        .stat-mini-card.yellow { border-left-color: #ed8936; }
        .stat-mini-card.red { border-left-color: #f56565; }
        .stat-mini-card.green { border-left-color: #48bb78; }

        .stat-mini-number {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .stat-mini-card.blue .stat-mini-number { color: #4299e1; }
        .stat-mini-card.yellow .stat-mini-number { color: #ed8936; }
        .stat-mini-card.red .stat-mini-number { color: #f56565; }
        .stat-mini-card.green .stat-mini-number { color: #48bb78; }

        .stat-mini-label {
            color: #718096;
            font-size: 13px;
            font-weight: 600;
        }

        .form-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-section-full {
            grid-column: 1 / -1;
        }

        .profile-avatar-wrapper {
            position: relative;
            display: inline-block;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.2);
            border: 5px solid white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: 700;
            color: white;
            backdrop-filter: blur(10px);
        }

        .profile-avatar-img {
            width: 120px;
            height: 120px;
            border: 5px solid white;
            border-radius: 50%;
            object-fit: cover;
            display: block;
        }

        .change-photo-btn {
            position: absolute;
            bottom: 3px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            color: #667eea;
            border: none;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            white-space: nowrap;
        }

        .change-photo-btn:hover {
            background: #667eea;
            color: white;
            transform: translateX(-50%) translateY(-2px);
        }

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .form-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-wrapper">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="welcome-section">
                    <h1>Profile Saya</h1>
                </div>
                <div class="header-actions">
                <?php if ($role === 'admin'): ?>
                <a href="create_user.php" class="btn-add" style="text-decoration: none; margin-right: 10px;">
                    ➕ Buat Akun Baru
                </a>
                <?php endif; ?>
                <button class="btn-logout" onclick="logout()">Logout</button>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error">
            ⚠️ <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success">
            ✓ <?php echo $success; ?>
        </div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar-wrapper">
                <?php 
                $photo_path = dirname(__DIR__) . '/uploads/profiles/' . $user_data['profile_photo'];
                if (!empty($user_data['profile_photo']) && file_exists($photo_path)): 
                ?>
                    <img src="../uploads/profiles/<?php echo htmlspecialchars($user_data['profile_photo']); ?>?v=<?php echo time(); ?>" 
                         alt="Profile Photo" class="profile-avatar-img">
                <?php else: ?>
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <button type="button" class="change-photo-btn" onclick="document.getElementById('photoInput').click()">
                    Ubah Foto Profile
                </button>
            </div>
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($username); ?></h2>
                <div class="profile-role">
                    <?php echo $role === 'admin' ? '👑 Administrator' : '👤 User'; ?>
                </div>
                <div class="profile-email">
                    📧 <?php echo htmlspecialchars($user_data['email']); ?>
                </div>
            </div>
        </div>

        <!-- Form Upload Photo (Hidden) -->
        <form method="POST" enctype="multipart/form-data" id="photoForm" style="display: none;">
            <input type="file" id="photoInput" name="profile_photo" accept="image/jpeg,image/jpg,image/png" onchange="previewAndUpload(this)">
            <input type="hidden" name="update_photo" value="1">
        </form>

        <!-- Stats Mini -->
        <?php if ($role !== 'admin'): ?>
        <div class="stats-mini">
            <div class="stat-mini-card blue">
                <div class="stat-mini-number"><?php echo $stats['total_sk']; ?></div>
                <div class="stat-mini-label">Total SK Saya</div>
            </div>
            <div class="stat-mini-card yellow">
                <div class="stat-mini-number"><?php echo $stats['draft']; ?></div>
                <div class="stat-mini-label">Draft</div>
            </div>
            <div class="stat-mini-card red">
                <div class="stat-mini-number"><?php echo $stats['revisi']; ?></div>
                <div class="stat-mini-label">Perlu Revisi</div>
            </div>
            <div class="stat-mini-card green">
                <div class="stat-mini-number"><?php echo $stats['disetujui']; ?></div>
                <div class="stat-mini-label">Disetujui</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Edit Profile Form -->
        <div class="content-card">
            <div class="card-header">
                <div class="card-title">✏️ Edit Profil</div>
            </div>

            <form method="POST" action="">
                <div class="form-section">
                    <div class="form-group">
                        <label class="form-label">Username <span style="color: #e53e3e;">*</span></label>
                        <input type="text" name="username" class="form-input" 
                               placeholder="Masukkan username" required minlength="4"
                               value="<?php echo htmlspecialchars($user_data['username']); ?>">
                        <small style="color: #718096; font-size: 13px;">Username dapat diubah</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-input" 
                               value="<?php echo htmlspecialchars($user_data['email']); ?>" 
                               disabled style="background: #f7fafc; cursor: not-allowed;">
                        <small style="color: #718096; font-size: 13px;">Email tidak dapat diubah</small>
                    </div>
                </div>

                <div class="form-section-full" style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #e2e8f0;">
                    <h3 style="color: #2d3748; font-size: 18px; margin-bottom: 15px;">🔒 Ubah Password</h3>
                    <p style="color: #718096; font-size: 14px; margin-bottom: 20px;">Biarkan kosong jika tidak ingin mengubah password</p>

                    <div class="form-section">
                        <div class="form-group">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="new_password" class="form-input" 
                                   placeholder="Minimal 6 karakter" minlength="6">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" name="confirm_password" class="form-input" 
                                   placeholder="Ulangi password baru">
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 30px;">
                    <button type="submit" name="update_profile" class="btn-add" style="flex: 1;">
                        💾 Simpan Perubahan
                    </button>
                    <a href="dashboard.php" class="btn-back" style="flex: 0; padding: 12px 24px; text-decoration: none;">
                        ✕ Batal
                    </a>
                </div>
            </form>
        </div>

        <!-- Account Info -->
        <div class="content-card" style="margin-top: 20px;">
            <div class="card-header">
                <div class="card-title">ℹ️ Informasi Akun</div>
            </div>
            <div style="display: grid; gap: 15px;">
                <div style="display: flex; justify-content: space-between; padding: 12px; background: #f7fafc; border-radius: 8px;">
                    <span style="color: #4a5568; font-weight: 600;">Role:</span>
                    <span style="color: #2d3748;"><?php echo ucfirst($user_data['role']); ?></span>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function logout() {
            if (confirm('Apakah Anda yakin ingin logout?')) {
                window.location.href = '../auth/logout.php';
            }
        }

        // Validasi password match
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.querySelector('[name="new_password"]').value;
            const confirmPassword = document.querySelector('[name="confirm_password"]').value;

            if (newPassword && newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Password baru dan konfirmasi password tidak cocok!');
            }

            if (newPassword && newPassword.length < 6) {
                e.preventDefault();
                alert('Password minimal 6 karakter!');
            }
        });

        // Upload photo function
        function previewAndUpload(input) {
            const file = input.files[0];
            
            if (file) {
                // Validasi ukuran
                if (file.size > 2 * 1024 * 1024) {
                    alert('Ukuran file maksimal 2MB!');
                    input.value = '';
                    return;
                }
                
                // Validasi tipe
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Hanya file JPG, JPEG, dan PNG yang diizinkan!');
                    input.value = '';
                    return;
                }
                
                // Preview image
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (confirm('Upload foto ini sebagai foto profil?')) {
                        // Submit form
                        document.getElementById('photoForm').submit();
                    } else {
                        input.value = '';
                    }
                };
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>