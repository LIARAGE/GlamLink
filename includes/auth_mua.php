<?php
/**
 * Penjaga halaman MUA. Panggil di baris awal setiap halaman folder /mua/
 * (setelah session_start() dan koneksi). Menghasilkan: $id_mua, $status_verifikasi, $akun.
 *  - Belum login / bukan MUA        -> ke halaman login
 *  - MUA belum disetujui admin      -> ke mua/status.php (kecuali sudah di halaman itu)
 * Status dicek ke database setiap halaman dibuka, jadi begitu admin menyetujui,
 * MUA langsung bisa masuk tanpa login ulang.
 */
require_once __DIR__ . '/../koneksi.php';

if (!isset($_SESSION['status_login']) || ($_SESSION['role'] ?? '') !== 'mua') {
    header('Location: ../login.php');
    exit();
}

$id_mua = (int)$_SESSION['id_user'];
$st = $koneksi->prepare('SELECT status_verifikasi, alasan_tolak FROM users WHERE id_user = ?');
$st->bind_param('i', $id_mua);
$st->execute();
$akun = $st->get_result()->fetch_assoc();
$status_verifikasi = $akun['status_verifikasi'] ?? 'pending';

if ($status_verifikasi !== 'approved' && basename($_SERVER['SCRIPT_NAME']) !== 'status.php') {
    header('Location: status.php');
    exit();
}

// Langganan yang tanggal_berakhir-nya sudah lewat otomatis ditandai 'kedaluwarsa'
// (dijalankan setiap halaman MUA dibuka, jadi status di database selalu sesuai tanggal)
$st_exp = $koneksi->prepare("UPDATE mua_subscriptions SET status = 'kedaluwarsa' WHERE id_mua = ? AND status = 'aktif' AND tanggal_berakhir < CURDATE()");
$st_exp->bind_param('i', $id_mua);
$st_exp->execute();