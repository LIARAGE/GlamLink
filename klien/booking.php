<?php
session_start();
require __DIR__ . '/../Includes/klien.php';

// Bisa dibuka dari: kartu MUA (?mua=ID), tombol layanan (?layanan=ID), atau kombinasi keduanya
$id_layanan = (int)($_GET['layanan'] ?? $_POST['layanan'] ?? 0);
$id_mua     = (int)($_GET['mua'] ?? $_POST['mua'] ?? 0);
if ($id_layanan && !$id_mua) { $id_mua = (int)(q('SELECT id_mua FROM services WHERE id_service = ?', [$id_layanan])[0]['id_mua'] ?? 0); }

$mua = q("SELECT id_user, nama_brand, lokasi FROM users WHERE id_user = ? AND role = 'mua' AND status_verifikasi = 'approved'", [$id_mua])[0] ?? null;
if (!$mua) { header('Location: cari.php'); exit(); }
$layanan = q('SELECT * FROM services WHERE id_mua = ? ORDER BY harga', [$id_mua]);

$error = '';
$v = ['layanan' => $id_layanan ?: ($layanan[0]['id_service'] ?? 0), 'tanggal' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['tanggal'] ?? '') ? $_GET['tanggal'] : '', 'waktu' => '', 'lokasi' => '', 'catatan' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v = ['layanan' => (int)($_POST['layanan'] ?? 0), 'tanggal' => trim($_POST['tanggal'] ?? ''), 'waktu' => trim($_POST['waktu'] ?? ''), 'lokasi' => trim($_POST['lokasi'] ?? ''), 'catatan' => trim($_POST['catatan'] ?? '')];
    $s = null; foreach ($layanan as $row) if ((int)$row['id_service'] === $v['layanan']) $s = $row;   // layanan harus milik MUA ini

    if (!$s)                                                                                          $error = 'Pilih layanan yang tersedia.';
    elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v['tanggal']) || $v['tanggal'] < date('Y-m-d'))   $error = 'Pilih tanggal acara hari ini atau setelahnya.';
    elseif (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $v['waktu']))                                 $error = 'Jam mulai tidak valid.';
    elseif ($v['lokasi'] === '' || mb_strlen($v['lokasi']) > 255)                                     $error = 'Alamat lokasi acara wajib diisi (maksimal 255 karakter).';
    elseif (mb_strlen($v['catatan']) > 1000)                                                          $error = 'Catatan maksimal 1000 karakter.';
    else {
        $bentrok = q("SELECT 1 FROM bookings WHERE id_mua = ? AND tanggal_booking = ? AND waktu_booking = ? AND status IN ('menunggu','dikonfirmasi')", [$id_mua, $v['tanggal'], $v['waktu'] . ':00']);
        if ($bentrok) { $error = 'Jadwal tersebut sudah terisi. Silakan pilih tanggal atau jam lain (lihat tab Jadwal di profil MUA).'; }
        else {
            $total = (int)round($s['harga']); // harga selalu dari database, bukan dari form
            for ($i = 0; $i < 10; $i++) {
                try {
                    $invoice = 'GL-' . random_int(1000, 9999);
                    q("INSERT INTO bookings (no_invoice, id_mua, id_pelanggan, id_layanan, tanggal_booking, waktu_booking, lokasi_acara, catatan, total_harga, status)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'menunggu')",
                      [$invoice, $id_mua, $id_klien, $s['id_service'], $v['tanggal'], $v['waktu'] . ':00', $v['lokasi'], $v['catatan'], $total]);
                    header('Location: history.php?ok=' . $invoice); exit();
                } catch (Throwable $ex) { if ($ex->getCode() != 1062) break; }
            }
            $error = 'Booking gagal disimpan, silakan coba lagi.';
        }
    }
}
$pilih = $layanan[0] ?? null; foreach ($layanan as $row) if ((int)$row['id_service'] === (int)$v['layanan']) $pilih = $row;

head_klien('Booking');
include __DIR__ . '/../navbar.php';
?>
<main class="kl-wrap page-top">
    <a class="kembali" href="mua.php?id=<?php echo $id_mua; ?>"><i class="fa-solid fa-chevron-left"></i> Kembali ke profil</a>
    <h1 class="kl-title big">Booking MUA</h1>
    <?php if (!$layanan): ?>
        <div class="kosong"><i class="fa-regular fa-face-frown"></i><p><?php echo e($mua['nama_brand']); ?> belum menambahkan layanan, sehingga belum bisa dibooking.</p><a class="kb-fill" href="cari.php">Cari MUA lain</a></div>
    <?php else: ?>
    <div class="booking-grid">
        <form class="form-card" method="POST">
            <input type="hidden" name="mua" value="<?php echo $id_mua; ?>">
            <?php if ($error) echo '<p class="alert-err">' . e($error) . '</p>'; ?>
            <label>Pilih Layanan</label>
            <select name="layanan" id="pilih-layanan">
                <?php foreach ($layanan as $s) echo '<option value="' . (int)$s['id_service'] . '" data-nama="' . e($s['nama_layanan']) . '" data-durasi="' . e($s['durasi']) . '" data-harga="' . e(rupiah($s['harga'])) . '"' . ((int)$s['id_service'] === (int)$v['layanan'] ? ' selected' : '') . '>' . e($s['nama_layanan']) . ' - ' . rupiah($s['harga']) . '</option>'; ?>
            </select>
            <label>Tanggal Acara</label><input type="date" name="tanggal" min="<?php echo date('Y-m-d'); ?>" value="<?php echo e($v['tanggal']); ?>" required>
            <label>Jam Mulai</label><input type="time" name="waktu" value="<?php echo e($v['waktu']); ?>" required>
            <label>Alamat Lokasi Acara</label><input type="text" name="lokasi" maxlength="255" placeholder="Contoh: Hotel Mulia, Jakarta Pusat" value="<?php echo e($v['lokasi']); ?>" required>
            <label>Catatan untuk MUA (opsional)</label><textarea name="catatan" rows="4" maxlength="1000" placeholder="Tema, preferensi, jumlah orang yang dirias..."><?php echo e($v['catatan']); ?></textarea>
            <button class="btn-cari full" type="submit">Kirim Permintaan Booking</button>
            <p class="muted small">Booking akan berstatus <b>Menunggu</b> sampai dikonfirmasi oleh MUA.</p>
        </form>
        <aside class="ringkasan">
            <h3>Ringkasan Pesanan</h3>
            <p><span class="muted">MUA</span><b><?php echo e($mua['nama_brand']); ?></b></p>
            <p><span class="muted">Layanan</span><b id="r-nama"><?php echo e($pilih['nama_layanan']); ?></b></p>
            <p id="r-durasi-row" <?php echo $pilih['durasi'] ? '' : 'style="display:none"'; ?>><span class="muted">Durasi</span><b id="r-durasi"><?php echo e($pilih['durasi']); ?></b></p>
            <hr><p class="total"><span>Total</span><b id="r-total"><?php echo rupiah($pilih['harga']); ?></b></p>
        </aside>
    </div>
    <script>
        // Ringkasan ikut berubah saat layanan diganti
        document.getElementById('pilih-layanan').addEventListener('change', function () {
            var o = this.options[this.selectedIndex];
            document.getElementById('r-nama').textContent = o.dataset.nama;
            document.getElementById('r-total').textContent = o.dataset.harga;
            document.getElementById('r-durasi').textContent = o.dataset.durasi;
            document.getElementById('r-durasi-row').style.display = o.dataset.durasi ? '' : 'none';
        });
    </script>
    <?php endif; ?>
</main>
<?php bottom_nav();