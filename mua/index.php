<?php
session_start();
require '../koneksi.php';

// 1. OTENTIKASI
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'mua') {
    header("Location: ../login.php");
    exit();
}

$id_mua = $_SESSION['id_user']; 

// 2. FETCH PROFIL MUA
$query_profil = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_mua'") or die('Error Profil');
$profil = mysqli_fetch_assoc($query_profil);

// 3. FETCH STATISTIK PESANAN (DENGAN LOGIKA DINAMIS)
$q_total = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM bookings WHERE id_mua = '$id_mua'");
$stat_total = mysqli_fetch_assoc($q_total)['total'] ?? 0;

// Logika Dinamis: Pesanan bulan ini vs bulan lalu
$current_month = date('m');
$current_year = date('Y');
$last_month = date('m', strtotime('-1 month'));
$last_month_year = date('Y', strtotime('-1 month'));

$q_this_month = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM bookings WHERE id_mua = '$id_mua' AND MONTH(created_at) = '$current_month' AND YEAR(created_at) = '$current_year'");
$this_month_total = mysqli_fetch_assoc($q_this_month)['total'] ?? 0;

$q_last_month = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM bookings WHERE id_mua = '$id_mua' AND MONTH(created_at) = '$last_month' AND YEAR(created_at) = '$last_month_year'");
$last_month_total = mysqli_fetch_assoc($q_last_month)['total'] ?? 0;

$diff = $this_month_total - $last_month_total;
$trend_sign = $diff >= 0 ? "+" : "";
$trend_text = "{$trend_sign}{$diff} dari bulan lalu";
$trend_class = $diff >= 0 ? "text-success" : "text-danger";

// Statistik Lainnya
$q_menunggu = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM bookings WHERE id_mua = '$id_mua' AND status = 'menunggu'");
$stat_menunggu = mysqli_fetch_assoc($q_menunggu)['total'] ?? 0;

$q_selesai = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM bookings WHERE id_mua = '$id_mua' AND status = 'selesai'");
$stat_selesai = mysqli_fetch_assoc($q_selesai)['total'] ?? 0;

$q_pendapatan = mysqli_query($koneksi, "SELECT SUM(total_harga) as total FROM bookings WHERE id_mua = '$id_mua' AND status = 'selesai'");
$stat_pendapatan = mysqli_fetch_assoc($q_pendapatan)['total'] ?? 0;

