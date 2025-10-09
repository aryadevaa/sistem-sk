<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check login
requireLogin();

// Ambil data user
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Get statistics
$stats = getStatistics($conn, $user_id);

// Data SK terbaru
if ($role === 'admin') {
    $query = "SELECT sk.*, u.username as creator_name 
              FROM surat_keputusan sk 
              LEFT JOIN users u ON sk.created_by = u.id 
              ORDER BY sk.created_at DESC LIMIT 3";
} else {
    $query = "SELECT sk.*, u.username as creator_name 
              FROM surat_keputusan sk 
              LEFT JOIN users u ON sk.created_by = u.id 
              WHERE sk.created_by = '$user_id'
              ORDER BY sk.created_at DESC LIMIT 3";
}
$result = mysqli_query($conn, $query);
$sk_terbaru = [];
while ($row = mysqli_fetch_assoc($result)) {
    $sk_terbaru[] = $row;
}

// Data chart - SK per bulan (6 bulan terakhir)
$chart_data = [];
$max_value = 1; // Set default max value

for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i month"));
    $month_name = date('M', strtotime("-$i month"));
    
    if ($role === 'admin') {
        $query = "SELECT COUNT(*) as jumlah FROM surat_keputusan 
                  WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'";
    } else {
        $query = "SELECT COUNT(*) as jumlah FROM surat_keputusan 
                  WHERE created_by = '$user_id' AND DATE_FORMAT(created_at, '%Y-%m') = '$month'";
    }
    
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $jumlah = (int)$row['jumlah'];
    
    $chart_data[] = [
        'bulan' => $month_name,
        'jumlah' => $jumlah
    ];
    
    // Update max value untuk scaling
    if ($jumlah > $max_value) {
        $max_value = $jumlah;
    }
}

