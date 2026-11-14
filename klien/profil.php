<?php
session_start();
require __DIR__ . '/../includes/klien.php';
require __DIR__ . '/../includes/profil_klien.php';

$u   = q('SELECT nama, email, nomor_telepon, lokasi, bio, foto_profil, dibuat_pada FROM users WHERE id_user = ?', [$id_klien])[0];
$st  = q("SELECT COUNT(*) AS total, COALESCE(SUM(status = 'selesai'), 0) AS selesai FROM bookings WHERE id_pelanggan = ?", [$id_klien])[0];
$fav = daftar_favorit($id_klien);
$notif = pesan_profil(is_string($_GET['msg'] ?? null) ? $_GET['msg'] : '', is_string($_GET['info'] ?? null) ? $_GET['info'] : '');
$gabung = NAMA_BULAN[(int)date('n', strtotime($u['dibuat_pada']))] . ' ' . date('Y', strtotime($u['dibuat_pada']));

/** Satu kotak informasi (baca-saja). $fokus = id kolom di halaman Edit yang langsung difokuskan; null = tidak bisa diubah */
function kotak_info($label, $nilai, $ikon, $fokus) {
    $isi = trim((string)$nilai) !== '' ? e($nilai) : '<span class="muted">Belum diisi</span>';
    echo '<div class="pr-item"><span class="pr-lbl">' . e($label) . '</span>';
    if ($fokus) echo '<a class="pr-box" href="profil_edit.php?fokus=' . $fokus . '"><i class="' . $ikon . ' pr-ic"></i><span>' . $isi . '</span><i class="fa-solid fa-pencil pr-pen"></i></a>';
    else echo '<div class="pr-box pr-lock" title="Email tidak dapat diubah"><i class="' . $ikon . ' pr-ic"></i><span>' . $isi . '</span><i class="fa-solid fa-lock pr-pen"></i></div>';
    echo '</div>';
}

head_klien('Profil');
include __DIR__ . '/../navbar.php';
?>
<main class="kl-wrap page-top pr-page">
    <?php if ($notif) echo '<p class="alert-' . $notif[0] . '" role="status">' . e($notif[1]) . '</p>'; ?>

    <section class="pr-head">
        <?php echo foto($u['foto_profil'], 'avatar xl'); ?>
        <div class="pr-head-info">
            <h1><?php echo e($u['nama']); ?></h1>
            <p class="pr-bio"><?php echo trim((string)$u['bio']) !== '' ? nl2br(e($u['bio'])) : '<span class="muted">Belum ada bio.</span>'; ?></p>
            <p class="pr-stat"><span><b><?php echo (int)$st['total']; ?></b> booking</span><span><b><?php echo (int)$st['selesai']; ?></b> selesai</span><span><b><?php echo count($fav); ?></b> favorit</span><span class="muted">Bergabung <?php echo e($gabung); ?></span></p>
        </div>
        <a class="pr-edit-btn" href="profil_edit.php"><i class="fa-solid fa-pen-to-square"></i> Edit Profil</a>
    </section>

    <div class="pr-grid">
        <section class="pr-card">
            <h2>Informasi Pribadi</h2>
            <?php kotak_info('Email', $u['email'], 'fa-regular fa-envelope', null);
            kotak_info('No Telepon', $u['nomor_telepon'], 'fa-solid fa-phone', 'telp');
            kotak_info('Alamat', $u['lokasi'], 'fa-solid fa-location-dot', 'alamat'); ?>
            <a class="pr-link" href="profil_edit.php#password"><i class="fa-solid fa-key"></i> Ganti Password</a>
        </section>

        <section class="pr-card">
            <h2>MUA Favorit</h2>
            <?php foreach ($fav as $m) kartu_favorit($m, 'profil'); ?>
            <?php if (!$fav): ?>
                <div class="kosong pr-kosong"><i class="fa-regular fa-heart"></i><p>Belum ada MUA favorit.<br><span class="small">Tekan ikon hati di profil MUA untuk menyimpannya di sini.</span></p><a class="kb-fill" href="cari.php">Cari MUA</a></div>
            <?php endif; ?>
        </section>
    </div>
</main>
<?php bottom_nav();
