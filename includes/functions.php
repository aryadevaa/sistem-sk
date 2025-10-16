<?php
// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "auth/login.php");
        exit;
    }
}

// Redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: " . BASE_URL . "pages/dashboard.php");
        exit;
    }
}

// Format tanggal Indonesia
function formatTanggal($date, $format = 'd F Y') {
    $bulan = array(
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );
    
    $split = explode('-', date('Y-m-d', strtotime($date)));
    
    if ($format == 'd F Y') {
        return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
    } else if ($format == 'd/m/Y') {
        return $split[2] . '/' . $split[1] . '/' . $split[0];
    }
    
    return date($format, strtotime($date));
}

// Generate No Register otomatis
function generateNoRegister($conn) {
    $query = "SELECT no_reg FROM surat_keputusan ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $lastNo = (int) substr($row['no_reg'], 4); // Ambil angka dari REG-00001
        $newNo = $lastNo + 1;
    } else {
        $newNo = 1;
    }
    
    return 'REG-' . str_pad($newNo, 5, '0', STR_PAD_LEFT);
}

// Upload file PDF
function uploadPDF($file, $noReg) {
    $targetDir = UPLOAD_PATH;
    $fileName = $noReg . '_' . time() . '.pdf';
    $targetFile = $targetDir . $fileName;
    
    // Cek apakah file adalah PDF
    $fileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    if ($fileType != "pdf") {
        return array('success' => false, 'message' => 'Hanya file PDF yang diizinkan');
    }
    
    // Cek ukuran file (max 5MB)
    if ($file["size"] > 5000000) {
        return array('success' => false, 'message' => 'Ukuran file terlalu besar (max 5MB)');
    }
    
    // Upload file
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return array('success' => true, 'filename' => $fileName);
    } else {
        return array('success' => false, 'message' => 'Terjadi kesalahan saat upload file');
    }
}

// Delete file PDF
function deletePDF($filename) {
    $filePath = UPLOAD_PATH . $filename;
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return false;
}

