<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check login
requireLogin();

// Ambil data user dari session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Query data SK
if ($role === 'admin') {
    // Admin melihat semua SK
    $query = "SELECT sk.*, u.username as creator_name 
              FROM surat_keputusan sk 
              LEFT JOIN users u ON sk.created_by = u.id 
              ORDER BY sk.created_at DESC";
} else {
    // User hanya melihat SK miliknya
    $query = "SELECT sk.*, u.username as creator_name 
              FROM surat_keputusan sk 
              LEFT JOIN users u ON sk.created_by = u.id 
              WHERE sk.created_by = '$user_id' 
              ORDER BY sk.created_at DESC";
}

$result = mysqli_query($conn, $query);
$data_sk = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data_sk[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Surat Keputusan - Sistem Informasi SK</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-wrapper">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="welcome-section">
                    <h1>Data Surat Keputusan</h1>
                </div>
                <div class="header-actions">
                    <div class="date-badge">📅 <?php echo formatTanggal(date('Y-m-d')); ?></div>
                    <button class="btn-logout" onclick="logout()">Logout</button>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?php 
            if ($_GET['success'] === 'created') echo '✓ Surat Keputusan berhasil dibuat!';
            if ($_GET['success'] === 'updated') echo '✓ Surat Keputusan berhasil diupdate!';
            if ($_GET['success'] === 'deleted') echo '✓ Surat Keputusan berhasil dihapus!';
            if ($_GET['success'] === 'approved') echo '✓ Surat Keputusan berhasil disetujui!';
            if ($_GET['success'] === 'perpanjangan_diajukan') echo '✓ Perpanjangan SK berhasil diajukan! Menunggu approval admin.';
            ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <?php 
            if ($_GET['error'] === 'notfound') echo '⚠️ Data tidak ditemukan!';
            if ($_GET['error'] === 'forbidden') echo '⚠️ Anda tidak memiliki akses!';
            if ($_GET['error'] === 'not_expired') echo '⚠️ SK belum expired atau belum disetujui!';
            if ($_GET['error'] === 'already_requested') echo '⚠️ Perpanjangan sudah pernah diajukan!';
            if ($_GET['error'] === 'perpanjangan_gagal') echo '⚠️ Gagal mengajukan perpanjangan!';
            ?>
        </div>
        <?php endif; ?>

        <!-- Main Content -->
        <div class="content-card">
            <div class="content-header">
                <h2>Daftar Surat Keputusan</h2>
                <button class="btn-add" onclick="tambahData()">
                    <span>➕</span> Tambah Surat Keputusan
                </button>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No Register</th>
                            <th>Nomor SK</th>
                            <th>Perihal</th>
                            <th>Tanggal SK</th>
                            <th>Expired</th>
                            <th>Status</th>
                            <th>Updated At</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (count($data_sk) > 0):
                            $no = 1;
                            foreach ($data_sk as $sk): 
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($sk['no_reg']); ?></td>
                            <td><?php echo htmlspecialchars($sk['no_sk']); ?></td>
                            <td><?php echo htmlspecialchars($sk['hal']); ?></td>
                            <td><?php echo formatTanggal($sk['tgl'], 'd/m/Y'); ?></td>
                            <td>
                                <?php 
                                // Tampilkan sisa hari hingga expired. Jika tidak ada tanggal atau sudah expired, tampilkan '-'
                                $sisa = sisaHariBerlaku($sk['tanggal_expired']);
                                echo ($sisa > 0) ? $sisa . ' hari' : ((isExpired($sk['tanggal_expired'])) ? 'Expired' : '-');
                                ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($sk['status']); ?>">
                                    <?php echo $sk['status']; ?>
                                </span>
                                <?php if (isExpired($sk['tanggal_expired']) && $sk['status'] === 'Disetujui'): ?>
                                <br><span class="status-badge status-expired" style="margin-top: 5px;">
                                    ⚠️ Expired
                                </span>
                                <?php elseif (willExpireSoon($sk['tanggal_expired']) && $sk['status'] === 'Disetujui'): ?>
                                <br><span class="status-badge status-warning" style="margin-top: 5px;">
                                    ⏰ <?php echo sisaHariBerlaku($sk['tanggal_expired']); ?> hari lagi
                                </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($sk['updated_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($sk['status'] !== 'Disetujui'): ?>
                                    <button class="btn-action btn-edit" onclick="editData('<?php echo $sk['no_reg']; ?>')">
                                        Edit
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    // Button Perpanjangan untuk SK yang expired
                                    if (isExpired($sk['tanggal_expired']) && $sk['status'] === 'Disetujui' && $sk['perpanjangan_status'] === 'tidak'):
                                    ?>
                                    <button class="btn-action btn-extend" onclick="ajukanPerpanjangan('<?php echo $sk['no_reg']; ?>')">
                                        🔄 Perpanjang
                                    </button>
                                    <?php elseif ($sk['perpanjangan_status'] === 'diminta'): ?>
                                    <span class="status-badge" style="background: #fbbf24; color: #78350f; font-size: 11px;">
                                        ⏳ Menunggu Approval
                                    </span>
                                    <?php endif; ?>
                                    
                                    <button class="btn-action btn-view" onclick="viewSK('<?php echo $sk['no_reg']; ?>')">
                                        View
                                    </button>
                                    <?php if ($role === 'admin'): ?>
                                    <button class="btn-action btn-delete" onclick="hapusData('<?php echo $sk['no_reg']; ?>')">
                                        Hapus
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endforeach;
                        else:
                        ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <div class="empty-state-icon">📭</div>
                                    <div class="empty-state-title">Belum Ada Data</div>
                                    <div class="empty-state-text">Belum ada surat keputusan yang dibuat</div>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function tambahData() {
            window.location.href = 'tambah_sk.php';
        }

        function editData(noReg) {
            window.location.href = 'edit_sk.php?id=' + noReg;
        }

        function viewSK(noReg) {
            window.location.href = 'view_sk.php?id=' + noReg;
        }

        function hapusData(noReg) {
            if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                window.location.href = '../process/hapus_sk.php?id=' + noReg;
            }
        }

        function ajukanPerpanjangan(noReg) {
            if (confirm('Ajukan perpanjangan untuk SK ini?')) {
                window.location.href = '../process/request_perpanjangan.php?id=' + noReg;
            }
        }

        function logout() {
            if (confirm('Apakah Anda yakin ingin logout?')) {
                window.location.href = '../auth/logout.php';
            }
        }
    </script>
</body>
</html>