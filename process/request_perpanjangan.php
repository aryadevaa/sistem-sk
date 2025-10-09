<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check login
requireLogin();

if (isset($_GET['id'])) {
    $no_reg = clean($_GET['id']);
    $user_id = $_SESSION['user_id'];
    
    // Ambil data SK
    $sk_data = getSKData($no_reg, $conn);
    
    if (!$sk_data) {
        header("Location: ../pages/data_sk.php?error=notfound");
        exit;
    }
    
    // Cek apakah user adalah pemilik SK (atau admin)
    if ($sk_data['created_by'] != $user_id && !isAdmin()) {
        header("Location: ../pages/data_sk.php?error=forbidden");
        exit;
    }
    
    // Cek apakah SK sudah disetujui dan expired
    if ($sk_data['status'] !== 'Disetujui' || !isExpired($sk_data['tanggal_expired'])) {
        header("Location: ../pages/data_sk.php?error=not_expired");
        exit;
    }
    
    // Cek apakah sudah pernah mengajukan perpanjangan
    if ($sk_data['perpanjangan_status'] !== 'tidak') {
        header("Location: ../pages/data_sk.php?error=already_requested");
        exit;
    }
    
    // Update status perpanjangan menjadi 'diminta'
    $query = "UPDATE surat_keputusan 
              SET perpanjangan_status='diminta', updated_at=NOW() 
              WHERE no_reg='$no_reg'";
    
    if (mysqli_query($conn, $query)) {
        header("Location: ../pages/data_sk.php?success=perpanjangan_diajukan");
    } else {
        header("Location: ../pages/data_sk.php?error=perpanjangan_gagal");
    }
} else {
    header("Location: ../pages/data_sk.php");
}
exit;
?>