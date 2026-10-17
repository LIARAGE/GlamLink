<?php
session_start();
require '../koneksi.php';

// 1. OTENTIKASI: Cek sesi MUA
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'mua') {
    header("Location: ../login.php");
    exit();
}

$id_mua = $_SESSION['id_user']; 

// 2. FETCH DAFTAR LAYANAN
$query_services = mysqli_query($koneksi, "SELECT * FROM services WHERE id_mua = '$id_mua' ORDER BY id_service DESC") or die('Error Services: ' . mysqli_error($koneksi));
$services = [];
if ($query_services) {
    while ($row = mysqli_fetch_assoc($query_services)) {
        $services[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layanan Saya - GlamLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <!-- File CSS khusus halaman Services -->
    <link rel="stylesheet" href="../assets/css/mua_services.css">
</head>
<body>

    <!-- Memanggil Navbar Terpusat -->
    <?php include '../navbar.php'; ?>

    <main class="services-main">
        
        <div class="page-header">
            <h2>Layanan Saya</h2>
            <button class="btn-add-service"><i class="fa-solid fa-plus"></i> Tambah Layanan</button>
        </div>

        <div class="services-grid">
            <?php if (count($services) > 0): ?>
                <?php foreach ($services as $srv): ?>
                    <div class="service-card">
                        <div class="service-content">
                            <h3><?php echo htmlspecialchars($srv['nama_layanan']); ?></h3>
                            <!-- Menampilkan deskripsi/kategori sebagai subtitle -->
                            <p class="service-category"><?php echo htmlspecialchars($srv['deskripsi'] ?? $srv['kategori']); ?></p>
                            <p class="service-duration">
                                <i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($srv['durasi']); ?> pengerjaan
                            </p>
                        </div>
                        
                        <div class="service-footer">
                            <div class="price-container">
                                <span class="price-label">Tarif Paket</span>
                                <span class="price-value">Rp <?php echo number_format($srv['harga'], 0, ',', '.'); ?></span>
                            </div>
                            <div class="action-buttons">
                                <button class="btn-edit">Edit</button>
                                <button class="btn-delete"><i class="fa-regular fa-trash-can"></i></button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>Anda belum menambahkan layanan apapun.</p>
                </div>
            <?php endif; ?>
        </div>

    </main>

    <!-- Script sederhana untuk mengubah status 'active' di navbar -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const navLinks = document.querySelectorAll('.nav-menu-mua a');
            navLinks.forEach(link => link.classList.remove('active'));
            // Set link kedua (Services) menjadi aktif
            if(navLinks[1]) navLinks[1].classList.add('active');
        });
    </script>
</body>
</html>