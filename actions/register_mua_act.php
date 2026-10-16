<?php
session_start();
require '../koneksi.php';

if (isset($_POST['selesai']) && isset($_SESSION['reg_mua_step1'])) {
    
    // Tarik semua data dari Session
    $s1 = $_SESSION['reg_mua_step1'];
    $s2 = $_SESSION['reg_mua_step2'] ?? [];
    $s3 = $_SESSION['reg_mua_step3'];

    // Data User (Langkah 1)
    $nama = mysqli_real_escape_string($koneksi, $s1['nama']);
    $email = mysqli_real_escape_string($koneksi, $s1['email']);
    $password_hashed = password_hash($s1['password'], PASSWORD_DEFAULT);
    $nomor_telepon = mysqli_real_escape_string($koneksi, $s1['nomor_telepon']);
    $nama_brand = mysqli_real_escape_string($koneksi, $s1['nama_brand']);
    $bio = mysqli_real_escape_string($koneksi, $s1['bio']);
    $lokasi = mysqli_real_escape_string($koneksi, $s1['lokasi']);
    $foto_profil = mysqli_real_escape_string($koneksi, $s1['foto_profil'] ?? '');
    
    // Cek duplikasi email
    $cek_email = mysqli_query($koneksi, "SELECT email FROM users WHERE email = '$email'");
    if (mysqli_num_rows($cek_email) > 0) {
        echo "<script>alert('Email sudah terdaftar!'); window.location.href='../register_form.php?role=mua';</script>";
        exit();
    }

    // 1. Insert ke tabel users
    $query_user = "INSERT INTO users (nama, email, password, role, nomor_telepon, nama_brand, bio, lokasi, foto_profil) 
                   VALUES ('$nama', '$email', '$password_hashed', 'mua', '$nomor_telepon', '$nama_brand', '$bio', '$lokasi', '$foto_profil')";
    
    if (mysqli_query($koneksi, $query_user)) {
        $id_mua = mysqli_insert_id($koneksi); // Mengambil ID user yang baru saja terdaftar

        // 2. Insert ke tabel portfolios
        if (isset($s2['portfolio'])) {
            foreach ($s2['portfolio'] as $foto_port) {
                $foto_safe = mysqli_real_escape_string($koneksi, $foto_port);
                mysqli_query($koneksi, "INSERT INTO portfolios (id_mua, foto) VALUES ('$id_mua', '$foto_safe')");
            }
        }

        // 3. Insert ke tabel services
        $nama_layanan = mysqli_real_escape_string($koneksi, $s3['nama_layanan']);
        $kategori = mysqli_real_escape_string($koneksi, $s3['kategori']);
        $harga = mysqli_real_escape_string($koneksi, $s3['harga']);
        $durasi = mysqli_real_escape_string($koneksi, $s3['durasi']);
        $deskripsi = mysqli_real_escape_string($koneksi, $s3['deskripsi_layanan']);

        $query_service = "INSERT INTO services (id_mua, nama_layanan, kategori, harga, durasi, deskripsi) 
                          VALUES ('$id_mua', '$nama_layanan', '$kategori', '$harga', '$durasi', '$deskripsi')";
        mysqli_query($koneksi, $query_service);

        // Hapus session registrasi setelah sukses
        unset($_SESSION['reg_mua_step1']);
        unset($_SESSION['reg_mua_step2']);
        unset($_SESSION['reg_mua_step3']);

        echo "<script>
                alert('Pendaftaran MUA Berhasil! Silakan masuk.');
                window.location.href = '../login.php';
              </script>";
    } else {
        echo "<script>alert('Terjadi kesalahan database: " . mysqli_error($koneksi) . "'); window.history.back();</script>";
    }
}
?>