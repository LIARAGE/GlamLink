<?php
session_start();
include '../koneksi.php';

$email = mysqli_real_escape_string($conn, $_POST['email']);
$password = $_POST['password'];

// Cari user berdasarkan email dan role 'admin'
$query = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' AND role='admin' LIMIT 1");
$admin = mysqli_fetch_assoc($query);

if ($admin) {
    // Verifikasi password (baik hash password_hash() maupun plain text untuk testing)
    if (password_verify($password, $admin['password']) || $password === $admin['password']) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id']        = $admin['id'];
        $_SESSION['admin_nama']      = $admin['nama'];
        $_SESSION['admin_email']     = $admin['email'];

        header("Location: dashboard.php");
        exit();
    } else {
        header("Location: login.php?pesan=gagal");
        exit();
    }
} else {
    header("Location: login.php?pesan=gagal");
    exit();
}
?>