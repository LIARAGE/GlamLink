<?php
session_start();

if (isset($_SESSION['status_login'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/index.php");
    } else if ($_SESSION['role'] == 'mua') {
        header("Location: mua/index.php");
    } else {
        header("Location: beranda.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GlamLink - Tampil Memukau Di Setiap Momen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="hero-gradient-bg">
        <main class="hero">
            <div class="hero-text">
                <h1>Tampil Memukau Di <span>Setiap Momen</span></h1>
                <p>Terhubung dengan makeup artist terpercaya dan temukan pengalaman kecantikan sempurna untuk setiap momen spesial — mulai dari pernikahan impian, hari wisuda, hingga photoshoot berkelas.</p>
                <a href="register.php" class="btn-large">Mulai Sekarang</a>
            </div>
            <div class="hero-image">
                <img src="assets/img/hero.jpg" alt="Model Kecantikan">
            </div>
        </main>
    </div>

    <section class="features-section">
        <div class="section-header">
            <span class="overline">MENGAPA MEMILIH GLAMLINK</span>
            <h2>Cara Baru Menemukan & Memesan<br>MUA Profesional</h2>
            <p>Kami menghubungkan klien yang menginginkan kesempurnaan riasan dengan para perias profesional yang siap menciptakan keajaiban.</p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="icon-box"><i class="fa-solid fa-magnifying-glass"></i></div>
                <span class="feature-tag">KURASI KETAT</span>
                <h3>Temukan MUA Terpercaya</h3>
                <p>Jelajahi makeup artist berdasarkan gaya riasan, lokasi, ulasan bintang, dan portofolio terverifikasi.</p>
            </div>
            <div class="feature-card">
                <div class="icon-box"><i class="fa-regular fa-calendar-check"></i></div>
                <span class="feature-tag">CEPAT & PRAKTIS</span>
                <h3>Pemesanan Mudah & Praktis</h3>
                <p>Pilih artis yang tepat dan pesan jadwal janji temu Anda hanya dalam beberapa langkah sederhana.</p>
            </div>
            <div class="feature-card">
                <div class="icon-box"><i class="fa-solid fa-shield-halved"></i></div>
                <span class="feature-tag">TRANSPARANSI</span>
                <h3>Aman & Transparan</h3>
                <p>Lihat daftar paket layanan, rincian harga rupiah, dan ketersediaan jadwal secara terbuka tanpa biaya tersembunyi.</p>
            </div>
            <div class="feature-card">
                <div class="icon-box"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                <span class="feature-tag">LENGKAP</span>
                <h3>Satu Platform untuk Setiap Momen</h3>
                <p>Mulai dari pernikahan adat & modern, wisuda kampus, lamaran, hingga sesi foto studio.</p>
            </div>
        </div>
    </section>

    <section class="portfolio-section">
        <div class="section-header">
            <span class="overline">PORTFOLIO PILIHAN</span>
            <h2>Temukan MUA untuk Momen Spesialmu</h2>
        </div>

        <div class="portfolio-grid">
            <div class="portfolio-card">
                <img src="assets/img/portfolio1.jpg" alt="Bridal Makeup">
                <div class="portfolio-info">
                    <span class="portfolio-tag">BRIDAL</span>
                    <h3>Kayla Studios</h3>
                </div>
            </div>
            <div class="portfolio-card">
                <img src="assets/img/portfolio2.jpg" alt="Graduation Makeup">
                <div class="portfolio-info">
                    <span class="portfolio-tag">GRADUATION</span>
                    <h3>Caroline</h3>
                </div>
            </div>
            <div class="portfolio-card">
                <img src="assets/img/portfolio3.jpg" alt="Party Makeup">
                <div class="portfolio-info">
                    <span class="portfolio-tag">PARTY</span>
                    <h3>Sandra & Sabrina Studio</h3>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <h2>Siap Menemukan MUA Impian<br>Anda?</h2>
        <p>Bergabunglah bersama ribuan klien yang telah mempercayakan riasan pernikahan, wisuda, dan photoshoot mereka bersama GlamLink.</p>
        <div class="cta-buttons">
            <a href="register_form.php?role=pelanggan" class="btn-filled">Mulai Sekarang</a>
            <a href="register_form.php?role=mua" class="btn-outline">Daftar sebagai MUA</a>
        </div>
    </section>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-brand">
                <img src="assets/img/logo-white.png" alt="GlamLink" class="footer-logo">
                <p>Marketplace kecantikan dua sisi terdepan yang menghubungkan klien dengan makeup artist (MUA) profesional terpercaya untuk pernikahan, wisuda, gala pesta, dan sesi photoshoot.</p>
                <div class="socials">
                    <a href="#"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#"><i class="fa-brands fa-tiktok"></i></a>
                    <a href="#"><i class="fa-regular fa-envelope"></i></a>
                </div>
            </div>
            
            <div class="footer-links">
                <h4>Platform</h4>
                <ul>
                    <li><a href="#">Fitur</a></li>
                    <li><a href="#">Layanan Kami</a></li>
                    <li><a href="#">Cara Kerja</a></li>
                    <li><a href="#">Temukan Makeup Artist</a></li>
                </ul>
            </div>

            <div class="footer-links">
                <h4>Perusahaan</h4>
                <ul>
                    <li><a href="#">Tentang Kami</a></li>
                    <li><a href="#">Kontak Bantuan</a></li>
                    <li><a href="#">Syarat & Ketentuan</a></li>
                    <li><a href="#">Kebijakan Privasi</a></li>
                </ul>
            </div>

            <div class="footer-links">
                <h4>Untuk MUA</h4>
                <ul>
                    <li><a href="register_form.php?role=mua">Daftar sebagai MUA</a></li>
                    <li><a href="#">Keuntungan Bermitra</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2026 GlamLink. Seluruh hak cipta dilindungi.</p>
            <p>Dibuat dengan ❤️ untuk para seniman rias dan klien di Indonesia</p>
        </div>
    </footer>

</body>
</html>