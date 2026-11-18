<?php
/**
 * LAYANAN KAMI (link footer "Layanan Kami")
 * Kategori sama dengan KATEGORI_LAYANAN di includes/klien.php: Bridal, Graduation, Party, Photoshoot.
 * Jumlah MUA & harga "mulai dari" diambil langsung dari database (hanya MUA yang sudah diverifikasi).
 */
session_start();
require 'koneksi.php';

$kategori = [
    'Bridal'     => ['judul' => 'Pernikahan & Akad',  'foto' => 'bridal.jpg',      'ikon' => 'fa-solid fa-ring',
                     'teks' => 'Riasan pengantin untuk akad, pemberkatan, dan resepsi. Dari tampilan adat hingga modern, tahan lama dari pagi sampai malam.'],
    'Graduation' => ['judul' => 'Wisuda',             'foto' => 'graduation.jpg',  'ikon' => 'fa-solid fa-graduation-cap',
                     'teks' => 'Riasan segar dan rapi untuk hari kelulusan, nyaman dipakai seharian dan tetap cantik di foto bersama keluarga.'],
    'Party'      => ['judul' => 'Pesta & Gala',       'foto' => 'party.jpg',       'ikon' => 'fa-solid fa-champagne-glasses',
                     'teks' => 'Glam look untuk pesta, gala dinner, lamaran, atau acara kantor. Bisa soft glam sampai bold sesuai tema acaramu.'],
    'Photoshoot' => ['judul' => 'Photoshoot',         'foto' => 'imglanding1.jpg', 'ikon' => 'fa-solid fa-camera',
                     'teks' => 'Riasan yang dirancang untuk kamera dan pencahayaan studio: prewedding, katalog produk, konten, hingga foto profil.'],
];

// Statistik per kategori dari layanan MUA yang sudah diverifikasi
$stat = [];
$hasil = mysqli_query($koneksi, "
    SELECT s.kategori, COUNT(DISTINCT s.id_mua) AS jml_mua, MIN(s.harga) AS harga_min
    FROM services s JOIN users u ON u.id_user = s.id_mua
    WHERE u.role = 'mua' AND u.status_verifikasi = 'approved'
    GROUP BY s.kategori");
while ($hasil && ($r = mysqli_fetch_assoc($hasil))) $stat[$r['kategori']] = $r;

$judul_halaman = 'Layanan Kami';
$deskripsi_halaman = 'Layanan rias GlamLink: pernikahan, wisuda, pesta, dan photoshoot bersama MUA profesional.';
include 'includes/info_head.php';
?>

<section class="in-hero">
    <nav class="in-crumb" aria-label="Breadcrumb"><a href="index.php">Beranda</a><span>/</span><span>Layanan Kami</span></nav>
    <span class="overline">LAYANAN KAMI</span>
    <h1>Satu Platform untuk <span>Setiap Momen</span></h1>
    <p>Pilih jenis acaramu, lalu temukan MUA yang gaya riasannya paling cocok. Semua harga paket terlihat jelas sebelum kamu memesan.</p>
</section>

<section class="in-section">
    <div class="in-wrap">
        <div class="in-grid c4">
            <?php foreach ($kategori as $kode => $k):
                $s = $stat[$kode] ?? null; ?>
                <article class="in-service" id="<?php echo strtolower($kode); ?>">
                    <img src="assets/img/<?php echo $k['foto']; ?>" alt="Contoh riasan <?php echo site_e($k['judul']); ?>" loading="lazy">
                    <div class="in-service-body">
                        <span class="in-tag"><i class="<?php echo $k['ikon']; ?>"></i> <?php echo $kode; ?></span>
                        <h3><?php echo site_e($k['judul']); ?></h3>
                        <p><?php echo site_e($k['teks']); ?></p>
                        <div class="in-service-meta">
                            <?php if ($s): ?>
                                <span><?php echo (int)$s['jml_mua']; ?> MUA tersedia</span>
                                <span>mulai <b>Rp <?php echo number_format((float)$s['harga_min'], 0, ',', '.'); ?></b></span>
                            <?php else: ?>
                                <span>Segera hadir</span>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo site_e(site_link_mulai($kode)); ?>" class="in-link">Cari MUA <?php echo site_e($k['judul']); ?> <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="in-section white">
    <div class="in-wrap">
        <div class="section-header">
            <span class="overline">TERMASUK DI SETIAP PESANAN</span>
            <h2>Yang Kamu Dapatkan di GlamLink</h2>
        </div>
        <div class="in-grid c3">
            <div class="in-card">
                <div class="in-icon"><i class="fa-solid fa-location-dot"></i></div>
                <h3>MUA Datang ke Lokasimu</h3>
                <p>Tulis lokasi acara saat memesan. MUA datang ke rumah, gedung, atau hotel di area jangkauannya.</p>
            </div>
            <div class="in-card">
                <div class="in-icon"><i class="fa-regular fa-calendar-check"></i></div>
                <h3>Jadwal yang Jelas</h3>
                <p>Pilih tanggal dan jam. Tanggal saat MUA sedang cuti otomatis tidak bisa dipilih, jadi tidak ada janji yang bentrok.</p>
            </div>
            <div class="in-card">
                <div class="in-icon"><i class="fa-regular fa-credit-card"></i></div>
                <h3>Harga Terbuka</h3>
                <p>Harga paket tertera di profil MUA. Saat membayar ada biaya layanan platform 5%, ditampilkan sebelum kamu konfirmasi.</p>
            </div>
            <div class="in-card">
                <div class="in-icon"><i class="fa-regular fa-images"></i></div>
                <h3>Portfolio &amp; Ulasan Asli</h3>
                <p>Lihat hasil riasan sebelumnya dan ulasan dari klien yang benar-benar sudah memakai jasa MUA tersebut.</p>
            </div>
            <div class="in-card">
                <div class="in-icon"><i class="fa-regular fa-comments"></i></div>
                <h3>Chat Langsung</h3>
                <p>Tanya detail paket, warna riasan, atau tema acara langsung ke MUA lewat fitur chat, sebelum maupun sesudah memesan.</p>
            </div>
            <div class="in-card">
                <div class="in-icon"><i class="fa-solid fa-wallet"></i></div>
                <h3>Banyak Pilihan Bayar</h3>
                <p>Transfer bank (BCA, Mandiri, BNI, BRI), e-wallet (GoPay, OVO, DANA), atau kartu debit/kredit.</p>
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <h2>Sudah Tahu Acaramu Butuh<br>Riasan Seperti Apa?</h2>
    <p>Cari MUA berdasarkan kategori, wilayah, harga, dan rating, lalu pesan jadwalnya dalam beberapa langkah.</p>
    <div class="cta-buttons">
        <a href="<?php echo site_e(site_link_mulai()); ?>" class="btn-filled">Mulai Cari MUA</a>
        <a href="cara_kerja.php" class="btn-outline">Lihat Cara Kerja</a>
    </div>
</section>

<?php include 'includes/info_foot.php'; ?>
