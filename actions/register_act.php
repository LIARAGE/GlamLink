<?php
// Proses pendaftaran KLIEN (pelanggan). Pendaftaran MUA ada di register_mua_act.php
session_start();
require __DIR__ . '/../koneksi.php';

// Hanya menerima kiriman dari form; akses langsung diarahkan ke halaman pilih peran
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['register'])) {
    header('Location: ../register.php');
    exit();
}

$nama     = trim($_POST['nama'] ?? '');
$email    = strtolower(trim($_POST['email'] ?? ''));
$telepon  = preg_replace('/[\s\-]/', '', trim($_POST['nomor_telepon'] ?? ''));
$password = $_POST['password'] ?? '';
$konfirm  = $_POST['confirm_password'] ?? '';

// Simpan isian (TANPA password) supaya form terisi kembali jika ada yang salah
$_SESSION['reg_klien_old'] = ['nama' => $nama, 'email' => $email, 'nomor_telepon' => $telepon];

function kembali($kode) {
    header('Location: ../register_form.php?role=pelanggan&err=' . $kode);
    exit();
}

// Validasi di SERVER (validasi JavaScript saja bisa dilewati)
if ($nama === '' || mb_strlen($nama) > 100)                      kembali('nama');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) kembali('email');
if (!preg_match('/^(\+62|62|0)8[0-9]{8,11}$/', $telepon))        kembali('telepon');
if (strlen($password) < 8)                                       kembali('password_pendek');
if ($password !== $konfirm)                                      kembali('password');

try {
    // Email sudah dipakai?
    $cek = $koneksi->prepare('SELECT 1 FROM users WHERE email = ?');
    $cek->bind_param('s', $email);
    $cek->execute();
    if ($cek->get_result()->num_rows > 0) kembali('email_dipakai');

    // Role SELALU 'pelanggan': tidak boleh diambil dari form (kalau tidak, siapa pun bisa mendaftar sebagai admin)
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $st = $koneksi->prepare("INSERT INTO users (nama, email, nomor_telepon, password, role) VALUES (?, ?, ?, ?, 'pelanggan')");
    $st->bind_param('ssss', $nama, $email, $telepon, $hash);
    $st->execute();
} catch (Throwable $e) {
    kembali($e->getCode() == 1062 ? 'email_dipakai' : 'gagal'); // detail error tidak ditampilkan ke pengguna
}

unset($_SESSION['reg_klien_old']);
echo "<script>
        alert('Registrasi berhasil! Silakan login.');
        window.location.href = '../login.php';
      </script>";