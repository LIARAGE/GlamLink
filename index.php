<?php
session_start();

// Pengecekan sesi: Jika belum login ATAU bukan pelanggan, tendang ke halaman login
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'pelanggan') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Beranda Pelanggan - GlamLink</title>
</head>
<body>
    <h1>Selamat Datang di GlamLink, <?php echo $_SESSION['nama']; ?>!</h1>
    <p>Anda saat ini login sebagai <b>Pelanggan</b>.</p>
    
    <nav>
        <!-- Nanti kita letakkan link ke halaman Chat dan Cari MUA di sini -->
        <a href="logout.php">Logout</a>
    </nav>
</body>
</html>