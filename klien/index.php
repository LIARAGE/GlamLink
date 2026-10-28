<?php
session_start();
require __DIR__ . '/../Includes/klien.php';

$kota     = WILAYAH;               // pilihan sama dengan filter di halaman Cari MUA
$kategori = KATEGORI_LAYANAN;

// 3 foto portfolio terbaru dari MUA terverifikasi
$porto = q("SELECT p.foto, u.id_user, u.nama_brand, u.foto_profil,
              (SELECT kategori FROM services WHERE id_mua = u.id_user ORDER BY id_service LIMIT 1) AS kategori
            FROM portfolios p JOIN users u ON u.id_user = p.id_mua
            WHERE u.role='mua' AND u.status_verifikasi='approved' ORDER BY p.id_portfolio DESC LIMIT 3");
$populer = daftar_mua('', [], 'rating DESC, selesai DESC, u.dibuat_pada DESC', 6);   // 6 MUA dengan rating tertinggi

head_klien('Home');
include __DIR__ . '/../navbar.php';
?>
<section class="kl-hero">
    <h2 class="salam">Selamat Datang, <span><?php echo e($_SESSION['nama']); ?></span></h2>
    <h1>Temukan MUA Anda!</h1>
    <p class="sub">Terhubung dengan para makeup artist profesional terverifikasi untuk momen pernikahan, wisuda, atau malam pesta Anda.</p>

    <form class="search-card" action="cari.php" method="GET">
        <div><label for="wilayah">Lokasi</label>
            <select id="wilayah" name="wilayah"><option value="">Pilih Kota</option>
                <?php foreach ($kota as $k) echo '<option value="' . e($k) . '">' . e($k) . '</option>'; ?>
            </select></div>
        <div><label for="tanggal">Tanggal Acara</label>
            <input type="date" id="tanggal" name="tanggal" min="<?php echo date('Y-m-d'); ?>"></div>
        <div><label for="kategori">Jenis Acara</label>
            <select id="kategori" name="kategori"><option value="">Pilih Kategori</option>
                <?php foreach ($kategori as $k) echo '<option value="' . e($k) . '">' . e($k) . '</option>'; ?>
            </select></div>
        <button class="btn-cari" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Cari MUA</button>
    </form>
</section>

<main class="kl-wrap">
    <section id="populer">
        <div class="kl-title-row">
            <h2 class="kl-title">Make Up Artist Populer</h2>
            <a href="cari.php" class="lihat-semua">Lihat Semua</a>
        </div>
        <div class="pop-scroll">
            <?php foreach ($populer as $m) kartu_populer($m); ?>
            <?php if (!$populer) echo '<p class="muted">Belum ada MUA terverifikasi.</p>'; ?>
        </div>
    </section>

    <h2 class="kl-title">Jelajahi Portfolio dari MUA Kami</h2>
    <div class="grid-3">
        <?php foreach ($porto as $p): ?>
            <a class="porto-card" href="mua.php?id=<?php echo (int)$p['id_user']; ?>">
                <?php echo foto($p['foto'], 'porto-img', 'fa-image'); ?>
                <span class="lihat-pill">Lihat Profil <i class="fa-solid fa-chevron-right"></i></span>
                <div class="porto-info">
                    <h3><?php echo $p['kategori'] ? e($p['kategori']) . ' Look' : 'Portfolio'; ?></h3>
                    <div class="porto-mua"><?php echo foto($p['foto_profil'], 'avatar sm'); ?><span><?php echo e($p['nama_brand']); ?></span></div>
                    <div class="chips"><?php if ($p['kategori']) echo '<span class="chip">#' . e($p['kategori']) . '</span>'; ?><span class="chip">#Terverifikasi</span></div>
                </div>
            </a>
        <?php endforeach; ?>
        <?php if (!$porto) echo '<p class="muted">Belum ada portfolio yang ditampilkan.</p>'; ?>
    </div>
</main>
<?php bottom_nav();