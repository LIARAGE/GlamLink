<?php
/**
 * Penjaga + fungsi bantu untuk semua halaman klien (folder /klien/).
 * Panggil setelah session_start(): require __DIR__ . '/../Includes/klien.php';
 */
require_once __DIR__ . '/../koneksi.php';

// Wajib login sebagai klien (pelanggan)
if (!isset($_SESSION['status_login']) || ($_SESSION['role'] ?? '') !== 'pelanggan') {
    header('Location: ../login.php');
    exit();
}
$id_klien = (int)$_SESSION['id_user'];

/** Jalankan query berparameter, hasilnya array baris (aman dari SQL injection) */
function q($sql, $params = []) {
    global $koneksi;
    $st = $koneksi->prepare($sql);
    if ($params) { $st->bind_param(str_repeat('s', count($params)), ...$params); }
    $st->execute();
    $r = $st->get_result();
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}
function e($v)      { return htmlspecialchars((string)$v, ENT_QUOTES); }
function rupiah($n) { return 'Rp' . number_format((float)$n, 0, ',', '.'); }

/** Tampilkan foto dari assets/img; jika file tidak ada, tampilkan kotak ikon */
function foto($file, $kelas = '', $ikon = 'fa-user') {
    $f = basename((string)$file);
    if ($f !== '' && file_exists(__DIR__ . '/../assets/img/' . $f)) {
        return '<img class="' . $kelas . '" src="../assets/img/' . e($f) . '" alt="">';
    }
    return '<div class="' . $kelas . ' foto-kosong"><i class="fa-solid ' . $ikon . '"></i></div>';
}

/** Pilihan tetap untuk filter (sesuai desain Cari MUA & form pendaftaran MUA) */
const KATEGORI_LAYANAN = ['Bridal', 'Graduation', 'Party', 'Photoshoot'];
const WILAYAH = ['Jakarta Utara', 'Jakarta Pusat', 'Jakarta Selatan', 'Jakarta Barat', 'Jakarta Timur', 'Bogor', 'Depok', 'Tangerang', 'Bekasi'];

/** Slot jam tetap pada kalender Jadwal & form Booking (sesuai desain): [mulai, selesai] */
const SLOT_JAM = [['08:00', '10:00'], ['10:00', '12:00'], ['12:00', '14:00'], ['14:00', '16:00'], ['16:00', '18:00']];
const ALASAN_LAPORAN = ['Penipuan/Scam', 'Profil atau portfolio palsu', 'Perilaku tidak pantas', 'MUA tidak memenuhi layanan', 'MUA tidak aktif', 'Lainnya'];
const NAMA_BULAN = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const NAMA_HARI  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

