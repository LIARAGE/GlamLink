<?php
session_start();
require __DIR__ . '/../koneksi.php';

// Wajib datang dari tombol "Selesaikan Pendaftaran" dan data langkah 1 & 3 harus ada
if (!isset($_POST['selesai'], $_SESSION['reg_mua_step1'], $_SESSION['reg_mua_step3'])) {
    header('Location: ../register_form.php?role=mua'); exit();
}
$s1 = $_SESSION['reg_mua_step1'];
$s2 = $_SESSION['reg_mua_step2'] ?? [];
$s3 = $_SESSION['reg_mua_step3'];
$foto  = $s1['foto_profil'] ?? '';
$harga = (int)$s3['harga'];

// Transaksi: user + portfolio + layanan masuk SEMUA, atau tidak sama sekali
$koneksi->begin_transaction();
try {
    $st = $koneksi->prepare("INSERT INTO users (nama, email, password, role, nomor_telepon, nama_brand, bio, lokasi, foto_profil) VALUES (?, ?, ?, 'mua', ?, ?, ?, ?, ?)");
    $st->bind_param('ssssssss', $s1['nama'], $s1['email'], $s1['password_hash'], $s1['nomor_telepon'], $s1['nama_brand'], $s1['bio'], $s1['lokasi'], $foto);
    $st->execute();
    $id_mua = $koneksi->insert_id;

    $p = $koneksi->prepare("INSERT INTO portfolios (id_mua, foto) VALUES (?, ?)");
    foreach ($s2['portfolio'] ?? [] as $foto_port) { $p->bind_param('is', $id_mua, $foto_port); $p->execute(); }

    $sv = $koneksi->prepare("INSERT INTO services (id_mua, nama_layanan, kategori, harga, durasi, deskripsi) VALUES (?, ?, ?, ?, ?, ?)");
    $sv->bind_param('ississ', $id_mua, $s3['nama_layanan'], $s3['kategori'], $harga, $s3['durasi'], $s3['deskripsi_layanan']);
    $sv->execute();

    $koneksi->commit();
    unset($_SESSION['reg_mua_step1'], $_SESSION['reg_mua_step2'], $_SESSION['reg_mua_step3']);
    echo "<script>alert('Pendaftaran MUA berhasil! Silakan masuk.'); window.location.href = '../login.php';</script>";
} catch (Throwable $e) {
    $koneksi->rollback();
    $pesan = ($e->getCode() == 1062) ? 'Email sudah terdaftar!' : 'Pendaftaran gagal, silakan coba lagi.'; // detail error tidak ditampilkan ke pengguna
    echo "<script>alert('$pesan'); window.location.href = '../register_form.php?role=mua';</script>";
}