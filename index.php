<?php
require_once 'includes/config.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: pages/dashboard.php");
    exit;
} else {
    // Jika belum login, redirect ke login
    header("Location: auth/login.php");
    exit;
}
?>