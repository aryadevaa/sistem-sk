<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if admin
requireAdmin();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

$error = '';
$success = '';

// Proses hapus user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $delete_user_id = clean($_POST['delete_user_id']);
    
    // Cek agar tidak bisa hapus diri sendiri
    if ($delete_user_id == $user_id) {
        $error = 'Anda tidak dapat menghapus akun Anda sendiri!';
    } else {
        // Hapus foto profil jika ada
        $user_to_delete = getUserData($delete_user_id, $conn);
        if (!empty($user_to_delete['profile_photo'])) {
            $photo_path = dirname(__DIR__) . '/uploads/profiles/' . $user_to_delete['profile_photo'];
            if (file_exists($photo_path)) {
                unlink($photo_path);
            }
        }
        
        // Hapus user dari database
        $query = "DELETE FROM users WHERE id='$delete_user_id'";
        if (mysqli_query($conn, $query)) {
            $success = 'Akun berhasil dihapus!';
        } else {
            $error = 'Gagal menghapus akun: ' . mysqli_error($conn);
        }
    }
}

// Proses create user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $new_username = clean($_POST['username']);
    $new_email = clean($_POST['email']);
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $new_role = clean($_POST['role']);
    
    // Validasi
    if (empty($new_username) || empty($new_email) || empty($new_password) || empty($new_role)) {
        $error = 'Semua field harus diisi!';
    } else if (strlen($new_username) < 4) {
        $error = 'Username minimal 4 karakter!';
    } else if (strlen($new_password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else if ($new_password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak cocok!';
    } else if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } else if (!in_array($new_role, ['admin', 'user'])) {
        $error = 'Role tidak valid!';
    } else {
        // Cek apakah username sudah ada
        $check_query = "SELECT id FROM users WHERE username = '$new_username'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Username sudah digunakan!';
        } else {
            // Cek apakah email sudah ada
            $check_email = "SELECT id FROM users WHERE email = '$new_email'";
            $check_email_result = mysqli_query($conn, $check_email);
            
            if (mysqli_num_rows($check_email_result) > 0) {
                $error = 'Email sudah terdaftar!';
            } else {
                // Hash password
                $password_hash = md5($new_password);
                
                // Insert ke database
                $query = "INSERT INTO users (username, password, email, role) 
                          VALUES ('$new_username', '$password_hash', '$new_email', '$new_role')";
                
                if (mysqli_query($conn, $query)) {
                    $success = 'Akun berhasil dibuat!';
                    // Reset form
                    $_POST = array();
                } else {
                    $error = 'Terjadi kesalahan: ' . mysqli_error($conn);
                }
            }
        }
    }
}

