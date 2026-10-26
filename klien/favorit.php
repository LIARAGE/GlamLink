<?php
// Tambah / hapus MUA dari favorit (dipanggil dari tombol hati di halaman detail MUA)
session_start();
require __DIR__ . '/../Includes/klien.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: cari.php'); exit(); }
$id  = (int)($_POST['id_mua'] ?? 0);
$tab = in_array($_POST['tab'] ?? '', ['tentang', 'layanan', 'portfolio', 'jadwal', 'review'], true) ? $_POST['tab'] : 'tentang';
$tgl = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['tanggal'] ?? '') ? '&tanggal=' . $_POST['tanggal'] : '';
$kembali = 'mua.php?id=' . $id . '&tab=' . $tab . $tgl;

$ada = q("SELECT 1 FROM users WHERE id_user = ? AND role = 'mua' AND status_verifikasi = 'approved'", [$id]);
if (!csrf_ok() || !$ada) { header('Location: ' . $kembali . '&info=gagal'); exit(); }

if (q('SELECT 1 FROM favorit WHERE id_pelanggan = ? AND id_mua = ?', [$id_klien, $id])) {
    q('DELETE FROM favorit WHERE id_pelanggan = ? AND id_mua = ?', [$id_klien, $id]);
    $info = 'fav_off';
} else {
    try { q('INSERT INTO favorit (id_pelanggan, id_mua) VALUES (?, ?)', [$id_klien, $id]); } catch (Throwable $e) { /* klik ganda: abaikan */ }
    $info = 'fav_on';
}
header('Location: ' . $kembali . '&info=' . $info);