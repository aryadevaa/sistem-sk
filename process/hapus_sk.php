<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if admin
requireAdmin();

if (isset($_GET['id'])) {
    $no_reg = clean($_GET['id']);
    
    // Ambil data SK untuk hapus file PDF
    $sk_data = getSKData($no_reg, $conn);
    
    if ($sk_data) {
        // Hapus file PDF jika ada
        if (!empty($sk_data['file'])) {
            deletePDF($sk_data['file']);
        }
        
        // Hapus dari database (comments akan terhapus otomatis karena CASCADE)
        $query = "DELETE FROM surat_keputusan WHERE no_reg='$no_reg'";
        
        if (mysqli_query($conn, $query)) {
            header("Location: ../pages/data_sk.php?success=deleted");
        } else {
            header("Location: ../pages/data_sk.php?error=delete");
        }
    } else {
        header("Location: ../pages/data_sk.php?error=notfound");
    }
} else {
    header("Location: ../pages/data_sk.php");
}
exit;
?>