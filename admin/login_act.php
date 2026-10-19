<?php
// Memanggil file koneksi yang berada satu tingkat di luar folder admin
session_start();
require '../koneksi.php';

// Pastikan koneksi berhasil menggunakan variabel $koneksi
if (!isset($koneksi) || !$koneksi) {
    die("Koneksi gagal terhubung ke database.");
}

$email = mysqli_real_escape_string($koneksi, $_POST['email']);
$password = $_POST['password'];

// Cek data admin di database
$query = mysqli_query($koneksi, "SELECT * FROM users WHERE email='$email' AND role='admin' LIMIT 1");
$admin = mysqli_fetch_assoc($query);

if ($admin) {
    // Verifikasi password
    if ($password === $admin['password'] || password_verify($password, $admin['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_nama']      = $admin['nama'];
        $_SESSION['admin_email']     = $admin['email'];

        header("Location: dashboard.php");
        exit();
    }
}

// Jika gagal login
header("Location: login.php?pesan=gagal");
exit();
?>