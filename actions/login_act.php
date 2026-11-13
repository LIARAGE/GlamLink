<?php
// 1. Wajib panggil session_start() di baris paling atas untuk memulai sesi
session_start();
require __DIR__ . '/../koneksi.php';

/**
 * Kembali ke halaman login dengan pesan error yang ditampilkan di dalam kartu login
 * (pengganti alert() bawaan browser). Email yang sudah diketik ikut dikembalikan.
 */
function gagal_login(string $pesan, string $email = ''): void {
    $_SESSION['login_error'] = ['pesan' => $pesan, 'email' => $email];
    header('Location: ../login.php');
    exit();
}

if (!isset($_POST['login'])) {
    header('Location: ../login.php');
    exit();
}

$email    = trim($_POST['email'] ?? '');
$password = (string)($_POST['password'] ?? '');

// Batasi percobaan: 5x gagal -> tunggu 5 menit (mencegah tebak-tebakan password)
$_SESSION['login_gagal'] = array_filter($_SESSION['login_gagal'] ?? [], fn($t) => $t > time() - 300);
if (count($_SESSION['login_gagal']) >= 5) {
    $tunggu = (int)ceil((min($_SESSION['login_gagal']) + 300 - time()) / 60);
    gagal_login("Terlalu banyak percobaan masuk. Coba lagi dalam $tunggu menit.", $email);
}

if ($email === '' || $password === '') {
    gagal_login('Email dan password wajib diisi.', $email);
}

// 2. Cari user berdasarkan email (prepared statement, aman dari SQL injection)
$st = $koneksi->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$st->bind_param('s', $email);
$st->execute();
$user = $st->get_result()->fetch_assoc();

// 3 & 4. Email harus terdaftar DAN password cocok dengan hash di database
if (!$user || !password_verify($password, $user['password'])) {
    $_SESSION['login_gagal'][] = time();
    $sisa = 5 - count($_SESSION['login_gagal']);
    // Pesan sengaja dibuat sama untuk email salah & password salah,
    // supaya orang lain tidak bisa menebak email mana yang terdaftar.
    if ($sisa <= 0)      gagal_login('Email atau password salah. Terlalu banyak percobaan, coba lagi dalam 5 menit.', $email);
    elseif ($sisa <= 2)  gagal_login("Email atau password yang kamu masukkan salah. Sisa $sisa percobaan lagi.", $email);
    else                 gagal_login('Email atau password yang kamu masukkan salah.', $email);
}

// 5. Simpan data penting ke dalam Session
session_regenerate_id(true);           // cegah session fixation
unset($_SESSION['login_gagal']);
$_SESSION['id_user']      = $user['id_user'];
$_SESSION['nama']         = $user['nama'];
$_SESSION['role']         = $user['role'];
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