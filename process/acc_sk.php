<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../vendor/autoload.php';

// Check if admin
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acc_sk'])) {
    $no_reg = clean($_POST['no_reg']);
    $admin_id = $_SESSION['user_id'];
    
    // Get SK data
    $sk_data = getSKData($no_reg, $conn);
    if (!$sk_data) {
        header("Location: ../pages/view_sk.php?id=$no_reg&error=notfound");
        exit;
    }
    
    // Get admin's signature
    $admin_data = getUserData($admin_id, $conn);
    if (empty($admin_data['signature_path'])) {
        header("Location: ../pages/view_sk.php?id=$no_reg&error=no_signature");
        exit;
    }
    
    $sourcePDF = UPLOAD_PATH . $sk_data['file'];
    $signaturePath = SIGNATURE_PATH . $admin_data['signature_path'];
    $signedFileName = 'signed_' . time() . '_' . $sk_data['file'];
    $outputPDF = UPLOAD_PATH . $signedFileName;
    
    // Add signature to PDF
    if (!file_exists($sourcePDF)) {
        header("Location: ../pages/view_sk.php?id=$no_reg&error=file_not_found");
        exit;
    }

    if (!file_exists($signaturePath)) {
        header("Location: ../pages/view_sk.php?id=$no_reg&error=signature_not_found");
        exit;
    }

    // Add signature to PDF
    if (addSignatureToPDF($sourcePDF, $signaturePath, $outputPDF)) {
        // Update database with signed file and approval info
        $query = "UPDATE surat_keputusan SET 
                  status='Disetujui', 
                  signed_file='$signedFileName',
                  approved_by='$admin_id',
                  approved_at=NOW(),
                  updated_at=NOW() 
                  WHERE no_reg='$no_reg'";
        
        if (mysqli_query($conn, $query)) {
            header("Location: ../pages/data_sk.php?success=approved");
            exit;
        } else {
            // If database update fails, delete the signed file
            if (file_exists($outputPDF)) {
                unlink($outputPDF);
            }
            error_log("MySQL Error: " . mysqli_error($conn));
            header("Location: ../pages/view_sk.php?id=$no_reg&error=acc");
            exit;
        }
    } else {
        error_log("Failed to add signature to PDF for SK: $no_reg");
        header("Location: ../pages/view_sk.php?id=$no_reg&error=signature_failed");
    }
} else {
    header("Location: ../pages/data_sk.php");
}
exit;
?>