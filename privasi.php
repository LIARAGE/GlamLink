<?php
/**
 * KEBIJAKAN PRIVASI (link footer)
 * DRAF untuk keperluan proyek. Bukan nasihat hukum.
 * Daftar data di bawah disesuaikan dengan kolom yang benar-benar disimpan di database GlamLink.
 */
session_start();
require 'koneksi.php';

$bagian = [
    'data'     => 'Data yang Kami Kumpulkan',
    'guna'     => 'Cara Kami Menggunakan Data',
    'bagi'     => 'Data yang Terlihat oleh Pengguna Lain',
    'aman'     => 'Keamanan Data',
    'simpan'   => 'Penyimpanan Data',
    'hak'      => 'Hak Kamu',
    'ubah'     => 'Perubahan Kebijakan',
];

$judul_halaman = 'Kebijakan Privasi';
$deskripsi_halaman = 'Bagaimana GlamLink mengumpulkan, menggunakan, dan melindungi data pribadimu.';
include 'includes/info_head.php';
?>

<section class="in-hero">
    <nav class="in-crumb" aria-label="Breadcrumb"><a href="index.php">Beranda</a><span>/</span><span>Kebijakan Privasi</span></nav>
    <span class="overline">LEGAL</span>
    <h1>Kebijakan <span>Privasi</span></h1>
    <p>Kami hanya mengumpulkan data yang dibutuhkan agar pemesanan berjalan lancar, dan menjaganya dengan serius.</p>
</section>

<section class="in-section">
    <div class="in-wrap in-legal">
        <aside class="in-toc">
            <b>Daftar Isi</b>
            <ol>
                <?php foreach ($bagian as $id => $judul): ?>
                    <li><a href="#<?php echo $id; ?>"><?php echo $judul; ?></a></li>
                <?php endforeach; ?>
            </ol>
        </aside>

        <article class="in-prose">
            <span class="in-update">Terakhir diperbarui: 18 November 2026</span>
            <div class="in-note">Kebijakan ini disusun dengan mengacu pada Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi.</div>

            <h2 id="data">1. Data yang Kami Kumpulkan</h2>
            <ul>
                <li><b>Data akun:</b> nama, email, nomor telepon, foto profil, dan password (disimpan dalam bentuk terenkripsi/hash).</li>
                <li><b>Data profil MUA:</b> nama brand, bio, lokasi atau wilayah layanan, daftar layanan dan harga, portfolio, serta hari cuti.</li>
                <li><b>Data pesanan:</b> nama dan jenis acara, tanggal dan jam, kota dan lokasi acara, catatan, metode pembayaran, foto dokumentasi, rating, ulasan, dan status pesanan.</li>
                <li><b>Data keuangan MUA:</b> riwayat pendapatan serta bank/e-wallet tujuan, nomor rekening atau nomor HP, dan nama penerima untuk penarikan saldo.</li>
                <li><b>Komunikasi:</b> isi chat antara klien dan MUA, serta laporan (beserta bukti) yang kamu kirimkan.</li>
            </ul>

            <h2 id="guna">2. Cara Kami Menggunakan Data</h2>
            <ul>
                <li>Memproses pemesanan, pembayaran, dan penarikan saldo.</li>
                <li>Menampilkan profil dan portfolio MUA kepada calon klien.</li>
                <li>Mengirim notifikasi terkait pesanan, ulasan, dan langganan.</li>
                <li>Memverifikasi MUA serta meninjau laporan pelanggaran.</li>
                <li>Menjaga keamanan akun, misalnya membatasi percobaan masuk yang gagal dan memproses permintaan reset password.</li>
            </ul>

            <h2 id="bagi">3. Data yang Terlihat oleh Pengguna Lain</h2>
            <p>Profil MUA (nama brand, bio, wilayah, layanan, portfolio, rating, dan ulasan) dapat dilihat oleh klien. Detail pesanan dan isi chat hanya terlihat oleh klien dan MUA yang terlibat, serta admin bila diperlukan untuk menangani laporan. Kami tidak menjual data pribadimu kepada pihak lain.</p>

            <h2 id="aman">4. Keamanan Data</h2>
            <p>Password disimpan dalam bentuk hash, link reset password hanya berlaku 30 menit dan sekali pakai, dan file unggahan diperiksa jenisnya sebelum disimpan. Meski demikian, tidak ada sistem yang sepenuhnya bebas risiko; segera hubungi kami jika kamu mencurigai akunmu disalahgunakan.</p>

            <h2 id="simpan">5. Penyimpanan Data</h2>
            <p>Data disimpan selama akunmu aktif atau selama diperlukan untuk keperluan transaksi, penyelesaian sengketa, dan kewajiban hukum.</p>

            <h2 id="hak">6. Hak Kamu</h2>
            <ul>
                <li>Mengakses dan memperbarui data profil melalui halaman profil.</li>
                <li>Meminta koreksi atau penghapusan data pribadi.</li>
                <li>Menarik persetujuan pemrosesan data, dengan konsekuensi sebagian layanan mungkin tidak dapat digunakan.</li>
            </ul>
            <p>Untuk permintaan terkait data pribadi, hubungi kami melalui <a href="bantuan.php#kontak">Kontak Bantuan</a>.</p>

            <h2 id="ubah">7. Perubahan Kebijakan</h2>
            <p>Kebijakan ini dapat diperbarui sewaktu-waktu. Lihat <a href="syarat.php">Syarat &amp; Ketentuan</a> untuk aturan penggunaan platform.</p>
        </article>
    </div>
</section>

<?php include 'includes/info_foot.php'; ?>
