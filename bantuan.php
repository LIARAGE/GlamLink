<?php
/**
 * KONTAK BANTUAN (link footer "Kontak Bantuan")
 * - FAQ memakai <details>, jadi tetap berfungsi tanpa JavaScript.
 * - Kontak diambil dari includes/site_info.php (isi data asli di sana).
 */
session_start();
require 'koneksi.php';
require_once 'includes/site_info.php';

$kontak = [
    ['email',     'fa-regular fa-envelope', 'Email',     SITE_EMAIL],
    ['whatsapp',  'fa-brands fa-whatsapp',  'WhatsApp',  SITE_WHATSAPP !== '' ? '+' . SITE_WHATSAPP : ''],
    ['instagram', 'fa-brands fa-instagram', 'Instagram', SITE_INSTAGRAM !== '' ? '@' . SITE_INSTAGRAM : ''],
];

$judul_halaman = 'Kontak Bantuan';
$deskripsi_halaman = 'Pertanyaan umum dan kontak bantuan GlamLink untuk klien dan MUA.';
include 'includes/info_head.php';
?>

<section class="in-hero">
    <nav class="in-crumb" aria-label="Breadcrumb"><a href="index.php">Beranda</a><span>/</span><span>Kontak Bantuan</span></nav>
    <span class="overline">PUSAT BANTUAN</span>
    <h1>Ada yang Bisa <span>Kami Bantu?</span></h1>
    <p>Cari jawabannya di pertanyaan umum di bawah. Kalau belum terjawab, hubungi tim kami lewat kontak yang tersedia.</p>
</section>

<section class="in-section">
    <div class="in-wrap narrow">
        <div class="in-faq">
            <p class="in-faq-group">Untuk Klien</p>
            <details>
                <summary>Bagaimana cara memesan MUA?</summary>
                <div class="jawab">Masuk ke akunmu, cari MUA lewat menu pencarian, buka profilnya, lalu pilih layanan, tanggal, dan jam. Isi detail acara, lalu selesaikan pembayaran. Langkah lengkapnya ada di halaman <a href="cara_kerja.php">Cara Kerja</a>.</div>
            </details>
            <details>
                <summary>Metode pembayaran apa saja yang tersedia?</summary>
                <div class="jawab">Transfer bank (BCA, Mandiri, BNI, BRI), e-wallet (GoPay, OVO, DANA), serta kartu debit/kredit (Visa, Mastercard). Ada biaya layanan platform 5% dari harga paket, ditampilkan sebelum kamu membayar.</div>
            </details>
            <details>
                <summary>Kapan pesanan saya dikonfirmasi?</summary>
                <div class="jawab">Setelah kamu membayar, pesanan berstatus <b>Menunggu</b> sampai MUA menerimanya. Kamu akan mendapat notifikasi saat status berubah. Pantau juga di halaman riwayat pesanan.</div>
            </details>
            <details>
                <summary>Bisakah saya bertanya dulu sebelum memesan?</summary>
                <div class="jawab">Bisa. Buka profil MUA lalu gunakan fitur chat untuk menanyakan paket, warna riasan, atau tema acara.</div>
            </details>
            <details>
                <summary>Bagaimana cara melaporkan MUA yang bermasalah?</summary>
                <div class="jawab">Buka profil MUA tersebut lalu pilih Laporkan. Pilih alasan, ceritakan kejadiannya, dan lampirkan bukti jika ada. Tim admin akan meninjau setiap laporan.</div>
            </details>

            <p class="in-faq-group">Untuk MUA</p>
            <details>
                <summary>Bagaimana cara bergabung sebagai MUA?</summary>
                <div class="jawab">Daftar lewat <a href="register_form.php?role=mua">formulir pendaftaran MUA</a>. Setelah data dan portfoliomu diverifikasi admin, profilmu akan tampil di pencarian klien. Lihat juga <a href="mitra.php">keuntungan bermitra</a>.</div>
            </details>
            <details>
                <summary>Apakah saya wajib mengisi jadwal ketersediaan setiap hari?</summary>
                <div class="jawab">Tidak. Semua tanggal otomatis terbuka untuk pengajuan klien. Kamu cukup menandai hari cuti, bisa per tanggal, satu bulan penuh, atau libur rutin mingguan.</div>
            </details>
            <details>
                <summary>Kapan pendapatan saya bisa ditarik?</summary>
                <div class="jawab">Pembayaran masuk ke saldo dompet setelah pesanan selesai. Saldo bisa ditarik dari menu Keuangan ke rekening bank atau e-wallet, minimal Rp 50.000 per penarikan.</div>
            </details>

            <p class="in-faq-group">Akun</p>
            <details>
                <summary>Saya lupa password, bagaimana masuk kembali?</summary>
                <div class="jawab">Klik <a href="lupa_password.php">Lupa Password?</a> di halaman masuk, isi email akunmu, lalu buat password baru lewat link yang diberikan. Link berlaku 30 menit dan hanya bisa dipakai sekali.</div>
            </details>
        </div>
    </div>
</section>

<section class="in-section white" id="kontak">
    <div class="in-wrap">
        <div class="section-header">
            <span class="overline">HUBUNGI KAMI</span>
            <h2>Masih Butuh Bantuan?</h2>
            <p>Tim kami melayani pada <?php echo site_e(SITE_JAM_LAYANAN); ?>.</p>
        </div>
        <div class="in-grid c3">
            <?php foreach ($kontak as [$kanal, $ikon, $label, $teks]):
                $url = site_link($kanal); ?>
                <div class="in-card in-contact">
                    <div class="in-icon"><i class="<?php echo $ikon; ?>"></i></div>
                    <div>
                        <b><?php echo $label; ?></b>
                        <?php if ($url): ?>
                            <a href="<?php echo site_e($url); ?>" <?php echo $kanal !== 'email' ? 'target="_blank" rel="noopener"' : ''; ?>><?php echo site_e($teks); ?></a>
                        <?php else: ?>
                            <span class="in-kosong">Belum diisi</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include 'includes/info_foot.php'; ?>
