<?php
/**
 * FOOTER BERSAMA (dipanggil di index.php dan semua halaman info)
 *   <?php include 'footer.php'; ?>
 * Butuh assets/css/landing.css (gaya footer ada di sana).
 *
 * "Fitur" & "Temukan Makeup Artist" untuk tamu = scroll ke bagian di halaman home.
 * User yang sudah login tidak bisa membuka index.php (langsung dialihkan ke dashboard),
 * jadi untuk mereka link diarahkan ke halaman yang setara.
 */
require_once __DIR__ . '/includes/site_info.php';

$ft_login = isset($_SESSION['status_login']);
$ft_role  = $_SESSION['role'] ?? '';
$ft_di_home = basename($_SERVER['SCRIPT_NAME']) === 'index.php';   // footer sedang tampil di landing page

$ft_fitur = $ft_login ? 'cara_kerja.php' : ($ft_di_home ? '#fitur' : 'index.php#fitur');
if ($ft_role === 'pelanggan')   $ft_temukan = 'klien/cari.php';
elseif ($ft_login)              $ft_temukan = 'layanan.php';
else                            $ft_temukan = $ft_di_home ? '#portfolio' : 'index.php#portfolio';

$ft_sosial = [
    ['instagram', 'fa-brands fa-instagram', 'Instagram GlamLink'],
    ['tiktok',    'fa-brands fa-tiktok',    'TikTok GlamLink'],
    ['email',     'fa-regular fa-envelope', 'Email GlamLink'],
];
?>
<footer class="footer">
    <div class="footer-container">
        <div class="footer-brand">
            <a href="index.php" class="footer-brand-logo">
                <img src="assets/img/logo-emblem.png" alt="" class="footer-logo" style="filter: none; height: 48px;">
                <span>GlamLink</span>
            </a>
            <p>Marketplace kecantikan dua sisi yang menghubungkan klien dengan makeup artist (MUA) profesional terpercaya untuk pernikahan, wisuda, gala pesta, dan sesi photoshoot.</p>
            <div class="socials">
                <?php foreach ($ft_sosial as [$kanal, $ikon, $label]):
                    $url = site_link($kanal); ?>
                    <a href="<?php echo site_e($url ?? 'bantuan.php#kontak'); ?>" aria-label="<?php echo $label; ?>"
                       <?php echo ($url && $kanal !== 'email') ? 'target="_blank" rel="noopener"' : ''; ?>><i class="<?php echo $ikon; ?>"></i></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="footer-links">
            <h4>Platform</h4>
            <ul>
                <li><a href="<?php echo $ft_fitur; ?>">Fitur</a></li>
                <li><a href="layanan.php">Layanan Kami</a></li>
                <li><a href="cara_kerja.php">Cara Kerja</a></li>
                <li><a href="<?php echo $ft_temukan; ?>">Temukan Makeup Artist</a></li>
            </ul>
        </div>

        <div class="footer-links">
            <h4>Perusahaan</h4>
            <ul>
                <li><a href="tentang.php">Tentang Kami</a></li>
                <li><a href="bantuan.php">Kontak Bantuan</a></li>
                <li><a href="syarat.php">Syarat &amp; Ketentuan</a></li>
                <li><a href="privasi.php">Kebijakan Privasi</a></li>
            </ul>
        </div>

        <div class="footer-links">
            <h4>Untuk MUA</h4>
            <ul>
                <li><a href="<?php echo $ft_role === 'mua' ? 'mua/index.php' : 'register_form.php?role=mua'; ?>"><?php echo $ft_role === 'mua' ? 'Dashboard MUA' : 'Daftar sebagai MUA'; ?></a></li>
                <li><a href="mitra.php">Keuntungan Bermitra</a></li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> GlamLink. Seluruh hak cipta dilindungi.</p>
        <p>Dibuat dengan ❤️ untuk para seniman rias dan klien di Indonesia</p>
    </div>
</footer>
