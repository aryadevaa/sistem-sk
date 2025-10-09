<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check login
requireLogin();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Clear filter jika diminta
if (isset($_GET['clear'])) {
    unset($_SESSION['report_filter']);
    header("Location: report_sk.php");
    exit;
}

// Data hasil pencarian
$hasil_pencarian = [];
$is_searched = false;
$tanggal_awal = '';
$tanggal_akhir = '';

// Cek apakah ada pencarian baru
if (isset($_GET['cari'])) {
    $tanggal_awal = clean($_GET['tanggal_awal']);
    $tanggal_akhir = clean($_GET['tanggal_akhir']);
    
    // Simpan ke session
    $_SESSION['report_filter'] = [
        'tanggal_awal' => $tanggal_awal,
        'tanggal_akhir' => $tanggal_akhir
    ];
    
    $is_searched = true;
} 
// Cek apakah ada filter tersimpan di session
else if (isset($_SESSION['report_filter'])) {
    $tanggal_awal = $_SESSION['report_filter']['tanggal_awal'];
    $tanggal_akhir = $_SESSION['report_filter']['tanggal_akhir'];
    $is_searched = true;
}

// Jalankan query jika ada pencarian
if ($is_searched && !empty($tanggal_awal) && !empty($tanggal_akhir)) {
    // Query berdasarkan role
    if ($role === 'admin') {
        $query = "SELECT sk.*, u.username as creator_name 
                  FROM surat_keputusan sk 
                  LEFT JOIN users u ON sk.created_by = u.id 
                  WHERE sk.tgl BETWEEN '$tanggal_awal' AND '$tanggal_akhir' 
                  ORDER BY sk.tgl DESC";
    } else {
        $query = "SELECT sk.*, u.username as creator_name 
                  FROM surat_keputusan sk 
                  LEFT JOIN users u ON sk.created_by = u.id 
                  WHERE sk.created_by = '$user_id' 
                  AND sk.tgl BETWEEN '$tanggal_awal' AND '$tanggal_akhir' 
                  ORDER BY sk.tgl DESC";
    }
    
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $hasil_pencarian[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Surat Keputusan - Sistem Informasi SK</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <style>
        .filter-section {
            background: #f7fafc;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 2px solid #e2e8f0;
        }

        .filter-title {
            color: #2d3748;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        .btn-search {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            height: fit-content;
        }

        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .results-section {
            margin-top: 30px;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .results-title {
            color: #2d3748;
            font-size: 20px;
            font-weight: 700;
        }

        .results-count {
            background: #edf2f7;
            padding: 8px 16px;
            border-radius: 20px;
            color: #4a5568;
            font-weight: 600;
            font-size: 14px;
        }

        .btn-export {
            background: #48bb78;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-export:hover {
            background: #38a169;
            transform: translateY(-2px);
        }

        .btn-view-pdf {
            background: #60a5fa;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-view-pdf:hover {
            background: #3b82f6;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }

            .results-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
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
                    <h1>Laporan Surat Keputusan</h1>
                </div>
                <div class="header-actions">
                    <div class="date-badge">📅 <?php echo formatTanggal(date('Y-m-d')); ?></div>
                    <button class="btn-logout" onclick="logout()">Logout</button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content-card">
            <div class="content-header">
                <div>
                    <h2>📊 Laporan Surat Keputusan</h2>
                    <p style="color: #718096; font-size: 14px; margin-top: 5px;">Cari dan lihat laporan surat keputusan berdasarkan rentang tanggal</p>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-title">
                    🔍 Filter Pencarian
                </div>
                <form class="filter-form" method="GET" action="">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Tanggal SK Awal</label>
                        <input type="date" name="tanggal_awal" class="form-input" required 
                               value="<?php echo htmlspecialchars($tanggal_awal); ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Tanggal SK Akhir</label>
                        <input type="date" name="tanggal_akhir" class="form-input" required
                               value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
                    </div>
                    <button type="submit" name="cari" class="btn-search">
                        🔍 Cari
                    </button>
                </form>
                <?php if ($is_searched): ?>
                <button type="button" class="btn-search" style="background: #f56565; margin-top: 5px;" onclick="clearFilter()">
                    ✕ Reset
                </button>
                <?php endif; ?>
            </div>

            <!-- Results Section -->
            <?php if ($is_searched): ?>
            <div class="results-section">
                <div class="results-header">
                    <div>
                        <div class="results-title">Hasil Pencarian</div>
                        <div class="results-count"><?php echo count($hasil_pencarian); ?> data ditemukan</div>
                    </div>
                    <?php if (count($hasil_pencarian) > 0): ?>
                    <!-- <button class="btn-export" onclick="window.print()">
                        📥 Export / Print
                    </button> -->
                    <?php endif; ?>
                </div>

                <?php if (count($hasil_pencarian) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No Register</th>
                                <th>Nomor Surat Keputusan</th>
                                <th>Perihal</th>
                                <th>Tanggal SK</th>
                                <th>Expired</th>
                                <th class="no-print">View</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($hasil_pencarian as $sk): 
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($sk['no_reg']); ?></td>
                                <td><?php echo htmlspecialchars($sk['no_sk']); ?></td>
                                <td><?php echo htmlspecialchars($sk['hal']); ?></td>
                                <td><?php echo formatTanggal($sk['tgl'], 'd-m-Y'); ?></td>
                                <td>
                                    <?php 
                                    $sisa = sisaHariBerlaku($sk['tanggal_expired']);
                                    echo ($sisa > 0) ? $sisa . ' hari' : ((isExpired($sk['tanggal_expired'])) ? 'Expired' : '-');
                                    ?>
                                </td>
                                <td class="no-print">
                                    <button class="btn-view-pdf" onclick="viewSK('<?php echo $sk['no_reg']; ?>')">
                                        👁️ View
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <div class="empty-state-title">Tidak Ada Data</div>
                    <div class="empty-state-text">Tidak ditemukan surat keputusan pada rentang tanggal yang dipilih</div>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">🔍</div>
                <div class="empty-state-title">Mulai Pencarian</div>
                <div class="empty-state-text">Pilih rentang tanggal dan klik tombol "Cari" untuk menampilkan laporan</div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function viewSK(noReg) {
            window.location.href = 'view_sk.php?id=' + noReg;
        }

        function clearFilter() {
            if (confirm('Hapus filter pencarian?')) {
                window.location.href = 'report_sk.php?clear=1';
            }
        }

        function logout() {
            if (confirm('Apakah Anda yakin ingin logout?')) {
                window.location.href = '../auth/logout.php';
            }
        }
    </script>

    <style>
        @media print {
            .sidebar, .header-actions, .btn-search, .btn-export, .no-print {
                display: none !important;
            }
            .main-wrapper {
                margin-left: 0;
            }
            .content-card {
                box-shadow: none;
            }
        }
    </style>
</body>
</html>