// Sanitize input
function clean($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

// Get user data
function getUserData($userId, $conn) {
    $query = "SELECT * FROM users WHERE id = '$userId'";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

// Get SK data by no_reg
function getSKData($noReg, $conn) {
    $query = "SELECT sk.*, u.username as creator_name 
              FROM surat_keputusan sk 
              LEFT JOIN users u ON sk.created_by = u.id 
              WHERE sk.no_reg = '$noReg'";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

// Get comments for SK
function getComments($skId, $conn) {
    $query = "SELECT * FROM comments WHERE sk_id = '$skId' ORDER BY created_at DESC";
    $result = mysqli_query($conn, $query);
    $comments = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $comments[] = $row;
    }
    return $comments;
}

// Add digital signature to PDF
function addSignatureToPDF($sourcePDF, $signaturePath, $outputPDF) {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    try {
        $pdf = new \setasign\Fpdi\Fpdi();
        
        // Get the number of pages in source PDF
        $pageCount = $pdf->setSourceFile($sourcePDF);
        
        // Import all pages from source
        for ($i = 1; $i <= $pageCount; $i++) {
            $tplIdx = $pdf->importPage($i);
            $pdf->AddPage();
            $pdf->useTemplate($tplIdx);
            
            // Add signature only to the last page
            if ($i === $pageCount && file_exists($signaturePath)) {
                // Get page dimensions
                $pageWidth = $pdf->GetPageWidth();
                $pageHeight = $pdf->GetPageHeight();
                
                // Position signature at bottom right
                $signatureWidth = 50; // Adjust as needed
                $signatureX = $pageWidth - $signatureWidth - 20; // 20mm from right
                $signatureY = $pageHeight - 40; // 40mm from bottom
                
                // Add the signature image
                $pdf->Image($signaturePath, $signatureX, $signatureY, $signatureWidth);
            }
        }
        
        // Save the signed PDF
        $pdf->Output('F', $outputPDF);
        return true;
    } catch (Exception $e) {
        error_log("Error adding signature: " . $e->getMessage());
        return false;
    }
}

// Count statistics
function getStatistics($conn, $userId = null) {
    $stats = array();
    
    // Total SK
    if ($userId && !isAdmin()) {
        $query = "SELECT COUNT(*) as total FROM surat_keputusan WHERE created_by = '$userId'";
    } else {
        $query = "SELECT COUNT(*) as total FROM surat_keputusan";
    }
    $result = mysqli_query($conn, $query);
    $stats['total_sk'] = mysqli_fetch_assoc($result)['total'];
    
    // SK Bulan Ini
    $bulanIni = date('Y-m');
    if ($userId && !isAdmin()) {
        $query = "SELECT COUNT(*) as total FROM surat_keputusan 
                  WHERE created_by = '$userId' AND DATE_FORMAT(created_at, '%Y-%m') = '$bulanIni'";
    } else {
        $query = "SELECT COUNT(*) as total FROM surat_keputusan 
                  WHERE DATE_FORMAT(created_at, '%Y-%m') = '$bulanIni'";
    }
    $result = mysqli_query($conn, $query);
    $stats['sk_bulan_ini'] = mysqli_fetch_assoc($result)['total'];
    
    // SK Tahun Ini
    $tahunIni = date('Y');
    if ($userId && !isAdmin()) {
        $query = "SELECT COUNT(*) as total FROM surat_keputusan 
                  WHERE created_by = '$userId' AND YEAR(created_at) = '$tahunIni'";
    } else {
        $query = "SELECT COUNT(*) as total FROM surat_keputusan 
                  WHERE YEAR(created_at) = '$tahunIni'";
    }
    $result = mysqli_query($conn, $query);
    $stats['sk_tahun_ini'] = mysqli_fetch_assoc($result)['total'];
    
    // SK Draft/Revisi
    if ($userId && !isAdmin()) {
        $query = "SELECT COUNT(*) as total FROM surat_keputusan 
                  WHERE created_by = '$userId' AND status IN ('Draft', 'Revisi')";
    } else {
        $query = "SELECT COUNT(*) as total FROM surat_keputusan 
                  WHERE status IN ('Draft', 'Revisi')";
    }
    $result = mysqli_query($conn, $query);
    $stats['sk_menunggu'] = mysqli_fetch_assoc($result)['total'];
    
    return $stats;
}

// Check apakah SK sudah expired
function isExpired($tanggal_expired) {
    if (empty($tanggal_expired)) return false;
    return strtotime($tanggal_expired) < strtotime(date('Y-m-d'));
}

// Check apakah SK akan expired dalam X hari
function willExpireSoon($tanggal_expired, $days = 30) {
    if (empty($tanggal_expired)) return false;
    $diff = (strtotime($tanggal_expired) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
    return $diff > 0 && $diff <= $days;
}

// Hitung sisa hari berlaku (kembalikan integer jumlah hari tersisa)
function sisaHariBerlaku($tanggal_expired) {
    if (empty($tanggal_expired)) return 0;
    $now = strtotime(date('Y-m-d'));
    $exp = strtotime($tanggal_expired);
    if ($exp <= $now) return 0;
    $diff = ($exp - $now) / (60 * 60 * 24);
    return (int) floor($diff);
}

// Get status expired label
function getExpiredStatus($tanggal_expired) {
    if (empty($tanggal_expired)) {
        return ['status' => 'unknown', 'label' => 'Tidak Diketahui', 'class' => 'secondary'];
    }
    
    if (isExpired($tanggal_expired)) {
        return ['status' => 'expired', 'label' => 'Kadaluarsa', 'class' => 'danger'];
    }
    
    if (willExpireSoon($tanggal_expired, 30)) {
        $days = floor((strtotime($tanggal_expired) - strtotime(date('Y-m-d'))) / (60 * 60 * 24));
        return ['status' => 'warning', 'label' => "Expired {$days} hari lagi", 'class' => 'warning'];
    }
    
    return ['status' => 'active', 'label' => 'Berlaku', 'class' => 'success'];
}

// Hitung tanggal expired dari tanggal SK
function calculateExpiredDate($tanggal_sk, $bulan = null) {
    if ($bulan === null) {
        $bulan = MASA_BERLAKU_SK;
    }
    return date('Y-m-d', strtotime("+{$bulan} months", strtotime($tanggal_sk)));
}

// Upload foto profil
function uploadProfilePhoto($file, $user_id) {
    $targetDir = UPLOAD_PATH . 'profiles/';
    $fileName = 'profile_' . $user_id . '_' . time() . '.jpg';
    $targetFile = $targetDir . $fileName;
    
    // Cek apakah file adalah gambar
    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        return ['success' => false, 'message' => 'Hanya file gambar yang diizinkan (JPG, JPEG, PNG, GIF)'];
    }
    
    // Cek ukuran file (max 2MB)
    if ($file["size"] > 2000000) {
        return ['success' => false, 'message' => 'Ukuran file terlalu besar (max 2MB)'];
    }
    
    // Upload file
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return ['success' => true, 'filename' => $fileName];
    } else {
        return ['success' => false, 'message' => 'Terjadi kesalahan saat upload file'];
    }
}
?>