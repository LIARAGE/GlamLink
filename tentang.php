<?php
/**
 * TENTANG KAMI (link footer "Tentang Kami")
 * Tidak ada angka/statistik karangan. Bagian tim bisa diisi sendiri di bawah (lihat komentar).
 */
session_start();
require 'koneksi.php';

$judul_halaman = 'Tentang Kami';
$deskripsi_halaman = 'GlamLink menghubungkan klien dengan makeup artist profesional untuk setiap momen spesial.';
include 'includes/info_head.php';
?>

<section class="in-hero">
    <nav class="in-crumb" aria-label="Breadcrumb"><a href="index.php">Beranda</a><span>/</span><span>Tentang Kami</span></nav>
    <span class="overline">TENTANG GLAMLINK</span>
    <h1>Mempertemukan Klien dengan <span>Seniman Rias</span> Terbaik</h1>
    <p>Kami percaya setiap orang berhak tampil percaya diri di momen pentingnya, dan setiap MUA berbakat berhak ditemukan.</p>
</section>

<section class="in-section">
    <div class="in-wrap">
        <div class="in-split">
            <img src="assets/img/bridal.jpg" alt="Riasan pengantin oleh MUA GlamLink" loading="lazy">
            <div>
                <span class="overline" style="text-align: left;">CERITA KAMI</span>
                <h2>Mencari MUA Tidak Seharusnya Serumit Itu</h2>
                <p>Selama ini, mencari makeup artist berarti menggulir media sosial berjam-jam, bertanya harga satu per satu, lalu berharap jadwalnya masih kosong. Di sisi lain, banyak MUA berbakat kesulitan menjangkau klien baru di luar lingkaran mereka.</p>
                <p>GlamLink hadir sebagai marketplace dua sisi untuk menyelesaikan keduanya: klien bisa membandingkan portfolio, harga, dan ulasan di satu tempat, sementara MUA mendapat etalase profesional, kalender, dan pembayaran yang tertata.</p>
                <a href="cara_kerja.php" class="in-link">Lihat cara kerjanya <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    </div>
</section>

<section class="in-section white">
    <div class="in-wrap">
        <div class="section-header">
            <span class="overline">NILAI KAMI</span>
            <h2>Yang Kami Pegang</h2>
        </div>
        <div class="in-grid c3">
            <div class="in-card">
                <div class="in-icon"><i class="fa-solid fa-shield-halved"></i></div>
                <h3>Kepercayaan</h3>
                <p>Setiap MUA diverifikasi tim admin sebelum tampil. Ulasan hanya bisa diberikan oleh klien yang sudah menyelesaikan pesanan.</p>
            </div>
            <div class="in-card">
                <div class="in-icon"><i class="fa-regular fa-eye"></i></div>
                <h3>Transparansi</h3>
                <p>Harga paket, biaya platform, dan jadwal terlihat sejak awal. Tidak ada biaya tersembunyi saat checkout.</p>
            </div>
            <div class="in-card">
                <div class="in-icon"><i class="fa-regular fa-heart"></i></div>
                <h3>Menghargai Karya</h3>
                <p>Kami memperlakukan riasan sebagai karya seni. Portfolio, ulasan, dan profil dirancang untuk menonjolkan keahlian setiap MUA.</p>
            </div>
        </div>
    </div>
</section>

<section class="in-section">
    <div class="in-wrap">
        <div class="section-header">
            <span class="overline">UNTUK SIAPA</span>
            <h2>Dibangun untuk Dua Sisi</h2>
        </div>
        <div class="in-grid c2">
            <div class="in-card white">
                <span class="in-tag">Klien</span>
                <h3>Calon pengantin, wisudawan, dan siapa pun yang punya acara</h3>
                <ul>
                    <li>Bandingkan MUA berdasarkan gaya, wilayah, harga, dan rating</li>
                    <li>Pesan jadwal dan bayar di satu tempat</li>
                    <li>Chat langsung dan pantau status pesanan</li>
                </ul>
            </div>
            <div class="in-card white">
                <span class="in-tag">Make-Up Artist</span>
                <h3>MUA independen maupun studio rias</h3>
                <ul>
                    <li>Etalase profil, layanan, dan portfolio yang profesional</li>
                    <li>Kelola pesanan, hari cuti, dan notifikasi dari satu dashboard</li>
                    <li>Pantau pendapatan dan tarik saldo ke rekening</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<?php /* Ingin menampilkan tim pengembang? Tambahkan bagian baru di sini memakai .in-grid.c3 dan .in-card */ ?>

<section class="cta-section">
    <h2>Mari Tampil Memukau<br>Bersama GlamLink</h2>
    <p>Bergabung sebagai klien untuk menemukan MUA impian, atau sebagai MUA untuk menjangkau lebih banyak klien.</p>
    <div class="cta-buttons">
        <a href="<?php echo site_e(site_link_mulai()); ?>" class="btn-filled">Mulai Sekarang</a>
        <a href="bantuan.php" class="btn-outline">Hubungi Kami</a>
    </div>
</section>

<?php include 'includes/info_foot.php'; ?>
