<?php
// Mendeteksi apakah navbar dipanggil dari dalam folder (seperti /mua/) atau root
$path_prefix = in_array(basename(dirname($_SERVER['PHP_SELF'])), ['mua', 'klien']) ? '../' : '';

$nav_logged_in = isset($_SESSION['status_login']) && $_SESSION['status_login'] == true;
$nav_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Jika MUA login, ambil data profil singkat langsung di navbar
if ($nav_logged_in && in_array($nav_role, ['mua', 'pelanggan']) && isset($koneksi)) {
    $id_mua_nav = $_SESSION['id_user'];
    $nav_query = mysqli_query($koneksi, "SELECT nama, nama_brand, foto_profil, status_verifikasi FROM users WHERE id_user = '$id_mua_nav'");
    $nav_profil = mysqli_fetch_assoc($nav_query);
}
?>
<header class="navbar">
    <div class="logo">
        <a href="<?php echo $path_prefix; ?>index.php">
            <img src="<?php echo $path_prefix; ?>assets/img/logo.png" alt="GlamLink">
        </a>
    </div>
    
    <?php if (!$nav_logged_in): ?>
        <!-- ========================================== -->
        <!-- TAMPILAN BELUM LOGIN (GUEST)               -->
        <!-- ========================================== -->
        <div class="nav-links">
            <a href="<?php echo $path_prefix; ?>login.php" class="btn-text">Masuk</a>
            <a href="<?php echo $path_prefix; ?>login.php" class="btn-outline">Masuk Sebagai MUA</a>
            <a href="<?php echo $path_prefix; ?>register.php" class="btn-filled">Daftar</a>
        </div>

    <?php elseif ($nav_role == 'mua'): ?>
        <!-- ========================================== -->
        <!-- TAMPILAN SUDAH LOGIN (MUA)                 -->
        <!-- ========================================== -->
        <?php if (($nav_profil['status_verifikasi'] ?? 'approved') === 'approved'): ?>
        <div class="nav-menu-mua">
            <a href="<?php echo $path_prefix; ?>mua/index.php">Home</a>
            <a href="<?php echo $path_prefix; ?>mua/services.php">Services</a>
            <a href="<?php echo $path_prefix; ?>mua/booking.php">Booking</a>
            <a href="#">Subscription</a>
        </div>
        <?php endif; ?>
        
        <div class="nav-profile-mua">
            <div class="nav-icons">
                <a href="#" class="icon-btn"><i class="fa-solid fa-message"></i><span class="badge">2</span></a>
                <a href="#" class="icon-btn"><i class="fa-solid fa-bell"></i><span class="badge">2</span></a>
            </div>
            <div class="profile-info">
                <?php if(!empty($nav_profil['foto_profil'])): ?>
                    <img src="<?php echo $path_prefix; ?>assets/img/<?php echo htmlspecialchars($nav_profil['foto_profil']); ?>" alt="Profile">
                <?php else: ?>
                    <div style="width:35px;height:35px;background:#e0d8da;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.8rem;"><i class="fa-solid fa-user"></i></div>
                <?php endif; ?>
                <div class="profile-text">
                    <span class="brand-name"><?php echo htmlspecialchars($nav_profil['nama_brand'] ?? 'Nama Brand'); ?></span>
                    <span class="role-text"><?php echo ($nav_profil['status_verifikasi'] ?? 'approved') === 'approved' ? 'MUA' : 'Belum terverifikasi'; ?></span>
                </div>
            </div>
            <a href="<?php echo $path_prefix; ?>logout.php" class="logout-link">Logout</a>
        </div>
    <?php elseif ($nav_role == 'pelanggan'): ?>
        <!-- ========================================== -->
        <!-- TAMPILAN SUDAH LOGIN (KLIEN)               -->
        <!-- ========================================== -->
        <?php $hal = basename($_SERVER['SCRIPT_NAME']); ?>
        <div class="nav-menu-mua">
            <a href="<?php echo $path_prefix; ?>klien/index.php" class="<?php echo $hal == 'index.php' ? 'active' : ''; ?>">Home</a>
            <a href="<?php echo $path_prefix; ?>klien/cari.php" class="<?php echo in_array($hal, ['cari.php', 'mua.php', 'booking.php']) ? 'active' : ''; ?>">Cari MUA</a>
            <a href="<?php echo $path_prefix; ?>klien/history.php" class="<?php echo $hal == 'history.php' ? 'active' : ''; ?>">History</a>
        </div>
        <div class="nav-profile-mua">
            <div class="nav-icons">
                <a href="#" class="icon-btn" title="Segera hadir"><i class="fa-solid fa-message"></i></a>
                <a href="#" class="icon-btn" title="Segera hadir"><i class="fa-solid fa-bell"></i></a>
            </div>
            <a href="<?php echo $path_prefix; ?>klien/profil.php" style="text-decoration:none;color:inherit;">
                <div class="profile-info">
                    <?php if (!empty($nav_profil['foto_profil']) && file_exists(__DIR__ . '/assets/img/' . basename($nav_profil['foto_profil']))): ?>
                        <img src="<?php echo $path_prefix; ?>assets/img/<?php echo htmlspecialchars(basename($nav_profil['foto_profil'])); ?>" alt="Profile">
                    <?php else: ?>
                        <div style="width:35px;height:35px;background:#e0d8da;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.8rem;"><i class="fa-solid fa-user"></i></div>
                    <?php endif; ?>
                    <div class="profile-text">
                        <span class="brand-name"><?php echo htmlspecialchars($nav_profil['nama'] ?? 'Klien'); ?></span>
                        <span class="role-text">Klien</span>
                    </div>
                </div>
            </a>
            <a href="<?php echo $path_prefix; ?>logout.php" class="logout-link">Logout</a>
        </div>
    <?php endif; ?>
</header>