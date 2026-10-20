<?php
session_start();
require __DIR__ . '/../Includes/klien.php';

// ---- 1. Baca & validasi filter (hanya nilai yang diizinkan yang dipakai) ----
$tgl = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['tanggal'] ?? '') ? $_GET['tanggal'] : '';
$f = [
    'q'        => trim($_GET['q'] ?? ''),
    'kategori' => in_array($_GET['kategori'] ?? '', KATEGORI_LAYANAN, true) ? $_GET['kategori'] : '',
    'wilayah'  => in_array($_GET['wilayah'] ?? '', WILAYAH, true) ? $_GET['wilayah'] : '',
    'harga'    => in_array($_GET['harga'] ?? '', ['rendah', 'sedang', 'tinggi'], true) ? $_GET['harga'] : '',
    'rating'   => in_array($_GET['rating'] ?? '', ['4', '4.5'], true) ? $_GET['rating'] : '',
];

// ---- 2. Susun query (urutan parameter = urutan kondisi) ----
$w = ''; $p = [];
if ($f['q'] !== '') {
    // "wisuda" -> Graduation, "pernikahan" -> Bridal, dst.
    $alias = ['wisuda' => 'graduation', 'pernikahan' => 'bridal', 'wedding' => 'bridal', 'pengantin' => 'bridal', 'pesta' => 'party', 'foto' => 'photoshoot'];
    $like  = '%' . $f['q'] . '%';
    $like2 = '%' . ($alias[mb_strtolower($f['q'])] ?? $f['q']) . '%';
    $w .= ' AND (u.nama_brand LIKE ? OR u.bio LIKE ? OR EXISTS (SELECT 1 FROM services s WHERE s.id_mua = u.id_user AND (s.nama_layanan LIKE ? OR s.kategori LIKE ? OR s.kategori LIKE ?)))';
    array_push($p, $like, $like, $like, $like, $like2);
}
if ($f['wilayah'] !== '') {   // MUA "Greater Jakarta"/"Jabodetabek" dianggap menjangkau semua wilayah
    $w .= " AND (u.lokasi LIKE ? OR u.lokasi LIKE '%Greater Jakarta%' OR u.lokasi LIKE '%Jabodetabek%')";
    $p[] = '%' . $f['wilayah'] . '%';
}
// Kategori + rentang harga harus cocok pada layanan YANG SAMA
$kond = []; $kp = [];
if ($f['kategori'] !== '') { $kond[] = 's.kategori = ?'; $kp[] = $f['kategori']; }
$rentang = ['rendah' => 's.harga < 800000', 'sedang' => 's.harga BETWEEN 800000 AND 1500000', 'tinggi' => 's.harga > 1500000'];
if ($f['harga'] !== '') $kond[] = $rentang[$f['harga']];
if ($kond) { $w .= ' AND EXISTS (SELECT 1 FROM services s WHERE s.id_mua = u.id_user AND ' . implode(' AND ', $kond) . ')'; $p = array_merge($p, $kp); }
if ($f['rating'] !== '') { $w .= ' AND (SELECT AVG(rating) FROM bookings WHERE id_mua = u.id_user AND rating > 0) >= ?'; $p[] = $f['rating']; }

$hasil  = daftar_mua($w, $p);
$aktif  = $f['q'] !== '' || $f['kategori'] !== '' || $f['wilayah'] !== '' || $f['harga'] !== '' || $f['rating'] !== '';

function opsi($nama, $nilai, $label, $sekarang) {
    $on = ((string)$sekarang === (string)$nilai);
    return '<label class="opt' . ($on ? ' on' : '') . '"><input type="radio" name="' . $nama . '" value="' . e($nilai) . '"' . ($on ? ' checked' : '') . ' onchange="this.form.submit()"><span>' . e($label) . '</span>' . ($on ? '<i class="fa-solid fa-check"></i>' : '') . '</label>';
}

head_klien('Cari MUA');
include __DIR__ . '/../navbar.php';
?>
<main class="kl-wrap page-top">
    <form method="GET" class="cari-layout">
        <?php if ($tgl) echo '<input type="hidden" name="tanggal" value="' . e($tgl) . '">'; ?>

        <div>
            <button type="button" class="kb-outline filter-toggle" onclick="document.querySelector('.filter-box').classList.toggle('open')"><i class="fa-solid fa-sliders"></i> Filter Pencarian</button>
            <aside class="filter-box">
                <h3><i class="fa-solid fa-sliders"></i> Filter Pencarian</h3>
                <div class="filter-group"><h4>Kategori Layanan</h4>
                    <?php echo opsi('kategori', '', 'Semua', $f['kategori']); foreach (KATEGORI_LAYANAN as $k) echo opsi('kategori', $k, $k, $f['kategori']); ?></div>
                <div class="filter-group"><h4>Wilayah Jabodetabek</h4>
                    <?php echo opsi('wilayah', '', 'Semua Wilayah', $f['wilayah']); foreach (WILAYAH as $k) echo opsi('wilayah', $k, $k, $f['wilayah']); ?></div>
                <div class="filter-group"><h4>Rentang Harga</h4>
                    <?php echo opsi('harga', '', 'Semua', $f['harga']) . opsi('harga', 'rendah', '< 800k', $f['harga']) . opsi('harga', 'sedang', '800k - 1.5M', $f['harga']) . opsi('harga', 'tinggi', '> 1.5M', $f['harga']); ?></div>
                <div class="filter-group"><h4>Rating Minimal</h4>
                    <?php echo opsi('rating', '', 'Semua', $f['rating']) . opsi('rating', '4', '4.0+ Bintang', $f['rating']) . opsi('rating', '4.5', '4.5+ Bintang', $f['rating']); ?></div>
                <noscript><button class="kb-fill" style="width:100%">Terapkan Filter</button></noscript>
                <?php if ($aktif) echo '<a class="filter-reset" href="cari.php' . ($tgl ? '?tanggal=' . e($tgl) : '') . '"><i class="fa-solid fa-rotate-left"></i> Reset filter</a>'; ?>
            </aside>
        </div>

        <section>
            <div class="search-bar"><i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" name="q" value="<?php echo e($f['q']); ?>" placeholder="Cari nama MUA, bridal, wisuda..."></div>
            <p class="muted hasil-info"><?php echo count($hasil); ?> MUA ditemukan<?php echo $tgl ? ' &middot; tanggal acara ' . e(date('d/m/Y', strtotime($tgl))) : ''; ?></p>

            <div class="cari-grid"><?php foreach ($hasil as $m) kartu_cari($m, $tgl); ?></div>
            <?php if (!$hasil): ?>
                <div class="kosong"><i class="fa-regular fa-face-frown"></i><p>Belum ada MUA yang cocok dengan filter Anda.</p>
                    <?php if ($aktif) echo '<a class="kb-fill" href="cari.php">Reset filter</a>'; ?></div>
            <?php endif; ?>
        </section>
    </form>
</main>
<?php bottom_nav();