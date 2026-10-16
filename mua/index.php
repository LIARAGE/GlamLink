<?php
session_start();
require '../koneksi.php';

// Cek otentikasi
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'mua') {
    header("Location: ../login.php");
    exit();
}

$id_mua = $_SESSION['id_user']; 

// 1. Ambil Profil MUA
$query_profil = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_mua'");
$profil = mysqli_fetch_assoc($query_profil);

// 2. Ambil Statistik Pesanan
// Total Pesanan
$q_total = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM bookings WHERE id_mua = '$id_mua'");
$stat_total = mysqli_fetch_assoc($q_total)['total'];

// Permintaan Menunggu
$q_menunggu = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM bookings WHERE id_mua = '$id_mua' AND status = 'menunggu'");
$stat_menunggu = mysqli_fetch_assoc($q_menunggu)['total'];

// Acara Selesai
$q_selesai = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM bookings WHERE id_mua = '$id_mua' AND status = 'selesai'");
$stat_selesai = mysqli_fetch_assoc($q_selesai)['total'];

// Total Pendapatan
$q_pendapatan = mysqli_query($koneksi, "SELECT SUM(total_harga) as total FROM bookings WHERE id_mua = '$id_mua' AND status = 'selesai'");
$stat_pendapatan = mysqli_fetch_assoc($q_pendapatan)['total'] ?? 0;

