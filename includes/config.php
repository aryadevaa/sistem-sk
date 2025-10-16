<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sk_system');

// Koneksi Database
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8");

// Base URL
define('BASE_URL', 'http://localhost/sistem-sk/');

// Path Upload
define('UPLOAD_PATH', __DIR__ . '/../uploads/sk/');
define('UPLOAD_URL', BASE_URL . 'uploads/sk/');

// Path Upload Signatures
define('SIGNATURE_PATH', __DIR__ . '/../uploads/signatures/');
define('SIGNATURE_URL', BASE_URL . 'uploads/signatures/');

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Masa Berlaku SK (dalam bulan) - EDIT DI SINI
define('MASA_BERLAKU_SK', 1); // Default 5 bulan
?>