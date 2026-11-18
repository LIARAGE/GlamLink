<?php
/**
 * SYARAT & KETENTUAN (link footer)
 * DRAF untuk keperluan proyek. Bukan nasihat hukum; tinjau ulang sebelum dipakai sungguhan.
 */
session_start();
require 'koneksi.php';

$bagian = [
    'umum'       => 'Ketentuan Umum',
    'akun'       => 'Akun Pengguna',
    'klien'      => 'Ketentuan untuk Klien',
    'mua'        => 'Ketentuan untuk MUA',
    'bayar'      => 'Pembayaran & Biaya',
    'batal'      => 'Pembatalan',
    'konten'     => 'Konten & Ulasan',
    'larangan'   => 'Larangan',
    'tanggung'   => 'Batas Tanggung Jawab',
    'ubah'       => 'Perubahan Ketentuan',
];

$judul_halaman = 'Syarat & Ketentuan';
$deskripsi_halaman = 'Syarat dan ketentuan penggunaan platform GlamLink.';
include 'includes/info_head.php';
?>

<section class="in-hero">
    <nav class="in-crumb" aria-label="Breadcrumb"><a href="index.php">Beranda</a><span>/</span><span>Syarat &amp; Ketentuan</span></nav>
    <span class="overline">LEGAL</span>
    <h1>Syarat &amp; <span>Ketentuan</span></h1>
    <p>Aturan main yang berlaku bagi klien dan MUA saat menggunakan GlamLink.</p>
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

            <h2 id="umum">1. Ketentuan Umum</h2>
            <p>GlamLink adalah platform yang mempertemukan klien dengan make-up artist (MUA). GlamLink bukan penyedia jasa rias; layanan rias diberikan langsung oleh MUA yang terdaftar. Dengan membuat akun atau menggunakan GlamLink, kamu dianggap telah membaca dan menyetujui ketentuan ini serta <a href="privasi.php">Kebijakan Privasi</a>.</p>

            <h2 id="akun">2. Akun Pengguna</h2>
            <ul>
                <li>Data yang kamu daftarkan harus benar dan milikmu sendiri.</li>
                <li>Kamu bertanggung jawab menjaga kerahasiaan password. Untuk keamanan, akun dikunci sementara setelah beberapa kali percobaan masuk yang gagal.</li>
                <li>Satu email hanya dapat digunakan untuk satu akun.</li>
            </ul>

            <h2 id="klien">3. Ketentuan untuk Klien</h2>
            <ul>
                <li>Isi detail acara (tanggal, jam, lokasi, catatan) dengan lengkap dan akurat saat memesan.</li>
                <li>Pesanan baru dianggap terkonfirmasi setelah MUA menerimanya.</li>
                <li>Ulasan hanya dapat diberikan untuk pesanan yang sudah selesai.</li>
            </ul>

            <h2 id="mua">4. Ketentuan untuk MUA</h2>
            <ul>
                <li>Akun MUA harus diverifikasi admin sebelum tampil di pencarian. Admin berhak menolak pendaftaran dengan menyertakan alasan.</li>
                <li>Portfolio yang diunggah harus merupakan hasil karya sendiri.</li>
                <li>MUA wajib menanggapi pesanan masuk dan menjaga hari cuti tetap diperbarui agar klien tidak memesan di tanggal yang tidak tersedia.</li>
                <li>Harga layanan yang tercantum adalah harga yang berlaku bagi klien.</li>
            </ul>

            <h2 id="bayar">5. Pembayaran &amp; Biaya</h2>
            <ul>
                <li>Klien membayar harga paket ditambah biaya layanan platform sebesar 5% yang ditampilkan sebelum pembayaran.</li>
                <li>Pendapatan MUA masuk ke saldo setelah pesanan berstatus selesai dan dapat ditarik melalui menu Keuangan dengan nominal minimum yang berlaku.</li>
                <li>Paket langganan mitra bersifat opsional dan berlaku sesuai durasi paket yang dipilih.</li>
            </ul>

            <h2 id="batal">6. Pembatalan</h2>
            <p>Selama pesanan masih berstatus <b>Menunggu</b>, klien dapat membatalkannya dan MUA dapat menolaknya. Pesanan yang sudah dikonfirmasi tidak dapat dibatalkan melalui aplikasi. Ketentuan pengembalian dana untuk pesanan yang dibatalkan akan diumumkan terpisah. Untuk kendala pesanan, hubungi kami melalui <a href="bantuan.php#kontak">Kontak Bantuan</a>.</p>

            <h2 id="konten">7. Konten &amp; Ulasan</h2>
            <p>Pengguna tetap memiliki hak atas foto dan teks yang diunggah, namun memberi GlamLink izin menampilkannya di platform untuk keperluan layanan. Ulasan harus jujur dan berdasarkan pengalaman nyata.</p>

            <h2 id="larangan">8. Larangan</h2>
            <ul>
                <li>Menggunakan foto karya orang lain tanpa izin.</li>
                <li>Memberikan ulasan palsu atau menyesatkan.</li>
                <li>Melakukan pelecehan, penipuan, atau tindakan yang melanggar hukum melalui fitur chat maupun pesanan.</li>
            </ul>
            <p>Pelanggaran dapat dilaporkan melalui fitur Laporkan pada profil MUA dan dapat berujung pada penangguhan akun.</p>

            <h2 id="tanggung">9. Batas Tanggung Jawab</h2>
            <p>GlamLink berupaya menjaga kualitas melalui verifikasi dan ulasan, tetapi hasil riasan serta kesepakatan teknis di lokasi acara merupakan tanggung jawab MUA dan klien yang bersangkutan.</p>

            <h2 id="ubah">10. Perubahan Ketentuan</h2>
            <p>Ketentuan ini dapat diperbarui sewaktu-waktu. Tanggal pembaruan terakhir selalu tercantum di bagian atas halaman ini.</p>
        </article>
    </div>
</section>

<?php include 'includes/info_foot.php'; ?>