// Aktivitas terbaru (khusus admin)
$aktivitas = [];
if ($role === 'admin') {
    $query = "SELECT sk.no_reg, sk.hal, sk.status, sk.created_at, u.username 
              FROM surat_keputusan sk 
              LEFT JOIN users u ON sk.created_by = u.id 
              ORDER BY sk.created_at DESC LIMIT 3";
    $result = mysqli_query($conn, $query);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $time_ago = time() - strtotime($row['created_at']);
        $hours = floor($time_ago / 3600);
        $days = floor($time_ago / 86400);
        
        if ($days > 0) {
            $waktu = $days . ' hari yang lalu';
        } else if ($hours > 0) {
            $waktu = $hours . ' jam yang lalu';
        } else {
            $waktu = 'Baru saja';
        }
        
        $aktivitas[] = [
            'aksi' => 'SK Baru Dibuat',
            'detail' => $row['no_reg'] . ' - ' . $row['hal'],
            'waktu' => $waktu,
            'icon' => '➕',
            'color' => '#48bb78'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Informasi SK</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-wrapper">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="welcome-section">
                    <h1>👋 Selamat Datang, <?php echo htmlspecialchars($username); ?>!</h1>
                    <p>Berikut adalah ringkasan sistem informasi surat keputusan Anda</p>
                </div>
                <div class="header-actions">
                    <div class="date-badge">📅 <?php echo formatTanggal(date('Y-m-d')); ?></div>
                    <button class="btn-logout" onclick="logout()">Logout</button>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?php echo $stats['total_sk']; ?></div>
                        <div class="stat-label">Total Surat Keputusan</div>
                    </div>
                    <div class="stat-icon">📄</div>
                </div>
            </div>

            <div class="stat-card green">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?php echo $stats['sk_bulan_ini']; ?></div>
                        <div class="stat-label">SK Bulan Ini</div>
                    </div>
                    <div class="stat-icon">📊</div>
                </div>
            </div>

            <div class="stat-card orange">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?php echo $stats['sk_tahun_ini']; ?></div>
                        <div class="stat-label">SK Tahun Ini</div>
                    </div>
                    <div class="stat-icon">📈</div>
                </div>
            </div>

            <div class="stat-card purple">
                <div class="stat-header">
                    <div>
                        <div class="stat-number"><?php echo $stats['sk_menunggu']; ?></div>
                        <div class="stat-label">Draft / Menunggu</div>
                        <div class="stat-change down">↘ Perlu review</div>
                    </div>
                    <div class="stat-icon">⏳</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="tambah_sk.php" class="action-card">
                <div class="action-icon">➕</div>
                <div class="action-title">Tambah Surat Keputusan</div>
                <div class="action-desc">Buat surat keputusan baru dengan cepat</div>
            </a>
            <a href="report_sk.php" class="action-card">
                <div class="action-icon">📊</div>
                <div class="action-title">Lihat Laporan</div>
                <div class="action-desc">Akses laporan surat keputusan</div>
            </a>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Chart Card -->
            <div class="content-card">
                <div class="card-header">
                    <div class="card-title">📈 Statistik SK 6 Bulan Terakhir</div>
                </div>
                <div class="chart-container">
                    <?php foreach ($chart_data as $data): ?>
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                        <div class="chart-bar" style="height: <?php echo ($data['jumlah'] > 0 ? ($data['jumlah'] / max(array_column($chart_data, 'jumlah')) * 100) : 5); ?>%;">
                            <div class="chart-value"><?php echo $data['jumlah']; ?></div>
                        </div>
                        <div class="chart-label"><?php echo $data['bulan']; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Activity Card -->
            <div class="content-card">
                <div class="card-header">
                    <div class="card-title">🕐 Aktivitas Terbaru</div>
                </div>
                <div class="activity-list">
                    <?php if (count($aktivitas) > 0): ?>
                        <?php foreach ($aktivitas as $item): ?>
                        <div class="activity-item">
                            <div class="activity-icon" style="background: <?php echo $item['color']; ?>20;">
                                <?php echo $item['icon']; ?>
                            </div>
                            <div class="activity-content">
                                <div class="activity-action"><?php echo $item['aksi']; ?></div>
                                <div class="activity-detail"><?php echo $item['detail']; ?></div>
                                <div class="activity-time"><?php echo $item['waktu']; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📭</div>
                            <div class="empty-state-text">Belum ada aktivitas</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent SK -->
        <div class="content-card">
            <div class="card-header">
                <div class="card-title">📋 Surat Keputusan Terbaru</div>
                <a href="data_sk.php" class="btn-view-all" style="color: #667eea; text-decoration: none; font-weight: 600;">Lihat Semua →</a>
            </div>
            <?php if (count($sk_terbaru) > 0): ?>
            <div class="sk-list">
                <?php foreach ($sk_terbaru as $sk): ?>
                <div class="sk-item" onclick="viewSK('<?php echo $sk['no_reg']; ?>')">
                    <div class="sk-item-header">
                        <div class="sk-register"><?php echo $sk['no_reg']; ?></div>
                        <?php 
                        $created_time = strtotime($sk['created_at']);
                        $now = time();
                        $diff = $now - $created_time;
                        if ($diff < 86400): // Kurang dari 24 jam
                        ?>
                        <div class="sk-badge">Baru</div>
                        <?php endif; ?>
                    </div>
                    <div class="sk-perihal"><?php echo $sk['hal']; ?></div>
                    <div class="sk-footer">
                        <div class="sk-date">📅 <?php echo formatTanggal($sk['tgl'], 'd M Y'); ?></div>
                        <div class="sk-number"><?php echo $sk['no_sk']; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <div class="empty-state-title">Belum Ada SK</div>
                <div class="empty-state-text">Belum ada surat keputusan yang dibuat</div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function viewSK(noReg) {
            window.location.href = 'view_sk.php?id=' + noReg;
        }

        function logout() {
            if (confirm('Apakah Anda yakin ingin logout?')) {
                window.location.href = '../auth/logout.php';
            }
        }

        // Animate chart bars on load
        window.addEventListener('load', function() {
            const bars = document.querySelectorAll('.chart-bar');
            bars.forEach((bar, index) => {
                setTimeout(() => {
                    bar.style.opacity = '0';
                    bar.style.transform = 'scaleY(0)';
                    bar.style.transition = 'all 0.6s ease';
                    
                    setTimeout(() => {
                        bar.style.opacity = '1';
                        bar.style.transform = 'scaleY(1)';
                    }, 50);
                }, index * 100);
            });
        });
    </script>
</body>
</html>