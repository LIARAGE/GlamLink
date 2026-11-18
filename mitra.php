<?php
/**
 * MITRA MUA (link footer "Daftar sebagai MUA" untuk tamu/klien)
 * - Keuntungan hanya yang SUDAH ada di aplikasi.
 * - Paket langganan diambil langsung dari tabel subscription_packages.
 * - Badge "PALING HEMAT" dihitung dari harga per hari terendah (bukan klaim karangan).
 */
session_start();
require 'koneksi.php';
require_once 'includes/site_info.php';

$paket = [];
$hasil = mysqli_query($koneksi, "SELECT id_paket, nama_paket, durasi_hari, harga FROM subscription_packages ORDER BY durasi_hari ASC");
if ($hasil) {
    while ($r = mysqli_fetch_assoc($hasil)) {
        $r['per_hari'] = $r['durasi_hari'] > 0 ? $r['harga'] / $r['durasi_hari'] : 0;
        $paket[] = $r;
    }
}
$id_hemat = null;
if (count($paket) > 1) {
    $termurah = min(array_column($paket, 'per_hari'));
    foreach ($paket as $p) { if ($p['per_hari'] == $termurah) { $id_hemat = $p['id_paket']; break; } }
}

$sudah_mua = isset($_SESSION['status_login']) && ($_SESSION['role'] ?? '') === 'mua';
$link_daftar = $sudah_mua ? 'mua/index.php' : 'register_form.php?role=mua';
$teks_daftar = $sudah_mua ? 'Buka Dashboard MUA' : 'Daftar sebagai MUA';

$judul_halaman = 'Jadi Mitra MUA';
$deskripsi_halaman = 'Bergabung sebagai make-up artist di GlamLink: etalase profil, kelola pesanan, chat klien, dan pantau pendapatan.';
include 'includes/info_head.php';
?>

<section class="in-hero">
    <nav class="in-crumb" aria-label="Breadcrumb"><a href="index.php">Beranda</a><span>/</span><span>Mitra MUA</span></nav>
    <span class="overline">UNTUK MAKE-UP ARTIST</span>
    <h1>Bawa Karyamu ke <span>Lebih Banyak Klien</span></h1>
    <p>Satu dashboard untuk menampilkan portfolio, menerima pesanan, mengobrol dengan klien, dan memantau pendapatan.</p>
    <a href="<?php echo $link_daftar; ?>" class="in-btn" style="margin-top: 26px;"><?php echo $teks_daftar; ?> <i class="fa-solid fa-arrow-right"></i></a>
</section>

<section class="in-section">
    <div class="in-wrap">
        <div class="section-header">
            <span class="overline">KEUNTUNGAN</span>
            <h2>Yang Kamu Dapatkan sebagai Mitra</h2>
        </div>
        <div class="in-grid c3">
            <div class="in-card white">
                <div class="in-icon"><i class="fa-regular fa-id-card"></i></div>
                <h3>Profil Profesional</h3>
                <p>Nama brand, bio, wilayah layanan, dan portfolio tampil rapi di halaman profilmu, lengkap dengan rating dari klien.</p>
            </div>
            <div class="in-card white">
                <div class="in-icon"><i class="fa-solid fa-list-check"></i></div>
                <h3>Kelola Pesanan</h3>
                <p>Terima atau tolak pesanan, lihat detail sesi, dan tandai pesanan selesai dari satu halaman.</p>
            </div>
            <div class="in-card white">
                <div class="in-icon"><i class="fa-regular fa-calendar-xmark"></i></div>
                <h3>Atur Hari Cuti</h3>
                <p>Tanpa perlu mengisi jadwal harian. Tandai cuti per tanggal, sebulan penuh, atau libur rutin mingguan.</p>
            </div>
            <div class="in-card white">
                <div class="in-icon"><i class="fa-regular fa-comments"></i></div>
                <h3>Chat dengan Klien</h3>
                <p>Diskusikan tema, warna, dan detail acara langsung di aplikasi tanpa perlu membagikan nomor pribadi.</p>
            </div>
            <div class="in-card white">
                <div class="in-icon"><i class="fa-regular fa-bell"></i></div>
                <h3>Notifikasi Pesanan</h3>
                <p>Dapat pemberitahuan pesanan baru, pembatalan, ulasan, dan masa langganan dalam satu kotak masuk.</p>
            </div>
            <div class="in-card white">
                <div class="in-icon"><i class="fa-solid fa-wallet"></i></div>
                <h3>Pantau Pendapatan</h3>
                <p>Lihat saldo dari pesanan yang selesai, riwayat transaksi, dan ajukan penarikan ke rekening atau e-wallet.</p>
            </div>
        </div>
    </div>
</section>

<section class="in-section white">
    <div class="in-wrap">
        <div class="section-header">
            <span class="overline">LANGKAH BERGABUNG</span>
            <h2>Mulai dalam 3 Langkah</h2>
        </div>
        <ol class="in-steps">
            <li class="in-step"><h3>Daftar akun MUA</h3><p>Isi data diri, nama brand, wilayah layanan, dan unggah portfolio awal.</p></li>
            <li class="in-step"><h3>Tunggu verifikasi admin</h3><p>Tim admin meninjau data dan portfoliomu. Kamu akan diberi tahu jika disetujui atau perlu perbaikan.</p></li>
            <li class="in-step"><h3>Lengkapi layanan & mulai terima pesanan</h3><p>Tambahkan paket layanan beserta harga, atur hari cuti, lalu profilmu siap ditemukan klien.</p></li>
        </ol>
    </div>
</section>

<?php if ($paket): ?>
<section class="in-section" id="paket">
    <div class="in-wrap">
        <div class="section-header">
            <span class="overline">PAKET LANGGANAN MITRA</span>
            <h2>Pilih Durasi yang Pas</h2>
            <p>Paket langganan bisa dibeli dari dashboard setelah akunmu terverifikasi.</p>
        </div>
        <div class="in-grid c<?php echo min(count($paket), 3); ?>">
            <?php foreach ($paket as $p): $hemat = $p['id_paket'] == $id_hemat; ?>
                <div class="in-plan<?php echo $hemat ? ' populer' : ''; ?>"<?php echo $hemat ? ' data-badge="PALING HEMAT PER HARI"' : ''; ?>>
                    <h3><?php echo site_e($p['nama_paket']); ?></h3>
                    <span class="durasi"><?php echo (int)$p['durasi_hari']; ?> hari</span>
                    <span class="harga">Rp <?php echo number_format($p['harga'], 0, ',', '.'); ?></span>
                    <span class="per-hari">≈ Rp <?php echo number_format(round($p['per_hari']), 0, ',', '.'); ?> / hari</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="cta-section">
    <h2>Siap Menjangkau<br>Lebih Banyak Klien?</h2>
    <p>Daftarkan dirimu sekarang. Ada pertanyaan? Cek pusat bantuan kami.</p>
    <div class="cta-buttons">
        <a href="<?php echo $link_daftar; ?>" class="btn-filled"><?php echo $teks_daftar; ?></a>
        <a href="bantuan.php" class="btn-outline">Pusat Bantuan</a>
    </div>
</section>

<?php include 'includes/info_foot.php'; ?>
