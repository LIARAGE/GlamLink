<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php?pesan=belum_login");
    exit();
}

include '../koneksi.php';

// Hitung Statistik
$total_klien = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as jml FROM users WHERE role='klien'"))['jml'];
$total_mua = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as jml FROM users WHERE role='mua' AND status_akun='Terverifikasi'"))['jml'];
$menunggu_verif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as jml FROM users WHERE role='mua' AND status_akun='Menunggu'"))['jml'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - GlamLink</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <!-- Navbar -->
    <header class="admin-navbar">
        <div class="logo"><strong>GlamLink</strong></div>
        <nav class="nav-links">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="kelola_pengguna.php">Kelola Pengguna</a>
            <a href="verifikasi_mua.php">Verifikasi MUA</a>
        </nav>
        <div class="admin-profile">
            <span>Admin</span> | <a href="../logout.php" style="color: #C62828; text-decoration: none;">Logout</a>
        </div>
    </header>

    <main class="main-container">
        <h1>Selamat Datang, Admin</h1>
        <p>Pantau seluruh aktivitas pendaftaran MUA, transaksi pemesanan, dan verifikasi dokumen.</p>

        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 30px;">
            <div class="card-stat">
                <p style="color: #7A6F68; font-size: 14px;">Total Klien Terdaftar</p>
                <h2 style="font-size: 32px; margin: 10px 0;"><?= $total_klien; ?></h2>
            </div>
            <div class="card-stat">
                <p style="color: #7A6F68; font-size: 14px;">Total MUA Terdaftar</p>
                <h2 style="font-size: 32px; margin: 10px 0;"><?= $total_mua; ?></h2>
            </div>
            <div class="card-stat">
                <p style="color: #7A6F68; font-size: 14px;">Menunggu Verifikasi</p>
                <h2 style="font-size: 32px; margin: 10px 0; color: #F57F17;"><?= $menunggu_verif; ?></h2>
            </div>
        </div>
    </main>
</body>
</html>