// 3. Ambil Daftar Pesanan Terbaru (Menunggu Konfirmasi)
// Asumsi kita melakukan JOIN dengan tabel users (untuk nama klien) dan services (untuk detail layanan)
$query_bookings = mysqli_query($koneksi, "
    SELECT b.*, u.nama AS nama_klien, u.foto_profil AS foto_klien, s.nama_layanan 
    FROM bookings b 
    JOIN users u ON b.id_pelanggan = u.id_user 
    JOIN services s ON b.id_layanan = s.id_layanan 
    WHERE b.id_mua = '$id_mua' AND b.status = 'menunggu'
    ORDER BY b.created_at DESC LIMIT 5
");

$bookings = [];
while ($row = mysqli_fetch_assoc($query_bookings)) {
    $bookings[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard MUA - GlamLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- File CSS Khusus Dashboard MUA (Untuk Frontend Dev) -->
    <link rel="stylesheet" href="../assets/css/mua_dashboard.css">
</head>
<body>

    <!-- NAVBAR KHUSUS MUA -->
    <nav class="mua-navbar">
        <div class="nav-brand">
            <a href="index.php"><img src="../assets/img/logo.png" alt="GlamLink"></a>
        </div>
        <div class="nav-menu">
            <a href="index.php" class="active">Home</a>
            <a href="#">Services</a>
            <a href="#">Booking</a>
            <a href="#">Subscription</a>
        </div>
        <div class="nav-profile">
            <div class="nav-icons">
                <a href="#" class="icon-btn"><i class="fa-solid fa-message"></i><span class="badge">2</span></a>
                <a href="#" class="icon-btn"><i class="fa-solid fa-bell"></i><span class="badge">2</span></a>
            </div>
            <div class="profile-info">
                <img src="../assets/img/<?php echo htmlspecialchars($profil['foto_profil'] ?? 'default.jpg'); ?>" alt="Profile">
                <div class="profile-text">
                    <span class="brand-name"><?php echo htmlspecialchars($profil['nama_brand']); ?></span>
                    <span class="role-text">MUA</span>
                </div>
            </div>
            <a href="../logout.php" class="logout-link">Logout</a>
        </div>
    </nav>

    <main class="dashboard-main">
        
        <!-- HEADER HERO -->
        <section class="hero-header">
            <h2>Selamat Datang , <span><?php echo htmlspecialchars($profil['nama_brand']); ?></span></h2>
            <h1>Saatnya Karyamu Dikenal Lebih Banyak Orang</h1>
            <p>Terima booking dari pelanggan baru, atur jadwalmu dengan mudah, dan fokus memberikan hasil makeup terbaik tanpa ribet mengelola pesanan.</p>
        </section>

        <!-- STATISTIK CARDS -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span>Total Pesanan Masuk</span>
                    <i class="fa-regular fa-calendar" style="color:#7E4655; background:#F4E2E6; padding:8px; border-radius:50%;"></i>
                </div>
                <h3><?php echo $stat_total; ?></h3>
                <span class="stat-desc text-success">+3 dari bulan lalu</span>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <span>Permintaan Menunggu</span>
                    <i class="fa-solid fa-exclamation" style="color:#D97706; background:#FEF3C7; padding:8px 12px; border-radius:50%;"></i>
                </div>
                <h3 style="color:#D97706;"><?php echo $stat_menunggu; ?></h3>
                <span class="stat-desc">Konfirmasi dalam 24 jam</span>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <span>Acara Selesai</span>
                    <i class="fa-solid fa-check" style="color:#059669; background:#D1FAE5; padding:8px 10px; border-radius:50%;"></i>
                </div>
                <h3 style="color:#059669;"><?php echo $stat_selesai; ?></h3>
                <span class="stat-desc">100% ulasan bintang 5</span>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <span>Total Pendapatan</span>
                    <i class="fa-solid fa-arrow-trend-up" style="color:#7E4655; background:#F4E2E6; padding:8px; border-radius:50%;"></i>
                </div>
                <h3 style="color:#7E4655;">Rp <?php echo number_format($stat_pendapatan, 0, ',', '.'); ?></h3>
                <span class="stat-desc text-success">Penyaluran langsung via GlamLink</span>
            </div>
        </section>

        <!-- DAFTAR PESANAN TERBARU -->
        <section class="recent-bookings">
            <div class="section-header">
                <div>
                    <h3>Permintaan Pesanan Terbaru</h3>
                    <p>Tinjau, terima, atau tolak jadwal pesanan masuk dari calon klien Anda.</p>
                </div>
                <a href="#" class="view-all">Lihat Semua (<?php echo $stat_total; ?>) &rarr;</a>
            </div>

            <div class="booking-list">
                <?php if (count($bookings) > 0): ?>
                    <?php foreach ($bookings as $bk): ?>
                        <div class="booking-item">
                            <div class="client-info">
                                <img src="../assets/img/<?php echo htmlspecialchars($bk['foto_klien'] ?? 'default.jpg'); ?>" alt="Klien">
                                <div class="client-details">
                                    <h4><?php echo htmlspecialchars($bk['nama_klien']); ?> <span class="invoice-badge"><?php echo htmlspecialchars($bk['no_invoice']); ?></span></h4>
                                    <p class="service-name"><?php echo htmlspecialchars($bk['nama_layanan']); ?></p>
                                    <p class="booking-meta">
                                        <i class="fa-regular fa-calendar"></i> <?php echo date('d/m/Y', strtotime($bk['tanggal_booking'])); ?> &nbsp;&middot;&nbsp; 
                                        <i class="fa-regular fa-clock"></i> <?php echo date('H:i', strtotime($bk['waktu_booking'])); ?> WIB &nbsp;&middot;&nbsp; 
                                        <span class="price-bold">Rp <?php echo number_format($bk['total_harga'], 0, ',', '.'); ?></span>
                                    </p>
                                </div>
                            </div>
                            <div class="booking-actions">
                                <!-- Tombol aksi form backend menyusul -->
                                <button class="btn-confirm"><i class="fa-solid fa-check"></i> Konfirmasi</button>
                                <button class="btn-reject"><i class="fa-solid fa-xmark"></i> Tolak</button>
                                <button class="btn-view"><i class="fa-regular fa-eye"></i></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty-state" style="text-align:center; padding: 30px; color:#8c8286;">Tidak ada permintaan pesanan baru.</p>
                <?php endif; ?>
            </div>
        </section>

    </main>
</body>
</html>