// 4. FETCH DAFTAR PESANAN TERBARU (Tambah nomor telepon, lokasi, dan catatan)
$query_bookings = mysqli_query($koneksi, "
    SELECT b.*, u.nama AS nama_klien, u.foto_profil AS foto_klien, u.nomor_telepon, s.nama_layanan 
    FROM bookings b 
    LEFT JOIN users u ON b.id_pelanggan = u.id_user 
    LEFT JOIN services s ON b.id_layanan = s.id_service 
    WHERE b.id_mua = '$id_mua' AND b.status = 'menunggu'
    ORDER BY b.created_at DESC LIMIT 5
");

$bookings = [];
if ($query_bookings) {
    while ($row = mysqli_fetch_assoc($query_bookings)) {
        $bookings[] = $row;
    }
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
    
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/mua_dashboard.css">
</head>
<body>

    <!-- Memanggil Navbar Terpusat -->
    <?php include '../navbar.php'; ?>

    <main class="dashboard-main">
        
        <section class="hero-header">
            <h2>Selamat Datang , <span><?php echo htmlspecialchars($profil['nama_brand'] ?? 'MUA'); ?></span></h2>
            <h1>Saatnya Karyamu Dikenal Lebih Banyak Orang</h1>
            <p>Terima booking dari pelanggan baru, atur jadwalmu dengan mudah, dan fokus memberikan hasil makeup terbaik tanpa ribet mengelola pesanan.</p>
        </section>

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span>Total Pesanan Masuk</span>
                    <i class="fa-regular fa-calendar"></i>
                </div>
                <h3><?php echo $stat_total; ?></h3>
                <span class="stat-desc <?php echo $trend_class; ?>"><?php echo $trend_text; ?></span>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <span>Permintaan Menunggu</span>
                    <i class="fa-solid fa-exclamation"></i>
                </div>
                <h3 class="text-warning"><?php echo $stat_menunggu; ?></h3>
                <span class="stat-desc">Konfirmasi dalam 24 jam</span>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <span>Acara Selesai</span>
                    <i class="fa-solid fa-check"></i>
                </div>
                <h3 class="text-success"><?php echo $stat_selesai; ?></h3>
                <span class="stat-desc">100% ulasan bintang 5</span>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <span>Total Pendapatan</span>
                    <i class="fa-solid fa-arrow-trend-up"></i>
                </div>
                <h3 class="text-primary">Rp <?php echo number_format($stat_pendapatan, 0, ',', '.'); ?></h3>
                <span class="stat-desc text-success">Penyaluran langsung via GlamLink</span>
            </div>
        </section>

        <section class="recent-bookings">
            <div class="section-header">
                <div>
                    <h3>Permintaan Pesanan Terbaru</h3>
                    <p>Tinjau, terima, atau tolak jadwal pesanan masuk dari calon klien Anda.</p>
                </div>
                <a href="#" class="view-all">Lihat Semua (<?php echo $stat_menunggu; ?>) &rarr;</a>
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
                                <button class="btn-confirm"><i class="fa-solid fa-check"></i> Konfirmasi</button>
                                <button class="btn-reject"><i class="fa-solid fa-xmark"></i> Tolak</button>
                                <!-- Tombol ini sekarang memicu JavaScript openModal -->
                                <button class="btn-view" onclick="openModal('modal-<?php echo $bk['id_booking']; ?>')"><i class="fa-regular fa-eye"></i></button>
                            </div>
                        </div>

                        <!-- ============================================== -->
                        <!-- POPUP MODAL (DETAIL KLIEN) UNTUK SETIAP PESANAN -->
                        <!-- ============================================== -->
                        <div id="modal-<?php echo $bk['id_booking']; ?>" class="modal-overlay">
                            <div class="modal-box">
                                <button class="close-modal" onclick="closeModal('modal-<?php echo $bk['id_booking']; ?>')"><i class="fa-solid fa-xmark"></i></button>
                                
                                <div class="modal-header-text">
                                    <span class="inv-number"><?php echo htmlspecialchars($bk['no_invoice']); ?></span>
                                    <h2>Client Details</h2>
                                </div>

                                <div class="modal-section client-card">
                                    <img src="../assets/img/<?php echo htmlspecialchars($bk['foto_klien'] ?? 'default.jpg'); ?>" alt="Klien">
                                    <div>
                                        <h3><?php echo htmlspecialchars($bk['nama_klien']); ?></h3>
                                        <p><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($bk['nomor_telepon'] ?? 'Belum ada nomor'); ?></p>
                                    </div>
                                </div>

                                <div class="modal-section detail-card">
                                    <div class="detail-row">
                                        <span class="label">Layanan:</span>
                                        <span class="value bold"><?php echo htmlspecialchars($bk['nama_layanan']); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="label">Tanggal & Jam:</span>
                                        <span class="value bold"><?php echo date('d/m/Y', strtotime($bk['tanggal_booking'])); ?> &middot; <?php echo date('H:i', strtotime($bk['waktu_booking'])); ?> WIB</span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="label">Tarif:</span>
                                        <span class="value price">Rp <?php echo number_format($bk['total_harga'], 0, ',', '.'); ?></span>
                                    </div>
                                </div>

                                <div class="modal-section location-card">
                                    <span class="label">Lokasi Acara:</span>
                                    <p class="value"><?php echo nl2br(htmlspecialchars($bk['lokasi_acara'] ?? 'Lokasi belum ditentukan')); ?></p>
                                </div>

                                <div class="modal-section notes-card">
                                    <span class="label">Catatan Klien:</span>
                                    <p class="value italic">"<?php echo nl2br(htmlspecialchars($bk['catatan'] ?? 'Tidak ada catatan khusus.')); ?>"</p>
                                </div>

                                <div class="modal-footer-actions">
                                    <button class="btn-modal-confirm">Terima & Konfirmasi</button>
                                    <button class="btn-modal-reject">Tolak Permintaan</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty-state">Tidak ada permintaan pesanan baru.</p>
                <?php endif; ?>
            </div>
        </section>

    </main>

    <!-- JavaScript untuk membuka dan menutup modal -->
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Menutup modal jika area gelap di luar kotak diklik
        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = "none";
            }
        }
    </script>
</body>
</html>