<?php
// Ambil halaman aktif untuk highlight menu
$current_page = basename($_SERVER['PHP_SELF']);

// Ambil data user lengkap untuk foto profil
$user_data_sidebar = getUserData($_SESSION['user_id'], $conn);
?>

<!-- Sidebar Toggle (Mobile) -->
<button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="../assets/images/bpr.png" class="sidebar-logo" style="width: 100%; height: auto;">
        <h2>Sistem SK</h2>
    </div>

    <div class="sidebar-menu">
        <div class="menu-section">MENU UTAMA</div>
        <a href="<?php echo BASE_URL; ?>pages/dashboard.php" 
           class="menu-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <span class="menu-icon">🏠</span>
            <span>Dashboard</span>
        </a>
        <a href="<?php echo BASE_URL; ?>pages/data_sk.php" 
           class="menu-item <?php echo ($current_page == 'data_sk.php') ? 'active' : ''; ?>">
            <span class="menu-icon">📄</span>
            <span>Data Surat Keputusan</span>
        </a>
        <a href="<?php echo BASE_URL; ?>pages/report_sk.php" 
           class="menu-item <?php echo ($current_page == 'report_sk.php') ? 'active' : ''; ?>">
            <span class="menu-icon">📊</span>
            <span>Report Surat Keputusan</span>
        </a>

        <div class="menu-section">LAINNYA</div>
        <a href="<?php echo BASE_URL; ?>pages/profile.php" 
           class="menu-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
            <span class="menu-icon">👤</span>
            <span>Profil Saya</span>
        </a>
    </div>

    <div class="sidebar-footer">
        <div class="user-profile">
            <?php 
            $photo_path = dirname(__DIR__) . '/uploads/profiles/' . $user_data_sidebar['profile_photo'];
            if (!empty($user_data_sidebar['profile_photo']) && file_exists($photo_path)): 
            ?>
                <img src="<?php echo BASE_URL; ?>uploads/profiles/<?php echo htmlspecialchars($user_data_sidebar['profile_photo']); ?>?v=<?php echo time(); ?>" 
                     alt="Profile" class="user-avatar user-avatar-img">
            <?php else: ?>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                </div>
            <?php endif; ?>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                <div class="user-role"><?php echo ucfirst($_SESSION['role']); ?></div>
            </div>
        </div>
    </div>
</div>