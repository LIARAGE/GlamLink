<?php
session_start();
require __DIR__ . '/../Includes/klien.php';

// ---- Aksi klien (PRG: setelah diproses, redirect agar refresh tidak mengulang) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idb = (int)($_POST['id_booking'] ?? 0);
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'selesai') {
        // Klien mengonfirmasi pesanan selesai. Hanya boleh SETELAH MUA menandai selesai (is_mua_done = 1).
        // Karena kedua pihak sudah konfirmasi, status menjadi 'selesai' (sama seperti logika di mua/booking.php).
        q("UPDATE bookings SET is_client_done = 1, status = 'selesai' WHERE id_booking = ? AND id_pelanggan = ? AND status = 'dikonfirmasi' AND is_mua_done = 1 AND is_client_done = 0", [$idb, $id_klien]);
        $ok = q('SELECT status FROM bookings WHERE id_booking = ? AND id_pelanggan = ?', [$idb, $id_klien])[0]['status'] ?? '';
        header('Location: history.php?' . ($ok === 'selesai' ? 'done=1' : 'err=selesai')); exit();
    }
    if ($aksi === 'rating') {
        $r = (int)($_POST['rating'] ?? 0); $ul = trim($_POST['ulasan'] ?? '');
        if ($r < 1 || $r > 5)          { header('Location: history.php?err=rating'); exit(); }
        if (mb_strlen($ul) > 1000)     { header('Location: history.php?err=ulasan'); exit(); }
        // Hanya pemilik pesanan & hanya pesanan yang sudah selesai
        q("UPDATE bookings SET rating = ?, ulasan = ? WHERE id_booking = ? AND id_pelanggan = ? AND status = 'selesai'", [$r, $ul, $idb, $id_klien]);
        $cek = q('SELECT rating FROM bookings WHERE id_booking = ? AND id_pelanggan = ?', [$idb, $id_klien])[0]['rating'] ?? 0;
        header('Location: history.php?' . ((int)$cek === $r ? 'rated=1' : 'err=rating')); exit();
    }
}

// Label disamakan dengan halaman Booking MUA (mua/booking.php)
$status = ['menunggu' => 'Menunggu Konfirmasi', 'dikonfirmasi' => 'Terkonfirmasi', 'selesai' => 'Selesai', 'ditolak' => 'Dibatalkan'];
$tab = isset($status[$_GET['tab'] ?? '']) ? $_GET['tab'] : '';
$sql = "SELECT b.*, s.nama_layanan, u.nama_brand, u.foto_profil FROM bookings b
        JOIN services s ON s.id_service = b.id_layanan JOIN users u ON u.id_user = b.id_mua
        WHERE b.id_pelanggan = ?" . ($tab ? ' AND b.status = ?' : '') . ' ORDER BY b.tanggal_booking DESC, b.id_booking DESC';
$data = q($sql, $tab ? [$id_klien, $tab] : [$id_klien]);
$pesan = ['done' => ['ok', 'Pesanan dikonfirmasi selesai. Terima kasih! Silakan beri rating untuk MUA.'], 'rated' => ['ok', 'Rating dan ulasan Anda tersimpan dan ikut menghitung rating MUA.'],
          'err' => ['err', 'Aksi tidak dapat diproses. Pastikan data yang diisi benar.']];

