<?php
// Menyimpan laporan klien terhadap MUA (pop-up "Laporkan MUA")
session_start();
require __DIR__ . '/../Includes/klien.php';
require __DIR__ . '/../Includes/upload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: cari.php'); exit(); }
$id  = (int)($_POST['id_mua'] ?? 0);
$kembali = 'mua.php?id=' . $id;
$alasan = $_POST['alasan'] ?? '';
$detail = trim($_POST['detail'] ?? '');

if (!csrf_ok() || !q("SELECT 1 FROM users WHERE id_user = ? AND role = 'mua'", [$id])) { header('Location: ' . $kembali . '&info=lap_gagal'); exit(); }
if (!in_array($alasan, ALASAN_LAPORAN, true) || mb_strlen($detail) < 10 || mb_strlen($detail) > 2000) { header('Location: ' . $kembali . '&info=lap_isi'); exit(); }

// Satu laporan aktif per MUA: cegah pengiriman berulang
if (q("SELECT 1 FROM laporan_mua WHERE id_pelapor = ? AND id_mua = ? AND status = 'baru'", [$id_klien, $id])) { header('Location: ' . $kembali . '&info=lap_dup'); exit(); }

$bukti = '';
if (!empty($_FILES['bukti']['name'])) {
    $bukti = simpan_gambar($_FILES['bukti'], 'bukti', 5, $gagal);
    if (!$bukti) { header('Location: ' . $kembali . '&info=lap_bukti'); exit(); }
}
q('INSERT INTO laporan_mua (id_pelapor, id_mua, alasan, detail, bukti) VALUES (?, ?, ?, ?, NULLIF(?, \'\'))', [$id_klien, $id, $alasan, $detail, $bukti]);
header('Location: ' . $kembali . '&info=lap_ok');