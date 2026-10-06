<?php
session_start();

// Jika user sudah login, langsung arahkan ke dashboard masing-masing agar tidak melihat landing page lagi
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
    <!-- Memanggil CSS khusus landing page sesuai pola Flavor Haven -->
    <link rel="stylesheet" href="assets/css/landing.css">
    <!-- Google Fonts untuk tipografi yang modern -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <!-- Navbar -->
    <header class="navbar">
        <div class="logo">
            <!-- Nanti masukkan logo Anda di folder img -->
            <img src="assets/img/logo.png" alt="GlamLink Logo">
        </div>
        <div class="nav-links">
            <a href="login.php" class="btn-masuk">Masuk</a>
            <a href="register.php" class="btn-daftar">Mulai Sekarang</a>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="hero">
        <div class="hero-text">
            <h1>Tampil Memukau Di Setiap Momen</h1>
            <p>Terhubung dengan makeup artist terpercaya dan temukan pengalaman kecantikan sempurna untuk setiap momen spesial — mulai dari pernikahan impian, hari wisuda, hingga photoshoot berkelas.</p>
            <a href="register.php" class="btn-primary">Mulai Sekarang</a>
        </div>
        <div class="hero-image">
            <!-- Nanti masukkan gambar hero Anda di folder img -->
            <img src="assets/img/hero.jpg" alt="Model Kecantikan">
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-brand">
                <h3>GlamLink</h3>
                <p>Marketplace kecantikan dua sisi terdepan yang menghubungkan klien dengan makeup artist (MUA) profesional terpercaya untuk pernikahan, wisuda, gala pesta, dan sesi photoshoot.</p>
                <div class="socials">
                    <a href="#">Instagram</a>
                    <a href="#">Facebook</a>
                    <a href="#">Twitter</a>
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
                    <li><a href="register.php">Daftar sebagai MUA</a></li>
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