<?php
/**
 * CARA KERJA (link footer "Cara Kerja", juga tujuan "Fitur" untuk user yang sudah login)
 * Dua alur: untuk Klien dan untuk MUA. Tombol tab menukar alur tanpa reload (?untuk=mua juga bisa).
 * Langkah-langkah ini mengikuti alur yang benar-benar ada di aplikasi.
 */
session_start();
require 'koneksi.php';

$untuk = ($_GET['untuk'] ?? '') === 'mua' ? 'mua' : 'klien';

$judul_halaman = 'Cara Kerja';
$deskripsi_halaman = 'Cara memesan MUA di GlamLink untuk klien, dan cara menerima pesanan untuk MUA.';
include 'includes/info_head.php';
?>

<section class="in-hero">
    <nav class="in-crumb" aria-label="Breadcrumb"><a href="index.php">Beranda</a><span>/</span><span>Cara Kerja</span></nav>
    <span class="overline">CARA KERJA</span>
    <h1>Dari Cari MUA Sampai <span>Siap Tampil</span></h1>
    <p>GlamLink mempertemukan dua pihak: klien yang butuh riasan, dan MUA yang siap menerima pesanan. Pilih alur yang sesuai denganmu.</p>
</section>

<section class="in-section">
    <div class="in-wrap">
        <div class="in-tabs-wrap">
            <div class="in-tabs" role="tablist" aria-label="Pilih alur">
                <button type="button" class="in-tab <?php echo $untuk === 'klien' ? 'active' : ''; ?>" role="tab" data-tab="klien" aria-selected="<?php echo $untuk === 'klien' ? 'true' : 'false'; ?>">Untuk Klien</button>
                <button type="button" class="in-tab <?php echo $untuk === 'mua' ? 'active' : ''; ?>" role="tab" data-tab="mua" aria-selected="<?php echo $untuk === 'mua' ? 'true' : 'false'; ?>">Untuk MUA</button>
            </div>
        </div>

        <ol class="in-steps" data-panel="klien" <?php echo $untuk === 'klien' ? '' : 'hidden'; ?>>
            <li class="in-step"><i class="fa-solid fa-magnifying-glass"></i>
                <h3>Cari MUA</h3>
                <p>Saring berdasarkan kategori acara, wilayah, kisaran harga, dan rating. Simpan MUA favoritmu untuk dibandingkan.</p></li>
            <li class="in-step"><i class="fa-regular fa-images"></i>
                <h3>Cek Profil &amp; Portfolio</h3>
                <p>Lihat paket layanan, hasil riasan, jadwal, dan ulasan klien. Ada pertanyaan? Chat langsung dengan MUA-nya.</p></li>
            <li class="in-step"><i class="fa-regular fa-calendar"></i>
                <h3>Pilih Jadwal &amp; Isi Detail</h3>
                <p>Pilih layanan, tanggal, dan jam, lalu isi nama acara, lokasi, dan catatan riasan yang kamu inginkan.</p></li>
            <li class="in-step"><i class="fa-regular fa-credit-card"></i>
                <h3>Bayar Pesanan</h3>
                <p>Bayar lewat transfer bank, e-wallet, atau kartu. Rincian harga dan biaya platform 5% tampil sebelum kamu konfirmasi.</p></li>
            <li class="in-step"><i class="fa-regular fa-circle-check"></i>
                <h3>MUA Mengonfirmasi</h3>
                <p>MUA meninjau pesananmu lalu menerimanya. Kamu mendapat notifikasi, dan jadwal tercatat di riwayat pesanan.</p></li>
            <li class="in-step"><i class="fa-regular fa-star"></i>
                <h3>Tampil Memukau &amp; Beri Ulasan</h3>
                <p>Setelah sesi selesai, konfirmasi di aplikasi dan beri rating supaya klien lain terbantu memilih.</p></li>
        </ol>

        <ol class="in-steps" data-panel="mua" <?php echo $untuk === 'mua' ? '' : 'hidden'; ?>>
            <li class="in-step"><i class="fa-regular fa-id-card"></i>
                <h3>Daftar &amp; Verifikasi</h3>
                <p>Isi data studio dan portfolio awal. Tim admin memverifikasi akunmu sebelum profilmu tampil di pencarian.</p></li>
            <li class="in-step"><i class="fa-solid fa-wand-magic-sparkles"></i>
                <h3>Lengkapi Profil</h3>
                <p>Tambahkan layanan &amp; harga, bio, area jangkauan, dan karya terbaik di portfolio agar klien yakin memilihmu.</p></li>
            <li class="in-step"><i class="fa-regular fa-calendar-xmark"></i>
                <h3>Atur Hari Cuti</h3>
                <p>Semua tanggal otomatis terbuka. Cukup tandai hari libur, bisa per tanggal, satu bulan penuh, atau libur rutin mingguan.</p></li>
            <li class="in-step"><i class="fa-regular fa-bell"></i>
                <h3>Terima Pesanan</h3>
                <p>Pesanan baru masuk ke notifikasi dan halaman Booking. Kamu yang memutuskan: terima atau tolak.</p></li>
            <li class="in-step"><i class="fa-solid fa-camera"></i>
                <h3>Rias &amp; Unggah Dokumentasi</h3>
                <p>Setelah sesi selesai, unggah foto dokumentasi. Foto itu juga bisa langsung dijadikan postingan portfolio.</p></li>
            <li class="in-step"><i class="fa-solid fa-wallet"></i>
                <h3>Dana Masuk ke Saldo</h3>
                <p>Pembayaran pesanan yang selesai masuk ke saldo dompetmu dan bisa ditarik ke rekening bank atau e-wallet.</p></li>
        </ol>
    </div>
</section>

<section class="cta-section">
    <h2>Siap Mencoba?</h2>
    <p>Daftar gratis sebagai klien untuk mulai memesan, atau sebagai MUA untuk mulai menerima pesanan.</p>
    <div class="cta-buttons">
        <a href="<?php echo site_e(site_link_mulai()); ?>" class="btn-filled">Mulai Sekarang</a>
        <a href="mitra.php" class="btn-outline">Keuntungan Jadi Mitra MUA</a>
    </div>
</section>

<script>
    // Tukar alur Klien / MUA tanpa reload, URL ikut berubah (?untuk=mua) agar bisa dibagikan
    document.querySelectorAll('.in-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            var pilih = tab.dataset.tab;
            document.querySelectorAll('.in-tab').forEach(function (t) {
                t.classList.toggle('active', t === tab);
                t.setAttribute('aria-selected', t === tab ? 'true' : 'false');
            });
            document.querySelectorAll('[data-panel]').forEach(function (p) { p.hidden = p.dataset.panel !== pilih; });
            var url = new URL(window.location.href);
            if (pilih === 'mua') url.searchParams.set('untuk', 'mua'); else url.searchParams.delete('untuk');
            history.replaceState(null, '', url);
        });
    });
</script>

<?php include 'includes/info_foot.php'; ?>
