<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check login
requireLogin();

// Ambil ID dari URL
$no_reg = isset($_GET['id']) ? clean($_GET['id']) : '';
if (empty($no_reg)) {
    header("Location: data_sk.php");
    exit;
}

// Ambil data SK
$sk_data = getSKData($no_reg, $conn);
if (!$sk_data) {
    header("Location: data_sk.php?error=notfound");
    exit;
}

// Ambil komentar
$comments = getComments($no_reg, $conn);

// Ambil data user
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View SK - <?php echo $sk_data['no_reg']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <style>
        .content-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .pdf-container {
            width: 100%;
            height: 700px;
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid #e2e8f0;
            background: #f7fafc;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pdf-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .pdf-placeholder {
            text-align: center;
            color: #a0aec0;
        }

        .pdf-placeholder-icon {
            font-size: 64px;
            margin-bottom: 15px;
        }

        .sk-info {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .info-label {
            color: #718096;
            font-size: 13px;
            font-weight: 600;
        }

        .info-value {
            color: #2d3748;
            font-size: 15px;
            font-weight: 500;
        }

        .btn-approve, .btn-reject {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            margin-top: 10px;
        }

        .btn-approve {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
        }

        .btn-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(72, 187, 120, 0.4);
        }

        .btn-reject {
            background: linear-gradient(135deg, #f56565, #e53e3e);
            color: white;
        }

        .btn-reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(245, 101, 101, 0.4);
        }

        .btn-download {
            background: #667eea;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            width: 100%;
            margin-top: 10px;
        }

        .btn-download:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .comments-section {
            margin-top: 25px;
        }

        .comments-header {
            font-size: 16px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .comments-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 20px;
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .comment-item {
            background: #f7fafc;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .comment-author {
            font-weight: 600;
            color: #2d3748;
            font-size: 14px;
        }

        .comment-date {
            color: #a0aec0;
            font-size: 12px;
        }

        .comment-text {
            color: #4a5568;
            font-size: 14px;
            line-height: 1.6;
        }

        .no-comments {
            text-align: center;
            padding: 30px;
            color: #a0aec0;
            font-style: italic;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        @media (max-width: 1024px) {
            .content-layout {
                grid-template-columns: 1fr;
            }
            .pdf-container {
                height: 500px;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-wrapper">
        <!-- Header --><div class="header">
            <div class="header-content">
                <div class="header-left">
                    <a href="data_sk.php" class="btn-back">
                        ← Kembali
                    </a>
                    <h1>Detail Surat Keputusan</h1>
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
            if ($_GET['success'] === 'comment') echo '✓ Komentar berhasil ditambahkan!';
            if ($_GET['success'] === 'approved') echo '✓ SK berhasil disetujui!';
            if ($_GET['success'] === 'rejected') echo '✓ SK dikembalikan untuk revisi!';
            ?>
        </div>
        <?php endif; ?>

        <!-- Content Layout -->
        <div class="content-layout">
            <!-- PDF Viewer -->
            <div class="content-card">
                <div class="card-header">
                    <div class="card-title">📄 Preview Dokumen</div>
                </div>
                <div class="pdf-container">
                    <?php if (!empty($sk_data['signed_file']) && $sk_data['status'] === 'Disetujui'): ?>
                        <iframe src="<?php echo UPLOAD_URL . $sk_data['signed_file']; ?>"></iframe>
                    <?php elseif (!empty($sk_data['file'])): ?>
                        <iframe src="<?php echo UPLOAD_URL . $sk_data['file']; ?>"></iframe>
                    <?php else: ?>
                        <div class="pdf-placeholder">
                            <div class="pdf-placeholder-icon">📄</div>
                            <p>File PDF tidak tersedia</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SK Info & Actions -->
            <div>
                <div class="content-card">
                    <div class="card-header">
                        <div class="card-title">ℹ️ Informasi SK</div>
                    </div>
                    <div class="sk-info">
                        <div class="info-item">
                            <div class="info-label">No Register</div>
                            <div class="info-value"><?php echo $sk_data['no_reg']; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Nomor SK</div>
                            <div class="info-value"><?php echo $sk_data['no_sk']; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Perihal</div>
                            <div class="info-value"><?php echo $sk_data['hal']; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Tanggal SK</div>
                            <div class="info-value"><?php echo formatTanggal($sk_data['tgl']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value">
                                <span class="status-badge status-<?php echo strtolower($sk_data['status']); ?>">
                                    <?php echo $sk_data['status']; ?>
                                </span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Dibuat Oleh</div>
                            <div class="info-value"><?php echo $sk_data['creator_name']; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Dibuat Pada</div>
                            <div class="info-value"><?php echo formatTanggal($sk_data['created_at'], 'd F Y H:i'); ?></div>
                        </div>
                    </div>

                    <?php if ($role === 'admin' && $sk_data['status'] !== 'Disetujui'): ?>
                    <form method="POST" action="../process/acc_sk.php">
                        <input type="hidden" name="no_reg" value="<?php echo $sk_data['no_reg']; ?>">
                        <button type="submit" name="acc_sk" class="btn-approve" 
                                onclick="return confirm('Apakah Anda yakin ingin menyetujui SK ini?')">
                            ✓ ACC / Setujui
                        </button>
                    </form>
                    <form method="POST" action="../process/reject_sk.php">
                        <input type="hidden" name="no_reg" value="<?php echo $sk_data['no_reg']; ?>">
                        <button type="submit" name="reject_sk" class="btn-reject" 
                                onclick="return confirm('Apakah Anda yakin ingin mengembalikan SK ini untuk revisi?')">
                            ✗ Tolak / Revisi
                        </button>
                    </form>
                    <?php endif; ?>

                    <?php if (!empty($sk_data['file'])): ?>
                    <button class="btn-download" onclick="downloadPDF()">
                        📥 Download PDF
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Comments Section -->
                <div class="content-card" style="margin-top: 20px;">
                    <div class="comments-header">
                        💬 Komentar & Catatan Revisi
                    </div>

                    <div class="comments-list">
                        <?php if (count($comments) > 0): ?>
                            <?php foreach ($comments as $comment): ?>
                            <div class="comment-item">
                                <div class="comment-header">
                                    <div class="comment-author">👤 <?php echo $comment['admin_username']; ?></div>
                                    <div class="comment-date"><?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?></div>
                                </div>
                                <div class="comment-text"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-comments">Belum ada komentar</div>
                        <?php endif; ?>
                    </div>

                    <?php if ($role === 'admin'): ?>
                    <form method="POST" action="../process/add_comment.php" class="comment-form">
                        <input type="hidden" name="sk_id" value="<?php echo $sk_data['no_reg']; ?>">
                        <textarea name="comment_text" class="form-textarea" 
                                  placeholder="Tulis komentar atau catatan revisi untuk user..." required></textarea>
                        <button type="submit" name="submit_comment" class="btn-submit">
                            📝 Kirim Komentar
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function downloadPDF() {
            window.open('<?php echo UPLOAD_URL . ($sk_data['status'] === 'Disetujui' && !empty($sk_data['signed_file']) ? $sk_data['signed_file'] : $sk_data['file']); ?>', '_blank');
        }

        function logout() {
            if (confirm('Apakah Anda yakin ingin logout?')) {
                window.location.href = '../auth/logout.php';
            }
        }
    </script>
</body>
</html>