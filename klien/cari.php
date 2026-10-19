<?php
session_start();
require __DIR__ . '/../Includes/klien.php';

$f_lokasi = trim($_GET['lokasi'] ?? '');
$f_kat    = trim($_GET['kategori'] ?? '');
$f_tgl    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['tanggal'] ?? '') ? $_GET['tanggal'] : '';
$f_q      = trim($_GET['q'] ?? '');

$where = ''; $p = [];
if ($f_lokasi !== '') { $where .= ' AND u.lokasi = ?'; $p[] = $f_lokasi; }
if ($f_kat !== '')    { $where .= ' AND EXISTS (SELECT 1 FROM services s WHERE s.id_mua = u.id_user AND s.kategori = ?)'; $p[] = $f_kat; }
if ($f_q !== '')      { $where .= ' AND u.nama_brand LIKE ?'; $p[] = '%' . $f_q . '%'; }
$hasil = daftar_mua($where, $p);

$kota     = array_column(q("SELECT DISTINCT lokasi FROM users WHERE role='mua' AND status_verifikasi='approved' AND lokasi <> '' ORDER BY lokasi"), 'lokasi');
$kategori = array_column(q("SELECT DISTINCT s.kategori FROM services s JOIN users u ON u.id_user = s.id_mua WHERE u.status_verifikasi='approved' AND s.kategori <> '' ORDER BY s.kategori"), 'kategori');

head_klien('Cari MUA');
include __DIR__ . '/../navbar.php';
?>
<main class="kl-wrap page-top">
    <h1 class="kl-title big">Cari MUA</h1>
    <form class="search-card flat" method="GET">
        <div><label>Nama MUA</label><input type="search" name="q" value="<?php echo e($f_q); ?>" placeholder="Cari nama studio"></div>
        <div><label>Lokasi</label><select name="lokasi"><option value="">Semua Kota</option>
            <?php foreach ($kota as $k) echo '<option value="' . e($k) . '"' . ($k === $f_lokasi ? ' selected' : '') . '>' . e($k) . '</option>'; ?></select></div>
        <div><label>Tanggal Acara</label><input type="date" name="tanggal" min="<?php echo date('Y-m-d'); ?>" value="<?php echo e($f_tgl); ?>"></div>
        <div><label>Jenis Acara</label><select name="kategori"><option value="">Semua Kategori</option>
            <?php foreach ($kategori as $k) echo '<option value="' . e($k) . '"' . ($k === $f_kat ? ' selected' : '') . '>' . e($k) . '</option>'; ?></select></div>
        <button class="btn-cari" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Cari</button>
    </form>

    <p class="muted hasil-info"><?php echo count($hasil); ?> MUA ditemukan<?php echo ($f_lokasi || $f_kat || $f_q) ? ' &middot; <a href="cari.php">Reset filter</a>' : ''; ?></p>
    <div class="grid-3">
        <?php foreach ($hasil as $m) kartu_mua($m, $f_tgl); ?>
    </div>
    <?php if (!$hasil): ?><div class="kosong"><i class="fa-regular fa-face-frown"></i><p>Belum ada MUA yang cocok dengan filter Anda.</p></div><?php endif; ?>
</main>
<?php bottom_nav();