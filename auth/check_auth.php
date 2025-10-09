<?php
// Middleware untuk cek autentikasi
// Include file ini di setiap halaman yang memerlukan login

require_once __DIR__ . '/../includes/config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    // Jika belum login, redirect ke halaman login
    header("Location: " . BASE_URL . "auth/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Cek apakah session masih valid (optional - untuk keamanan lebih)
// Misalnya cek last activity time
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
    // Session timeout setelah 1 jam tidak aktif
    session_unset();
    session_destroy();
    header("Location: " . BASE_URL . "auth/login.php?timeout=1");
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Ambil data user dari database untuk memastikan user masih ada
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = '$user_id'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    // User tidak ditemukan di database
    session_unset();
    session_destroy();
    header("Location: " . BASE_URL . "auth/login.php?error=invalid");
    exit;
}

// User valid, lanjutkan
$current_user = mysqli_fetch_assoc($result);

// Update session dengan data terbaru (jika ada perubahan)
$_SESSION['username'] = $current_user['username'];
$_SESSION['email'] = $current_user['email'];
$_SESSION['role'] = $current_user['role'];

// Function untuk cek apakah user adalah admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Function untuk cek apakah user adalah pemilik data
function isOwner($created_by_id) {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] == $created_by_id;
}
?>