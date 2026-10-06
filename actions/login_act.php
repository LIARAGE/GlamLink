<?php
// 1. Wajib panggil session_start() di baris paling atas untuk memulai sesi
session_start();
require '../koneksi.php';

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = $_POST['password'];

    // 2. Cari user berdasarkan email
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($koneksi, $query);

    // 3. Jika email ditemukan (jumlah baris == 1)
    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        
        // 4. Verifikasi password (mencocokkan password ketikan dengan password acak di database)
        if (password_verify($password, $user['password'])) {
            
            // 5. Simpan data penting ke dalam Session
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['status_login'] = true;

            // 6. Arahkan halaman sesuai role
            if ($user['role'] == 'admin') {
                header("Location: ../admin/index.php");
            } else if ($user['role'] == 'mua') {
                header("Location: ../mua/index.php");
            } else {
                header("Location: ../beranda.php"); // Halaman utama pelanggan
            }
            exit();

        } else {
            echo "<script>alert('Password salah!'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Email tidak terdaftar!'); window.history.back();</script>";
    }
}
?>