<?php
session_start();
require __DIR__ . '/../Includes/klien.php';

$id_layanan = (int)($_GET['layanan'] ?? $_POST['layanan'] ?? 0);
$s = q("SELECT s.*, u.nama_brand, u.lokasi FROM services s JOIN users u ON u.id_user = s.id_mua
        WHERE s.id_service = ? AND u.role = 'mua' AND u.status_verifikasi = 'approved'", [$id_layanan])[0] ?? null;
if (!$s) { header('Location: cari.php'); exit(); }

$error = '';
$v = ['tanggal' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['tanggal'] ?? '') ? $_GET['tanggal'] : '', 'waktu' => '', 'lokasi' => '', 'catatan' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v = ['tanggal' => trim($_POST['tanggal'] ?? ''), 'waktu' => trim($_POST['waktu'] ?? ''), 'lokasi' => trim($_POST['lokasi'] ?? ''), 'catatan' => trim($_POST['catatan'] ?? '')];

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v['tanggal']) || $v['tanggal'] < date('Y-m-d')) $error = 'Pilih tanggal acara hari ini atau setelahnya.';
    elseif (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $v['waktu']))  $error = 'Jam mulai tidak valid.';
    elseif ($v['lokasi'] === '' || mb_strlen($v['lokasi']) > 255)       $error = 'Alamat lokasi acara wajib diisi (maksimal 255 karakter).';
    elseif (mb_strlen($v['catatan']) > 1000)                            $error = 'Catatan maksimal 1000 karakter.';
    else {
        // Jadwal MUA tidak boleh bentrok dengan booking lain yang masih aktif
        $bentrok = q("SELECT 1 FROM bookings WHERE id_mua = ? AND tanggal_booking = ? AND waktu_booking = ? AND status IN ('menunggu','dikonfirmasi')", [$s['id_mua'], $v['tanggal'], $v['waktu'] . ':00']);
        if ($bentrok) { $error = 'Jadwal tersebut sudah terisi. Silakan pilih tanggal atau jam lain.'; }
        else {
            $total = (int)round($s['harga']); // harga selalu diambil dari database, bukan dari form
            for ($i = 0; $i < 10; $i++) {
                try {
                    $invoice = 'GL-' . random_int(1000, 9999);
                    q("INSERT INTO bookings (no_invoice, id_mua, id_pelanggan, id_layanan, tanggal_booking, waktu_booking, lokasi_acara, catatan, total_harga, status)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'menunggu')",
                      [$invoice, $s['id_mua'], $id_klien, $s['id_service'], $v['tanggal'], $v['waktu'] . ':00', $v['lokasi'], $v['catatan'], $total]);
                    header('Location: history.php?ok=' . $invoice); exit();
                } catch (Throwable $ex) { if ($ex->getCode() != 1062) { $error = 'Booking gagal disimpan, silakan coba lagi.'; break; } }
            }
            if (!$error) $error = 'Booking gagal disimpan, silakan coba lagi.';
        }
    }
}

head_klien('Booking');
include __DIR__ . '/../navbar.php';
?>
<main class="kl-wrap page-top">
    <a class="kembali" href="mua.php?id=<?php echo (int)$s['id_mua']; ?>"><i class="fa-solid fa-chevron-left"></i> Kembali ke profil</a>
    <h1 class="kl-title big">Booking MUA</h1>
    <div class="booking-grid">
        <form class="form-card" method="POST">
            <input type="hidden" name="layanan" value="<?php echo (int)$s['id_service']; ?>">
            <?php if ($error) echo '<p class="alert-err">' . e($error) . '</p>'; ?>
            <label>Tanggal Acara</label><input type="date" name="tanggal" min="<?php echo date('Y-m-d'); ?>" value="<?php echo e($v['tanggal']); ?>" required>
            <label>Jam Mulai</label><input type="time" name="waktu" value="<?php echo e($v['waktu']); ?>" required>
            <label>Alamat Lokasi Acara</label><input type="text" name="lokasi" maxlength="255" placeholder="Contoh: Hotel Mulia, Jakarta Pusat" value="<?php echo e($v['lokasi']); ?>" required>
            <label>Catatan untuk MUA (opsional)</label><textarea name="catatan" rows="4" maxlength="1000" placeholder="Tema, preferensi, jumlah orang yang dirias..."><?php echo e($v['catatan']); ?></textarea>
            <button class="btn-cari full" type="submit">Kirim Permintaan Booking</button>
            <p class="muted small">Booking akan berstatus <b>Menunggu</b> sampai dikonfirmasi oleh MUA.</p>
        </form>
        <aside class="ringkasan">
            <h3>Ringkasan Pesanan</h3>
            <p><span class="muted">MUA</span><b><?php echo e($s['nama_brand']); ?></b></p>
            <p><span class="muted">Layanan</span><b><?php echo e($s['nama_layanan']); ?></b></p>
            <?php if ($s['durasi']) echo '<p><span class="muted">Durasi</span><b>' . e($s['durasi']) . '</b></p>'; ?>
            <hr><p class="total"><span>Total</span><b><?php echo rupiah($s['harga']); ?></b></p>
        </aside>
    </div>
</main>
<?php bottom_nav();