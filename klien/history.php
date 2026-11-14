<?php
session_start();
require __DIR__ . '/../includes/klien.php';
require __DIR__ . '/../includes/riwayat.php';

aksi_booking($id_klien);   // proses batal / konfirmasi selesai / review (lalu redirect)

/* ---------- Tab aktif & jumlah per tab ---------- */
$tab    = tab_valid(is_string($_GET['tab'] ?? null) ? $_GET['tab'] : '');
$status = TAB_RIWAYAT[$tab][1];
$jml = [];
foreach (q('SELECT status, COUNT(*) AS n FROM bookings WHERE id_pelanggan = ? GROUP BY status', [$id_klien]) as $r) { $jml[$r['status']] = (int)$r['n']; }

// Urutan per tab: pending = terbaru dibuat; upcoming = jadwal terdekat dulu; lainnya = terbaru dulu
$urut = ['menunggu' => 'b.created_at DESC, b.id_booking DESC', 'dikonfirmasi' => 'b.tanggal_booking ASC, b.waktu_booking ASC, b.id_booking ASC',
         'selesai' => 'b.tanggal_booking DESC, b.id_booking DESC', 'ditolak' => 'b.tanggal_booking DESC, b.id_booking DESC'][$status];
$data = q("SELECT b.*, s.nama_layanan, u.nama_brand, u.foto_profil, u.nomor_telepon
           FROM bookings b JOIN services s ON s.id_service = b.id_layanan JOIN users u ON u.id_user = b.id_mua
           WHERE b.id_pelanggan = ? AND b.status = ? ORDER BY $urut", [$id_klien, $status]);

$notif = pesan_riwayat(is_string($_GET['msg'] ?? null) ? $_GET['msg'] : '');
$kosong = ['pending' => 'Belum ada booking yang menunggu konfirmasi.', 'upcoming' => 'Belum ada booking yang akan datang.',
           'completed' => 'Belum ada booking yang selesai.', 'cancelled' => 'Tidak ada booking yang dibatalkan.'][$tab];

head_klien('History');
include __DIR__ . '/../navbar.php';
?>
<main class="kl-wrap page-top">
    <h1 class="kl-title big hs-judul">Booking Saya</h1>
    <?php if (isset($_GET['ok']) && is_string($_GET['ok'])) echo '<p class="alert-ok"><i class="fa-solid fa-circle-check"></i> Pembayaran berhasil. Booking <b>' . e($_GET['ok']) . '</b> terkirim dan menunggu konfirmasi MUA.</p>';
    if ($notif) echo '<p class="alert-' . $notif[0] . '" role="status">' . e($notif[1]) . '</p>'; ?>

    <nav class="hs-tabs" aria-label="Status booking">
        <?php foreach (TAB_RIWAYAT as $k => [$label, $st]) echo '<a class="' . ($tab === $k ? 'on' : '') . '" href="history.php?tab=' . $k . '"' . ($tab === $k ? ' aria-current="page"' : '') . '>' . $label . (!empty($jml[$st]) ? '<span class="hs-n">' . $jml[$st] . '</span>' : '') . '</a>'; ?>
    </nav>

    <?php foreach ($data as $b):
        [$label, $kelas, $pesan] = info_status($b);
        $id = (int)$b['id_booking'];
        $detail = 'detail_booking.php?id=' . $id; ?>
        <article class="hs-card">
            <div class="hs-mua">
                <?php echo foto($b['foto_profil'], 'avatar'); ?>
                <div><b><a href="mua.php?id=<?php echo (int)$b['id_mua']; ?>"><?php echo e($b['nama_brand']); ?></a></b>
                    <span class="hs-layanan"><?php echo e($b['nama_layanan']); ?></span>
                    <small class="muted"><?php echo e($b['no_invoice']); ?></small></div>
            </div>
            <div class="hs-meta">
                <span><i class="fa-regular fa-calendar"></i> <?php echo tgl_lengkap($b['tanggal_booking']); ?></span>
                <span><i class="fa-regular fa-clock"></i> <?php echo e(jam_tampil($b['waktu_booking'])); ?></span>
            </div>
            <div class="hs-pesan">
                <?php if ($b['status'] === 'selesai' && (int)$b['rating'] > 0): ?>
                    <?php echo bintang((int)$b['rating']); ?>
                    <span><?php echo trim((string)$b['ulasan']) !== '' ? '“' . e(mb_strimwidth($b['ulasan'], 0, 70, '…', 'UTF-8')) . '”' : 'Tanpa ulasan tertulis'; ?></span>
                <?php else: echo e($pesan); endif; ?>
            </div>
            <div class="hs-aksi">
                <span class="hs-badge <?php echo e($kelas); ?>"><?php echo e($label); ?></span>
                <div class="hs-btns">
                    <?php if (in_array($b['status'], ['menunggu', 'dikonfirmasi'], true)): ?>
                        <a class="kb-fill hs-btn" href="chat.php?dengan=<?php echo (int)$b['id_mua']; ?>"><i class="fa-solid fa-comment"></i> Chat</a>
                    <?php elseif ($b['status'] === 'selesai'): ?>
                        <button type="button" class="kb-fill hs-btn js-rate" data-id="<?php echo $id; ?>" data-mua="<?php echo e($b['nama_brand']); ?>" data-rating="<?php echo (int)$b['rating']; ?>" data-ulasan="<?php echo e($b['ulasan']); ?>">
                            <i class="fa-solid fa-star"></i> <?php echo (int)$b['rating'] > 0 ? 'Ubah Review' : 'Beri Review'; ?></button>
                    <?php elseif ($b['status'] === 'ditolak'): ?>
                        <a class="kb-fill hs-btn" href="booking.php?mua=<?php echo (int)$b['id_mua']; ?>">Booking Lagi</a>
                    <?php endif; ?>
                    <a class="kb-outline hs-btn" href="<?php echo $detail; ?>">Lihat Detail</a>
                </div>
            </div>
        </article>
    <?php endforeach; ?>

    <?php if (!$data): ?><div class="kosong"><i class="fa-regular fa-calendar-xmark"></i><p><?php echo e($kosong); ?></p><a class="btn-cari" href="cari.php">Cari MUA</a></div><?php endif; ?>
</main>
<?php dialog_rating('list'); bottom_nav();