head_klien('History');
include __DIR__ . '/../navbar.php';
?>
<main class="kl-wrap page-top">
    <h1 class="kl-title big">History Pesanan</h1>
    <?php if (isset($_GET['ok'])) echo '<p class="alert-ok"><i class="fa-solid fa-circle-check"></i> Permintaan booking <b>' . e($_GET['ok']) . '</b> terkirim. Menunggu konfirmasi MUA.</p>';
    foreach ($pesan as $k => [$t, $isi]) if (isset($_GET[$k])) echo '<p class="alert-' . $t . '">' . e($isi) . '</p>'; ?>
    <div class="tabs"><a href="history.php" class="<?php echo $tab ? '' : 'on'; ?>">Semua</a>
        <?php foreach ($status as $k => $l) echo '<a href="?tab=' . $k . '" class="' . ($tab === $k ? 'on' : '') . '">' . $l . '</a>'; ?></div>

    <div class="order-list">
        <?php foreach ($data as $b):
            $st = $b['status']; $label = $status[$st] ?? e($st); $kelas = 's-' . e($st);
            $tunggu_klien = ($st === 'dikonfirmasi' && $b['is_mua_done'] && !$b['is_client_done']);
            if ($tunggu_klien) { $label = 'Menunggu Konfirmasi Anda'; $kelas = 's-menunggu-klien'; } ?>
            <div class="order-card">
                <div class="order-top"><span class="inv"><?php echo e($b['no_invoice']); ?></span><span class="badge-status <?php echo $kelas; ?>"><?php echo $label; ?></span></div>
                <div class="order-mua"><?php echo foto($b['foto_profil'], 'avatar'); ?>
                    <div><h3><a href="mua.php?id=<?php echo (int)$b['id_mua']; ?>"><?php echo e($b['nama_brand']); ?></a></h3><span class="muted"><?php echo e($b['nama_layanan']); ?></span></div></div>
                <div class="order-meta">
                    <span><i class="fa-regular fa-calendar"></i> <?php echo date('d/m/Y', strtotime($b['tanggal_booking'])) . ', ' . substr($b['waktu_booking'], 0, 5) . ' WIB'; ?></span>
                    <span><i class="fa-solid fa-location-dot"></i> <?php echo e($b['lokasi_acara'] ?: '-'); ?></span>
                    <b><?php echo rupiah($b['total_harga']); ?></b>
                </div>

                <?php if ($tunggu_klien): ?>
                    <div class="order-extra">
                        <div class="dokum"><?php if ($b['foto_dokumentasi']) echo foto($b['foto_dokumentasi'], 'porto-img', 'fa-image'); ?><span>MUA menandai sesi ini selesai.<br>Mohon konfirmasi jika riasan sudah Anda terima.</span></div>
                        <form method="POST" onsubmit="return confirm('Konfirmasi bahwa pesanan ini sudah selesai?');">
                            <input type="hidden" name="aksi" value="selesai"><input type="hidden" name="id_booking" value="<?php echo (int)$b['id_booking']; ?>">
                            <button class="kb-fill" type="submit"><i class="fa-solid fa-circle-check"></i> Konfirmasi Selesai</button></form>
                    </div>
                <?php elseif ($st === 'dikonfirmasi'): ?>
                    <div class="order-extra"><span class="muted small"><i class="fa-regular fa-clock"></i> Pesanan dikonfirmasi MUA. Anda dapat mengonfirmasi selesai setelah MUA menandai sesi selesai.</span></div>
                <?php elseif ($st === 'selesai'): ?>
                    <div class="order-extra">
                        <?php if ((int)$b['rating'] > 0): ?>
                            <div class="my-rate"><?php echo bintang((int)$b['rating']); ?><span class="ul"><?php echo trim((string)$b['ulasan']) !== '' ? '"' . e(mb_strimwidth($b['ulasan'], 0, 90, '...')) . '"' : 'Tanpa ulasan tertulis'; ?></span></div>
                            <button type="button" class="kb-outline sm js-rate" data-id="<?php echo (int)$b['id_booking']; ?>" data-mua="<?php echo e($b['nama_brand']); ?>" data-rating="<?php echo (int)$b['rating']; ?>" data-ulasan="<?php echo e($b['ulasan']); ?>">Ubah Ulasan</button>
                        <?php else: ?>
                            <span class="muted small"><i class="fa-regular fa-star"></i> Bagikan pengalaman Anda untuk membantu klien lain.</span>
                            <button type="button" class="kb-fill sm js-rate" data-id="<?php echo (int)$b['id_booking']; ?>" data-mua="<?php echo e($b['nama_brand']); ?>" data-rating="0" data-ulasan="">Beri Rating</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if (!$data): ?><div class="kosong"><i class="fa-regular fa-calendar-xmark"></i><p>Belum ada pesanan.</p><a class="btn-cari" href="cari.php">Cari MUA</a></div><?php endif; ?>
</main>

<!-- Pop-up beri rating -->
<dialog class="rate-dlg" id="dlg-rate">
    <form method="POST">
        <input type="hidden" name="aksi" value="rating"><input type="hidden" name="id_booking" id="rate-id">
        <h3>Beri Rating</h3><p class="muted" id="rate-mua" style="margin:0"></p>
        <div class="star-input">
            <?php for ($i = 5; $i >= 1; $i--) echo '<input type="radio" name="rating" id="st' . $i . '" value="' . $i . '" required><label for="st' . $i . '" title="' . $i . ' bintang"><i class="fa-solid fa-star"></i></label>'; ?>
        </div>
        <textarea name="ulasan" id="rate-ulasan" rows="4" maxlength="1000" placeholder="Ceritakan pengalaman Anda (opsional)"></textarea>
        <div class="dlg-actions"><button type="button" class="kb-outline" onclick="document.getElementById('dlg-rate').close()">Batal</button><button type="submit" class="kb-fill">Kirim</button></div>
    </form>
</dialog>
<script>
    var dlg = document.getElementById('dlg-rate');
    document.querySelectorAll('.js-rate').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('rate-id').value = btn.dataset.id;
            document.getElementById('rate-mua').textContent = btn.dataset.mua;
            document.getElementById('rate-ulasan').value = btn.dataset.ulasan;
            document.querySelectorAll('.star-input input').forEach(function (r) { r.checked = (r.value === btn.dataset.rating); });
            dlg.showModal();
        });
    });
</script>
<?php bottom_nav();