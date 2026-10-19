<?php
session_start();
require __DIR__ . '/../Includes/klien.php';

// Label disamakan dengan halaman Booking MUA (mua/booking.php)
$status = ['menunggu' => 'Menunggu Konfirmasi', 'dikonfirmasi' => 'Terkonfirmasi', 'selesai' => 'Selesai', 'ditolak' => 'Dibatalkan'];
$tab = isset($status[$_GET['tab'] ?? '']) ? $_GET['tab'] : '';
$sql = "SELECT b.*, s.nama_layanan, u.nama_brand, u.foto_profil FROM bookings b
        JOIN services s ON s.id_service = b.id_layanan JOIN users u ON u.id_user = b.id_mua
        WHERE b.id_pelanggan = ?" . ($tab ? ' AND b.status = ?' : '') . ' ORDER BY b.tanggal_booking DESC, b.id_booking DESC';
$data = q($sql, $tab ? [$id_klien, $tab] : [$id_klien]);

head_klien('History');
include __DIR__ . '/../navbar.php';
?>
<main class="kl-wrap page-top">
    <h1 class="kl-title big">History Pesanan</h1>
    <?php if (isset($_GET['ok'])) echo '<p class="alert-ok"><i class="fa-solid fa-circle-check"></i> Permintaan booking <b>' . e($_GET['ok']) . '</b> terkirim. Menunggu konfirmasi MUA.</p>'; ?>
    <div class="tabs"><a href="history.php" class="<?php echo $tab ? '' : 'on'; ?>">Semua</a>
        <?php foreach ($status as $k => $l) echo '<a href="?tab=' . $k . '" class="' . ($tab === $k ? 'on' : '') . '">' . $l . '</a>'; ?></div>

    <div class="order-list">
        <?php foreach ($data as $b): ?>
            <div class="order-card">
                <div class="order-top"><span class="inv"><?php echo e($b['no_invoice']); ?></span><span class="badge-status s-<?php echo e($b['status']); ?>"><?php echo $status[$b['status']] ?? e($b['status']); ?></span></div>
                <div class="order-mua"><?php echo foto($b['foto_profil'], 'avatar'); ?>
                    <div><h3><?php echo e($b['nama_brand']); ?></h3><span class="muted"><?php echo e($b['nama_layanan']); ?></span></div></div>
                <div class="order-meta">
                    <span><i class="fa-regular fa-calendar"></i> <?php echo date('d/m/Y', strtotime($b['tanggal_booking'])) . ', ' . substr($b['waktu_booking'], 0, 5) . ' WIB'; ?></span>
                    <span><i class="fa-solid fa-location-dot"></i> <?php echo e($b['lokasi_acara'] ?: '-'); ?></span>
                    <b><?php echo rupiah($b['total_harga']); ?></b>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if (!$data): ?><div class="kosong"><i class="fa-regular fa-calendar-xmark"></i><p>Belum ada pesanan.</p><a class="btn-cari" href="cari.php">Cari MUA</a></div><?php endif; ?>
</main>
<?php bottom_nav();