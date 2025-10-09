<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if admin
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acc_sk'])) {
    $no_reg = clean($_POST['no_reg']);
    
    // Update status menjadi Disetujui
    $query = "UPDATE surat_keputusan SET status='Disetujui', updated_at=NOW() WHERE no_reg='$no_reg'";
    
    if (mysqli_query($conn, $query)) {
        header("Location: ../pages/data_sk.php?success=approved");
    } else {
        header("Location: ../pages/view_sk.php?id=$no_reg&error=acc");
    }
} else {
    header("Location: ../pages/data_sk.php");
}
exit;
?>