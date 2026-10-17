<?php
// Mendeteksi apakah navbar dipanggil dari dalam folder (seperti /mua/) atau root
$path_prefix = (basename(dirname($_SERVER['PHP_SELF'])) == 'mua') ? '../' : '';

$is_logged_in = isset($_SESSION['status_login']) && $_SESSION['status_login'] == true;
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Jika MUA login, ambil data profil singkat langsung di navbar
if ($is_logged_in && $role == 'mua' && isset($koneksi)) {
    $id_mua_nav = $_SESSION['id_user'];
    $nav_query = mysqli_query($koneksi, "SELECT nama_brand, foto_profil FROM users WHERE id_user = '$id_mua_nav'");
    $nav_profil = mysqli_fetch_assoc($nav_query);
}
?>
<header class="navbar">
    <div class="logo">
        <a href="<?php echo $path_prefix; ?>index.php">
            <img src="<?php echo $path_prefix; ?>assets/img/logo.png" alt="GlamLink">
        </a>
    </div>
    
    <?php if (!$is_logged_in): ?>
        <!-- ========================================== -->
        <!-- TAMPILAN BELUM LOGIN (GUEST)               -->
        <!-- ========================================== -->
        <div class="nav-links">
            <a href="<?php echo $path_prefix; ?>login.php" class="btn-text">Masuk</a>
            <a href="<?php echo $path_prefix; ?>login.php" class="btn-outline">Masuk Sebagai MUA</a>
            <a href="<?php echo $path_prefix; ?>register.php" class="btn-filled">Daftar</a>
        </div>

    <?php elseif ($role == 'mua'): ?>
        <!-- ========================================== -->
        <!-- TAMPILAN SUDAH LOGIN (MUA)                 -->
        <!-- ========================================== -->
        <div class="nav-menu-mua">
            <a href="<?php echo $path_prefix; ?>mua/index.php" class="active">Home</a>
            <a href="<?php echo $path_prefix; ?>mua/services.php">Services</a>
            <a href="#">Booking</a>
            <a href="#">Subscription</a>
        </div>
        
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
                    <span class="role-text">MUA</span>
                </div>
            </div>
            <a href="<?php echo $path_prefix; ?>logout.php" class="logout-link">Logout</a>
        </div>
    <?php endif; ?>
</header>