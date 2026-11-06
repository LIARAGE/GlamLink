<?php
session_start();
require __DIR__ . '/../Includes/klien.php';
require __DIR__ . '/../Includes/riwayat.php';

aksi_booking($id_klien);   // batal / konfirmasi selesai / review dari halaman ini (lalu redirect kembali ke sini)

$id = (int)(is_string($_GET['id'] ?? null) ? $_GET['id'] : 0);
// Hanya pemilik booking yang bisa membuka detailnya
$b = q("SELECT b.*, s.nama_layanan, u.nama_brand, u.foto_profil, u.nomor_telepon, u.email
        FROM bookings b JOIN services s ON s.id_service = b.id_layanan JOIN users u ON u.id_user = b.id_mua
        WHERE b.id_booking = ? AND b.id_pelanggan = ?", [$id, $id_klien])[0] ?? null;
if (!$b) { header('Location: history.php'); exit(); }

$st      = $b['status'];
$tab     = tab_dari_status($st);
[$label, $kelas, $pesan] = info_status($b);
$wa      = format_wa($b['nomor_telepon']);
$notif   = pesan_riwayat(is_string($_GET['msg'] ?? null) ? $_GET['msg'] : '');

// Pembayaran. Booking lama (sebelum ada wizard pembayaran) tidak punya metode/biaya platform -> tampil "-"
$harga   = (int)$b['total_harga'];
$fee     = (int)$b['biaya_platform'];
$ada_bayar = $b['metode_bayar'] !== null && $b['metode_bayar'] !== '';
$persen  = ($harga > 0 && $fee > 0) ? (int)round($fee * 100 / $harga) : 0;
$lokasi  = trim((string)$b['kota_acara']) !== '' ? $b['kota_acara'] : $b['lokasi_acara'];
$bayar_status = !$ada_bayar ? '-' : ($st === 'ditolak' ? 'Dikembalikan' : 'Lunas');

// Stepper 3 langkah: [jumlah langkah menyala, label langkah 2, langkah 2 = batal?]
$stepper = ['menunggu' => [1, 'Menunggu Konfirmasi MUA', false], 'dikonfirmasi' => [2, 'Konfirmasi MUA', false],
            'selesai' => [3, 'Konfirmasi MUA', false], 'ditolak' => [1, 'Booking Dibatalkan', true]][$st];

function baris($l, $v) { echo '<div><dt>' . e($l) . '</dt><dd>' . $v . '</dd></div>'; }

head_klien('Detail Booking');
?>
<header class="bk-header"><a href="index.php"><img src="../assets/img/logo.png" alt="GlamLink"></a></header>
<div class="bk-band"><div class="bk-wrap bk-band-in">
    <a class="bk-back" href="history.php?tab=<?php echo $tab; ?>" aria-label="Kembali ke History"><i class="fa-solid fa-arrow-left"></i></a>
    <h1>Detail Booking</h1><span class="bk-mua"><?php echo e($b['no_invoice']); ?></span>
</div></div>

<main class="hs-det">
    <ol class="bk-stepper s3" aria-label="Progres booking">
        <li class="bk-step on<?php echo $stepper[0] === 1 && !$stepper[2] ? ' now' : ''; ?>"><span class="bk-dot">1</span><span class="bk-lbl">Booking Dibuat</span></li>
        <li class="bk-step<?php echo $stepper[0] >= 2 ? ' on' : ''; ?><?php echo $stepper[2] ? ' x' : ''; ?><?php echo $stepper[0] === 2 ? ' now' : ''; ?>"><span class="bk-dot"><?php echo $stepper[2] ? '&times;' : '2'; ?></span><span class="bk-lbl"><?php echo $stepper[1]; ?></span></li>
        <li class="bk-step<?php echo $stepper[0] >= 3 ? ' on now' : ''; ?>"><span class="bk-dot">3</span><span class="bk-lbl">Booking Selesai</span></li>
    </ol>

    <?php if ($notif) echo '<p class="alert-' . $notif[0] . '" role="status">' . e($notif[1]) . '</p>'; ?>
    <?php if ($pesan !== '' && $st !== 'selesai'): ?><p class="hs-info <?php echo e($kelas); ?>"><span class="hs-badge <?php echo e($kelas); ?>"><?php echo e($label); ?></span> <?php echo e($pesan); ?><?php
        if ($st === 'ditolak' && $b['dibatalkan_pada']) echo ' <span class="muted">(' . tgl_lengkap($b['dibatalkan_pada']) . ', ' . date('H:i', strtotime($b['dibatalkan_pada'])) . ' WIB)</span>'; ?></p>
    <?php elseif ($st === 'selesai'): ?><p class="hs-info s-selesai"><span class="hs-badge s-selesai">Selesai</span> Terima kasih sudah memakai GlamLink!</p><?php endif; ?>

    <div class="hs-grid2">
        <section class="bk-card">
            <h2>Informasi Booking</h2>
            <dl class="hs-rows">
                <?php baris('No. Invoice', e($b['no_invoice']));
                baris('Jenis Layanan', e($b['nama_layanan']));
                baris('Tanggal', e(tgl_lengkap($b['tanggal_booking'])));
                baris('Waktu', e(jam_tampil($b['waktu_booking'])));
                baris('Lokasi', e($lokasi ?: '-'));
                if (trim((string)$b['nama_acara']) !== '')  baris('Nama Acara', e($b['nama_acara']));
                if (trim((string)$b['jenis_acara']) !== '') baris('Jenis Acara', e($b['jenis_acara']));
                if (trim((string)$b['lokasi_acara']) !== '' && trim((string)$b['kota_acara']) !== '') baris('Alamat Lengkap', e($b['lokasi_acara']));
                if (trim((string)$b['catatan']) !== '')     baris('Catatan', nl2br(e($b['catatan']))); ?>
            </dl>
        </section>
        <section class="bk-card">
            <h2>Ringkasan Pembayaran</h2>
            <dl class="hs-rows">
                <?php baris('Biaya Layanan', rupiah($harga));
                baris('Biaya Platform' . ($persen ? ' (' . $persen . '%)' : ''), $ada_bayar ? rupiah($fee) : '-'); ?>
            </dl>
            <div class="bk-total"><span>Total Biaya</span><b><?php echo rupiah($harga + $fee); ?></b></div>
            <dl class="hs-rows">
                <?php baris('Metode Pembayaran', e($ada_bayar ? $b['metode_bayar'] : '-'));
                baris('Status Pembayaran', e($bayar_status)); ?>
            </dl>
        </section>
    </div>

    <section class="bk-card hs-muacard">
        <h2><i class="fa-solid fa-user"></i> Informasi MUA</h2>
        <div class="hs-muabody">
            <a class="hs-muaid" href="mua.php?id=<?php echo (int)$b['id_mua']; ?>"><?php echo foto($b['foto_profil'], 'avatar'); ?><b><?php echo e($b['nama_brand']); ?></b></a>
            <?php if ($wa): ?><div class="hs-kontak"><i class="fa-solid fa-phone"></i><div><b>WhatsApp</b><span><?php echo e($wa['tampil']); ?></span></div></div><?php endif; ?>
            <div class="hs-kontak"><i class="fa-regular fa-envelope"></i><div><b>Email</b><span><?php echo e($b['email']); ?></span></div></div>
            <?php if ($wa): ?>
                <a class="kb-fill hs-chat" href="<?php echo e($wa['link']); ?>" target="_blank" rel="noopener"><i class="fa-solid fa-comment"></i> Chat</a>
            <?php endif; ?>
        </div>
    </section>

    <?php if (!empty($b['is_mua_done']) && !empty($b['foto_dokumentasi'])): ?>
    <section class="bk-card hs-dok"><h2><i class="fa-regular fa-image"></i> Dokumentasi dari MUA</h2><?php echo foto($b['foto_dokumentasi'], 'hs-dokfoto', 'fa-image'); ?></section>
    <?php endif; ?>

    <?php if ($st === 'selesai'): ?>
    <section class="bk-card hs-review">
        <h2><i class="fa-regular fa-star"></i> Review Anda</h2>
        <?php if ((int)$b['rating'] > 0): ?>
            <p><?php echo bintang((int)$b['rating']); ?></p>
            <p class="muted"><?php echo trim((string)$b['ulasan']) !== '' ? nl2br(e($b['ulasan'])) : 'Tanpa ulasan tertulis.'; ?></p>
        <?php else: ?><p class="muted">Bagikan pengalaman Anda untuk membantu klien lain memilih MUA.</p><?php endif; ?>
        <button type="button" class="kb-fill js-rate" data-id="<?php echo $id; ?>" data-mua="<?php echo e($b['nama_brand']); ?>" data-rating="<?php echo (int)$b['rating']; ?>" data-ulasan="<?php echo e($b['ulasan']); ?>"><i class="fa-solid fa-star"></i> <?php echo (int)$b['rating'] > 0 ? 'Ubah Review' : 'Beri Review'; ?></button>
    </section>
    <?php endif; ?>

    <div class="hs-foot">
        <?php if ($st === 'menunggu'): ?>
            <button type="button" class="bk-btn bk-btn-fill" id="btn-batal">Batalkan Booking</button>
        <?php elseif ($st === 'dikonfirmasi'):
            $siap = !empty($b['is_mua_done']) && empty($b['is_client_done']); ?>
            <?php if (!$siap): ?><p class="muted small hs-catat"><i class="fa-regular fa-clock"></i> Tombol aktif setelah MUA menandai sesi selesai.</p><?php endif; ?>
            <form method="POST" onsubmit="return confirm('Konfirmasi bahwa pesanan ini sudah selesai?');">
                <?php echo csrf_field(); ?><input type="hidden" name="aksi" value="selesai"><input type="hidden" name="dari" value="detail"><input type="hidden" name="id_booking" value="<?php echo $id; ?>">
                <button type="submit" class="bk-btn bk-btn-fill" <?php echo $siap ? '' : 'disabled'; ?>>Konfirmasi Pesanan</button>
            </form>
        <?php else: ?>
            <a class="bk-btn bk-btn-line hs-linkbtn" href="booking.php?mua=<?php echo (int)$b['id_mua']; ?>">Booking Lagi</a>
        <?php endif; ?>
    </div>
</main>

<?php if ($st === 'menunggu'): ?>
<dialog class="rate-dlg" id="dlg-batal">
    <form method="POST">
        <?php echo csrf_field(); ?><input type="hidden" name="aksi" value="batal"><input type="hidden" name="dari" value="detail"><input type="hidden" name="id_booking" value="<?php echo $id; ?>">
        <h3>Batalkan booking ini?</h3>
        <p class="muted">Jadwal akan dilepas kembali dan pembayaran Anda dikembalikan. Tindakan ini tidak dapat diurungkan.</p>
        <div class="dlg-actions"><button type="button" class="kb-outline" onclick="document.getElementById('dlg-batal').close()">Tidak</button><button type="submit" class="kb-fill">Ya, Batalkan</button></div>
    </form>
</dialog>
<script>document.getElementById('btn-batal').addEventListener('click', function () { document.getElementById('dlg-batal').showModal(); });</script>
<?php endif; ?>
<?php if ($st === 'selesai') dialog_rating('detail'); ?>
</body></html>