// Get list all users (untuk ditampilkan di bawah)
$users_query = "SELECT id, username, email, role FROM users ORDER BY id DESC";
$users_result = mysqli_query($conn, $users_query);
$all_users = [];
while ($row = mysqli_fetch_assoc($users_result)) {
    $all_users[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Akun Baru - Sistem Informasi SK</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <style>
        .role-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 10px;
        }

        .role-card {
            position: relative;
            cursor: pointer;
        }

        .role-card input[type="radio"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        .role-label {
            display: block;
            padding: 20px;
            background: #f7fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }

        .role-card input[type="radio"]:checked + .role-label {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: white;
        }

        .role-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .role-title {
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 4px;
        }

        .role-desc {
            font-size: 12px;
            opacity: 0.8;
        }

        .role-card input[type="radio"]:checked + .role-label .role-desc {
            opacity: 1;
        }

        .user-list-card {
            background: #f7fafc;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }

        .user-list-card:hover {
            background: #edf2f7;
        }

        .user-info {
            flex: 1;
        }

        .user-username {
            font-weight: 700;
            color: #2d3748;
            font-size: 16px;
        }

        .user-email {
            color: #718096;
            font-size: 14px;
        }

        .user-role-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .user-role-badge.admin {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .user-role-badge.user {
            background: #48bb78;
            color: white;
        }

        .btn-delete-user {
            background: #f56565;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-delete-user:hover {
            background: #e53e3e;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .role-cards {
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
                <div class="header-left">
                    <a href="profile.php" class="btn-back">
                        ← Kembali
                    </a>
                    <h1>Buat Akun Baru</h1>
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

        <?php if ($success): ?>
        <div class="alert alert-success">
            ✓ <?php echo $success; ?>
        </div>
        <?php endif; ?>

        <!-- Form Create User -->
        <div class="content-card">
            <div class="card-header">
                <div class="card-title">👤 Form Buat Akun Baru</div>
            </div>

            <form method="POST" action="" id="createUserForm">
                <div class="form-group">
                    <label class="form-label">Username <span style="color: #e53e3e;">*</span></label>
                    <input type="text" name="username" class="form-input" 
                           placeholder="Masukkan username" required minlength="4"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    <small style="color: #718096; font-size: 13px;">Minimal 4 karakter</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Email <span style="color: #e53e3e;">*</span></label>
                    <input type="email" name="email" class="form-input" 
                           placeholder="email@example.com" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Password <span style="color: #e53e3e;">*</span></label>
                    <input type="password" name="password" class="form-input" 
                           placeholder="Minimal 6 karakter" required minlength="6">
                </div>

                <div class="form-group">
                    <label class="form-label">Konfirmasi Password <span style="color: #e53e3e;">*</span></label>
                    <input type="password" name="confirm_password" class="form-input" 
                           placeholder="Ulangi password" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Pilih Role <span style="color: #e53e3e;">*</span></label>
                    <div class="role-cards">
                        <div class="role-card">
                            <input type="radio" name="role" id="role_user" value="user" required checked>
                            <label for="role_user" class="role-label">
                                <div class="role-icon">👤</div>
                                <div class="role-title">User</div>
                                <div class="role-desc">Membuat & mengelola SK</div>
                            </label>
                        </div>
                        <div class="role-card">
                            <input type="radio" name="role" id="role_admin" value="admin">
                            <label for="role_admin" class="role-label">
                                <div class="role-icon">👑</div>
                                <div class="role-title">Admin</div>
                                <div class="role-desc">Full access & approval</div>
                            </label>
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 30px;">
                    <button type="submit" class="btn-add" style="flex: 1;">
                        ✅ Buat Akun
                    </button>
                    <a href="profile.php" class="btn-back" style="flex: 0; padding: 12px 24px; text-decoration: none;">
                        ✕ Batal
                    </a>
                </div>
            </form>
        </div>

        <!-- List All Users -->
        <div class="content-card" style="margin-top: 25px;">
            <div class="card-header">
                <div class="card-title">👥 Daftar Semua User (<?php echo count($all_users); ?>)</div>
            </div>

            <?php if (count($all_users) > 0): ?>
                <?php foreach ($all_users as $user): ?>
                <div class="user-list-card">
                    <div class="user-info">
                        <div class="user-username">
                            <?php echo htmlspecialchars($user['username']); ?>
                            <?php if ($user['id'] == $user_id): ?>
                            <span style="color: #667eea; font-size: 12px;">(Anda)</span>
                            <?php endif; ?>
                        </div>
                        <div class="user-email">📧 <?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div class="user-role-badge <?php echo $user['role']; ?>">
                            <?php echo $user['role'] === 'admin' ? '👑 Admin' : '👤 User'; ?>
                        </div>
                        <?php if ($user['id'] != $user_id): ?>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun <?php echo htmlspecialchars($user['username']); ?>? Aksi ini tidak dapat dibatalkan!')">
                            <input type="hidden" name="delete_user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" name="delete_user" class="btn-delete-user">
                                🗑️ Hapus
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">👥</div>
                    <div class="empty-state-text">Belum ada user</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        // Form validation
        document.getElementById('createUserForm').addEventListener('submit', function(e) {
            const password = document.querySelector('[name="password"]').value;
            const confirmPassword = document.querySelector('[name="confirm_password"]').value;
            const username = document.querySelector('[name="username"]').value;

            if (username.length < 4) {
                e.preventDefault();
                alert('Username minimal 4 karakter!');
                return false;
            }

            if (password.length < 6) {
                e.preventDefault();
                alert('Password minimal 6 karakter!');
                return false;
            }

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Password dan konfirmasi password tidak cocok!');
                return false;
            }
        });

        function logout() {
            if (confirm('Apakah Anda yakin ingin logout?')) {
                window.location.href = '../auth/logout.php';
            }
        }
    </script>
</body>
</html>