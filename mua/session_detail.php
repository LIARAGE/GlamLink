<?php
session_start();
// auth_mua.php: koneksi + cek login MUA + blokir MUA yang belum di-approve, mengisi $id_mua (integer)
require_once '../includes/auth_mua.php';
require_once '../includes/upload.php';
require_once '../includes/mua_ui.php'; // toast notifikasi

$id_booking = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --- PROSES UPDATE FOTO DOKUMENTASI ---
if (isset($_FILES['update_foto']) && $_FILES['update_foto']['error'] !== UPLOAD_ERR_NO_FILE) {
    // simpan_gambar() mengecek isi file (JPG/PNG/WEBP asli), bukan hanya ekstensi nama file
    $alasan = null;
    $nama_foto = simpan_gambar($_FILES['update_foto'], 'dokum_' . $id_booking, 10, $alasan);

    if ($nama_foto) {
        $st = $koneksi->prepare("UPDATE bookings SET foto_dokumentasi = ? WHERE id_booking = ? AND id_mua = ?");
        $st->bind_param('sii', $nama_foto, $id_booking, $id_mua);
        $st->execute();
        mua_toast('Foto dokumentasi berhasil diperbarui!', 'success');
    } else {
        mua_toast('Foto gagal diunggah: ' . strip_tags(pesan_upload($alasan)), 'error');
    }
    // Redirect supaya refresh halaman tidak mengirim ulang file
    header("Location: session_detail.php?id=$id_booking");
    exit();
}

// Ambil detail pesanan spesifik
$query = mysqli_query($koneksi, "
    SELECT b.*, u.nama AS nama_klien, u.email AS email_klien, u.nomor_telepon, u.foto_profil AS foto_klien, 
           s.nama_layanan, s.kategori 
    FROM bookings b 
    LEFT JOIN users u ON b.id_pelanggan = u.id_user 
    LEFT JOIN services s ON b.id_layanan = s.id_service 
    WHERE b.id_booking = '$id_booking' AND b.id_mua = '$id_mua'
");

$detail = mysqli_fetch_assoc($query);

if (!$detail) {
    mua_toast('Data pesanan tidak ditemukan!', 'error');
    header("Location: booking.php?tab=done");
    exit();
}

// Persiapkan variabel untuk mencegah error jika data kosong
$foto_dokum = !empty($detail['foto_dokumentasi']) ? $detail['foto_dokumentasi'] : 'default_dokum.jpg';
$kategori = !empty($detail['kategori']) ? $detail['kategori'] : 'Layanan';
$rating = !empty($detail['rating']) ? number_format($detail['rating'], 1) : '-';
$ulasan = !empty($detail['ulasan']) ? $detail['ulasan'] : 'Klien belum memberikan ulasan.';
$waktu_selesai = date('H.i', strtotime($detail['waktu_booking'])); 
$tanggal_selesai = date('d/m/Y', strtotime($detail['tanggal_booking']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Detail - GlamLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/mua_booking.css">
    <link rel="stylesheet" href="../assets/css/mua_ui.css">
</head>
<body>

    <?php include '../navbar.php'; ?>

    <!-- Gradient Header Area -->
    <div class="detail-top-bg">
        <div class="detail-container">
            <a href="booking.php?tab=done" class="btn-back"><i class="fa-solid fa-arrow-left"></i></a>
            <h2>Session Detail</h2>
        </div>
    </div>

    <main class="detail-container" style="margin-top: -60px; padding-bottom: 50px;">
        <div class="sc-card">
            <!-- Kolom Kiri: Foto Dokumentasi -->
            <div class="sc-left" style="background-image: url('../assets/img/<?php echo htmlspecialchars($foto_dokum); ?>');">
                <div class="sc-badges-top">
                    <span class="badge-foto"><i class="fa-solid fa-camera"></i> Foto Dokumentasi Sesi</span>
                    <span class="badge-kategori"><?php echo htmlspecialchars($kategori); ?></span>
                </div>
                <div class="sc-kode-bottom">
                    <span class="k-label">KODE SESI</span>
                    <span class="k-val"><?php echo htmlspecialchars($detail['no_invoice']); ?></span>
                </div>
            </div>

            <!-- Kolom Kanan: Rincian Data -->
            <div class="sc-right">
                
                <!-- Info Klien & Harga -->
                <div class="sc-header-row">
                    <div class="sc-client-info">
                        <img src="../assets/img/<?php echo htmlspecialchars($detail['foto_klien'] ?? 'default.jpg'); ?>" alt="Klien">
                        <div>
                            <h3><?php echo htmlspecialchars($detail['nama_klien']); ?> <span class="badge-lunas"><i class="fa-solid fa-check"></i> Lunas</span></h3>
                            <p><?php echo htmlspecialchars($detail['nomor_telepon'] ?? '-'); ?> &middot; <?php echo htmlspecialchars($detail['email_klien']); ?></p>
                        </div>
                    </div>
                    <div class="sc-price-info">
                        <span class="p-label">Tarif Selesai</span>
                        <span class="p-val">Rp <?php echo number_format($detail['total_harga'], 0, ',', '.'); ?></span>
                    </div>
                </div>

                <!-- Grid Layanan & Lokasi -->
                <div class="sc-grid-info">
                    <div class="sc-box">
                        <span class="b-label">LAYANAN RIAS</span>
                        <span class="b-title"><?php echo htmlspecialchars($detail['nama_layanan']); ?></span>
                        <span class="b-desc"><i class="fa-regular fa-calendar"></i> <?php echo $tanggal_selesai; ?> &nbsp;&nbsp; <i class="fa-regular fa-clock"></i> <?php echo date('H:i', strtotime($detail['waktu_booking'])); ?> WIB</span>
                    </div>
                    <div class="sc-box">
                        <span class="b-label">LOKASI ACARA / VENUE</span>
                        <span class="b-desc" style="margin-top:5px; color:#2b2326;"><i class="fa-solid fa-location-dot" style="color:#7E4655;"></i> <?php echo htmlspecialchars($detail['lokasi_acara']); ?></span>
                    </div>
                </div>

                <!-- Box Rating -->
                <div class="sc-rating-container">
                    <span class="r-label">RATING</span>
                    <div class="r-content">
                        <div class="r-badge"><i class="fa-solid fa-star"></i> <?php echo $rating; ?></div>
                        <div class="r-text"><?php echo htmlspecialchars($ulasan); ?></div>
                    </div>
                </div>

                <!-- Footer Card -->
                <div class="sc-footer">
                    <div class="sf-time">Selesai pada: <strong><?php echo $waktu_selesai; ?> &middot; <?php echo $tanggal_selesai; ?></strong></div>
                    <div class="sf-actions">
                        <!-- Form Upload Foto Tersembunyi -->
                        <form method="POST" enctype="multipart/form-data" style="margin: 0; display: inline-block;">
                            <input type="file" name="update_foto" id="fileUpdateDokum" style="display: none;" accept="image/png, image/jpeg, image/jpg, image/webp" data-preview-bg=".sc-left" data-auto-submit>
                            <button type="button" class="btn-outline-grey" data-loading-button data-loading-text="Mengunggah..." onclick="document.getElementById('fileUpdateDokum').click();">
                                <i class="fa-solid fa-camera"></i> Ganti / Tambah Foto
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <?php mua_toast_render(); ?>
    <script src="../assets/js/mua_ui.js"></script>
</body>
</html>