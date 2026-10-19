<?php
session_start();
require __DIR__ . '/../Includes/klien.php';

$id  = (int)($_GET['id'] ?? 0);
$tgl = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['tanggal'] ?? '') ? $_GET['tanggal'] : '';
$m = q("SELECT * FROM users WHERE id_user = ? AND role = 'mua' AND status_verifikasi = 'approved'", [$id])[0] ?? null;

head_klien($m['nama_brand'] ?? 'MUA tidak ditemukan');
include __DIR__ . '/../navbar.php';
if (!$m) { echo '<main class="kl-wrap page-top"><div class="kosong"><i class="fa-regular fa-face-frown"></i><p>MUA tidak ditemukan atau belum terverifikasi.</p><a class="btn-cari" href="cari.php">Kembali ke Cari MUA</a></div></main>'; bottom_nav(); exit(); }

$layanan = q('SELECT * FROM services WHERE id_mua = ? ORDER BY harga', [$id]);
$porto   = q('SELECT foto FROM portfolios WHERE id_mua = ? ORDER BY id_portfolio DESC', [$id]);
$selesai = q("SELECT COUNT(*) AS n FROM bookings WHERE id_mua = ? AND status = 'selesai'", [$id])[0]['n'] ?? 0;
?>
<main class="kl-wrap page-top">
    <div class="profil-head">
        <?php echo foto($m['foto_profil'], 'avatar xl'); ?>
        <div>
            <h1><?php echo e($m['nama_brand']); ?> <i class="fa-solid fa-circle-check verif" title="Terverifikasi"></i></h1>
            <p class="muted"><i class="fa-solid fa-location-dot"></i> <?php echo e($m['lokasi']); ?> &middot; <?php echo (int)$selesai; ?> acara selesai</p>
            <p class="bio"><?php echo nl2br(e($m['bio'])); ?></p>
        </div>
    </div>

    <h2 class="kl-title">Layanan</h2>
    <div class="layanan-list">
        <?php foreach ($layanan as $s): $fas = json_decode((string)$s['fasilitas'], true) ?: []; ?>
            <div class="layanan-item">
                <div class="info">
                    <h3><?php echo e($s['nama_layanan']); ?> <span class="chip">#<?php echo e($s['kategori']); ?></span></h3>
                    <p class="muted"><?php echo e($s['deskripsi']); ?><?php echo $s['durasi'] ? ' &middot; ' . e($s['durasi']) : ''; ?></p>
                    <?php if ($fas): ?><ul class="fasilitas"><?php foreach ($fas as $f) echo '<li><i class="fa-solid fa-check"></i> ' . e($f) . '</li>'; ?></ul><?php endif; ?>
                </div>
                <div class="aksi"><b><?php echo rupiah($s['harga']); ?></b>
                    <a class="btn-cari" href="booking.php?layanan=<?php echo (int)$s['id_service']; ?><?php echo $tgl ? '&tanggal=' . urlencode($tgl) : ''; ?>">Booking</a></div>
            </div>
        <?php endforeach; ?>
        <?php if (!$layanan) echo '<p class="muted">MUA ini belum menambahkan layanan.</p>'; ?>
    </div>

    <h2 class="kl-title">Portfolio</h2>
    <div class="galeri"><?php foreach ($porto as $f) echo '<div>' . foto($f['foto'], 'porto-img', 'fa-image') . '</div>'; ?>
        <?php if (!$porto) echo '<p class="muted">Belum ada portfolio.</p>'; ?></div>
</main>
<?php bottom_nav();