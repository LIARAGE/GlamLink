<?php
session_start();

// Pengecekan sesi: Jika belum login ATAU bukan mua, tendang ke halaman login
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'mua') {
    // Perhatikan penggunaan '../' karena file ini ada di dalam folder mua/
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard MUA - GlamLink</title>
</head>
<body>
    <h1>Halo MUA <?php echo $_SESSION['nama']; ?>!</h1>
    <p>Ini adalah halaman khusus untuk mengelola jasa makeup Anda.</p>
    
    <nav>
        <!-- Nanti kita letakkan link ke halaman Kelola Jasa dan Chat Klien di sini -->
        <a href="../logout.php">Logout</a>
    </nav>
</body>
</html>