/** Token CSRF untuk form POST (favorit, laporan) */
function csrf_field() { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">'; }
function csrf_ok()    { return !empty($_POST['csrf']) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']); }
/** 'HH:MM[:SS]' -> nomor slot yang memuat jam itu (0-4), atau -1 jika di luar slot */
function slot_index($jam) { $j = substr((string)$jam, 0, 5); foreach (SLOT_JAM as $i => [$a, $b]) if ($j >= $a && $j < $b) return $i; return -1; }

/** Daftar MUA yang SUDAH diverifikasi admin (+ cover, harga mulai, kategori, RATING dari bookings.rating) */
function daftar_mua($where = '', $params = [], $urut = 'rating DESC, selesai DESC, u.dibuat_pada DESC', $limit = 60) {
    $rows = q("SELECT u.id_user, u.nama_brand, u.lokasi, u.foto_profil,
                (SELECT foto FROM portfolios WHERE id_mua = u.id_user ORDER BY id_portfolio LIMIT 1) AS cover,
                (SELECT MIN(harga) FROM services WHERE id_mua = u.id_user) AS harga_min,
                (SELECT COUNT(*) FROM bookings WHERE id_mua = u.id_user AND status = 'selesai') AS selesai,
                (SELECT ROUND(AVG(rating), 1) FROM bookings WHERE id_mua = u.id_user AND rating > 0) AS rating,
                (SELECT COUNT(*) FROM bookings WHERE id_mua = u.id_user AND rating > 0) AS n_ulasan,
                (SELECT COUNT(*) FROM favorit f WHERE f.id_mua = u.id_user AND f.id_pelanggan = " . (int)($GLOBALS['id_klien'] ?? 0) . ") AS fav
              FROM users u
              WHERE u.role = 'mua' AND u.status_verifikasi = 'approved' $where
              ORDER BY $urut LIMIT " . (int)$limit, $params);
    foreach ($rows as &$m) {
        $m['kats'] = array_column(q("SELECT DISTINCT kategori FROM services WHERE id_mua = ? AND kategori <> ''", [$m['id_user']]), 'kategori');
    }
    return $rows;
}

/** Bintang 1-5 (penuh / setengah / kosong) */
function bintang($r) {
    $h = '';
    for ($i = 1; $i <= 5; $i++) {
        $h .= $r >= $i ? '<i class="fa-solid fa-star"></i>' : ($r >= $i - 0.5 ? '<i class="fa-solid fa-star-half-stroke"></i>' : '<i class="fa-regular fa-star"></i>');
    }
    return '<span class="stars">' . $h . '</span>';
}
/** Baris rating ringkas: ★ 4.9 (124 reviews) */
function rating_teks($m) {
    if (empty($m['n_ulasan'])) return '<span class="rate muted small">Belum ada ulasan</span>';
    return '<span class="rate"><i class="fa-solid fa-star"></i> <b>' . number_format((float)$m['rating'], 1) . '</b> <span class="muted">(' . (int)$m['n_ulasan'] . ' reviews)</span></span>';
}
/** "Jocelyn Nicole" -> "Jocelyn N." (nama klien di ulasan dipersingkat demi privasi) */
function nama_singkat($nama) {
    $p = preg_split('/\s+/', trim((string)$nama));
    return count($p) > 1 ? $p[0] . ' ' . mb_strtoupper(mb_substr($p[1], 0, 1)) . '.' : ($p[0] ?: 'Klien');
}
/** 0812-... / 62812... -> ['tampil' => '+62 812-3456-789', 'link' => 'https://wa.me/62812...'] */
function format_wa($tel) {
    $d = preg_replace('/\D/', '', (string)$tel);
    if ($d === '') return null;
    if (strpos($d, '0') === 0) $d = '62' . substr($d, 1); elseif (strpos($d, '62') !== 0) $d = '62' . $d;
    $r = substr($d, 2);
    return ['tampil' => rtrim('+62 ' . substr($r, 0, 3) . '-' . substr($r, 3, 4) . '-' . substr($r, 7), '-'), 'link' => 'https://wa.me/' . $d];
}

/** Kartu MUA ringkas (dipakai di Home) */
function kartu_mua($m, $tanggal = '') {
    $href = 'mua.php?id=' . $m['id_user'] . ($tanggal ? '&tanggal=' . urlencode($tanggal) : '');
    echo '<a class="mua-card" href="' . $href . '"><div class="mua-cover">' . foto($m['cover'], '', 'fa-image') . '</div><div class="mua-body">';
    echo '<div class="mua-head">' . foto($m['foto_profil'], 'avatar') . '<div><h3>' . e($m['nama_brand']) . '</h3><span class="muted"><i class="fa-solid fa-location-dot"></i> ' . e($m['lokasi']) . '</span></div></div>';
    echo '<div class="mua-rate">' . rating_teks($m) . '</div>';
    echo '<div class="chips">'; foreach (array_slice($m['kats'], 0, 3) as $k) echo '<span class="chip">#' . e($k) . '</span>'; echo '</div>';
    echo '<div class="mua-foot"><span class="muted">Mulai dari</span><b>' . ($m['harga_min'] ? rupiah($m['harga_min']) : '-') . '</b></div></div></a>';
}

/** Kartu MUA Populer di Home (sesuai desain: avatar, rating, lokasi, harga, hati, tag, 2 tombol) */
function kartu_populer($m) {
    $id  = (int)$m['id_user'];
    $fav = !empty($m['fav']);
    $aria = $fav ? 'Hapus dari favorit' : 'Simpan ke favorit';
    echo '<article class="pop-card">';
    echo '<form class="pop-fav" method="POST" action="favorit.php">' . csrf_field() . '<input type="hidden" name="id_mua" value="' . $id . '"><input type="hidden" name="dari" value="home">';
    echo '<button type="submit" class="pop-heart' . ($fav ? ' on' : '') . '" aria-pressed="' . ($fav ? 'true' : 'false') . '" aria-label="' . $aria . '" title="' . $aria . '"><i class="' . ($fav ? 'fa-solid' : 'fa-regular') . ' fa-heart"></i></button></form>';
    echo '<a class="pop-link" href="mua.php?id=' . $id . '">' . foto($m['foto_profil'], 'avatar pop') . '<div class="pop-info"><h3>' . e($m['nama_brand']) . '</h3>' . rating_teks($m);
    echo '<div class="muted small"><i class="fa-solid fa-location-dot"></i> ' . e($m['lokasi']) . '</div>';
    echo '<div class="cc-price"><span class="muted">Mulai dari</span> <b>' . ($m['harga_min'] ? rupiah($m['harga_min']) : '-') . '</b></div></div></a>';
    echo '<div class="chips pop-tags">'; foreach (array_slice($m['kats'], 0, 2) as $k) echo '<span class="pop-tag">' . e($k) . '</span>'; echo '</div>';
    echo '<div class="cc-actions"><a class="kb-outline" href="mua.php?id=' . $id . '&tab=layanan">Detail Layanan</a><a class="kb-fill" href="booking.php?mua=' . $id . '">Booking Sekarang</a></div>';
    echo '</article>';
}

/** Kartu hasil Cari MUA (sesuai desain: foto, avatar, rating, lokasi, harga, 2 tombol) */
function kartu_cari($m, $tanggal = '') {
    $t = $tanggal ? '&tanggal=' . urlencode($tanggal) : '';
    $id = (int)$m['id_user'];
    echo '<article class="cari-card"><a class="cc-cover" href="mua.php?id=' . $id . $t . '">' . foto($m['cover'], '', 'fa-image') . (!empty($m['fav']) ? '<span class="fav-badge" title="Favorit Anda"><i class="fa-solid fa-heart"></i></span>' : '') . '</a><div class="cc-body"><div class="cc-row">';
    echo '<a href="mua.php?id=' . $id . $t . '">' . foto($m['foto_profil'], 'avatar lg') . '</a><div class="cc-info"><h3>' . e($m['nama_brand']) . '</h3>' . rating_teks($m);
    echo '<div class="muted small"><i class="fa-solid fa-location-dot"></i> ' . e($m['lokasi']) . '</div>';
    echo '<div class="cc-price"><span class="muted">Mulai dari</span> <b>' . ($m['harga_min'] ? rupiah($m['harga_min']) : '-') . '</b></div></div></div>';
    echo '<div class="cc-actions"><a class="kb-outline" href="mua.php?id=' . $id . '&tab=layanan' . $t . '">Detail Layanan</a><a class="kb-fill" href="booking.php?mua=' . $id . $t . '">Booking Sekarang</a></div></div></article>';
}

/** Bagian <head> + pembuka <body>. Navbar di-include manual oleh tiap halaman. */
function head_klien($judul) {
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . e($judul) . ' - GlamLink</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/navbar.css"><link rel="stylesheet" href="../assets/css/klien.css"></head><body>';
}

/** Menu bawah khusus HP (menu tengah navbar disembunyikan di layar kecil) */
function bottom_nav() {
    $h = basename($_SERVER['SCRIPT_NAME']);
    $m = ['index.php' => ['Home', 'fa-house'], 'cari.php' => ['Cari MUA', 'fa-magnifying-glass'], 'history.php' => ['History', 'fa-clock-rotate-left'], 'profil.php' => ['Profil', 'fa-user']];
    echo '<nav class="bottom-nav">';
    foreach ($m as $file => [$label, $ikon]) echo '<a href="' . $file . '" class="' . ($h == $file ? 'on' : '') . '"><i class="fa-solid ' . $ikon . '"></i><span>' . $label . '</span></a>';
    echo '</nav></body></html>';
}