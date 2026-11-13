<?php
/**
 * Helper halaman Profil MUA (mua/profil.php, mua/profil_edit.php, mua/portfolio_tambah.php).
 * Butuh $koneksi (sudah tersedia setelah require auth_mua.php).
 */

// Pilihan area jangkauan. Disamakan dengan konstanta WILAYAH di includes/klien.php
// supaya filter pencarian di sisi klien tetap cocok.
const MUA_WILAYAH = ['Jakarta Utara', 'Jakarta Pusat', 'Jakarta Selatan', 'Jakarta Barat', 'Jakarta Timur', 'Bogor', 'Depok', 'Tangerang', 'Bekasi'];

// Saran spesialisasi (MUA tetap boleh mengetik sendiri)
const MUA_SPESIALISASI = ['Pernikahan', 'Wisuda', 'Pesta', 'Photoshoot', 'Glam', 'Natural', 'Korean Dewy', 'Bold', 'Hijab', 'Adat'];

const NAMA_HARI_ID  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
const NAMA_BULAN_ID = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

/** htmlspecialchars singkat */
function mp_e($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/**
 * Pecah teks "a, b, #c" menjadi array unik ['a','b','c'].
 * Dipakai untuk spesialisasi, area jangkauan (users.lokasi) dan tag portfolio.
 */
function mp_pecah_tag(?string $teks): array {
    $hasil = [];
    foreach (preg_split('/\s*,\s*/', (string)$teks) as $t) {
        $t = trim(ltrim(trim($t), '#'));
        if ($t !== '' && !in_array(mb_strtolower($t), array_map('mb_strtolower', $hasil), true)) {
            $hasil[] = $t;
        }
    }
    return $hasil;
}

/** Gabung array tag jadi "a,b,c" dengan batas jumlah & panjang per tag */
function mp_gabung_tag(array $tags, int $maks_jumlah, int $maks_panjang): string {
    $bersih = [];
    foreach ($tags as $t) {
        $t = trim(ltrim(trim((string)$t), '#'));
        $t = preg_replace('/[,<>]/', '', $t);              // koma dipakai sebagai pemisah
        if ($t === '') continue;
        $bersih[] = mb_substr($t, 0, $maks_panjang);
    }
    return implode(',', array_slice(mp_pecah_tag(implode(',', $bersih)), 0, $maks_jumlah));
}

/** Data lengkap profil MUA: users + mua_profil + ringkasan rating */
function mp_ambil_profil(mysqli $koneksi, int $id_mua): array {
    $st = $koneksi->prepare("
        SELECT u.id_user, u.nama, u.email, u.nomor_telepon, u.nama_brand, u.bio, u.lokasi, u.foto_profil,
               p.tagline, p.alamat, p.spesialisasi, p.foto_sampul
        FROM users u
        LEFT JOIN mua_profil p ON p.id_mua = u.id_user
        WHERE u.id_user = ?");
    $st->bind_param('i', $id_mua);
    $st->execute();
    $profil = $st->get_result()->fetch_assoc() ?: [];

    $st = $koneksi->prepare("SELECT ROUND(AVG(rating), 1) AS rata, COUNT(*) AS jumlah FROM bookings WHERE id_mua = ? AND rating > 0");
    $st->bind_param('i', $id_mua);
    $st->execute();
    $rt = $st->get_result()->fetch_assoc();
    $profil['rating_rata']   = $rt['rata'] !== null ? (float)$rt['rata'] : 0;
    $profil['rating_jumlah'] = (int)$rt['jumlah'];

    $profil['area_list']   = mp_pecah_tag($profil['lokasi'] ?? '');
    $profil['spesial_list'] = mp_pecah_tag($profil['spesialisasi'] ?? '');
    return $profil;
}

/** URL gambar di assets/img, atau null jika file tidak ada */
function mp_url_foto(?string $nama, string $prefix = '../'): ?string {
    $nama = basename((string)$nama);
    if ($nama === '' || !is_file(dirname(__DIR__) . '/assets/img/' . $nama)) return null;
    return $prefix . 'assets/img/' . rawurlencode($nama);
}

/** Hapus file gambar lama dengan aman (hanya file buatan upload, di dalam assets/img) */
function mp_hapus_foto(?string $nama): void {
    $nama = basename((string)$nama);
    if ($nama === '' || !preg_match('/^(porto|profil|sampul)_[a-zA-Z0-9_]+\.(jpg|png|webp)$/', $nama)) return;
    $path = dirname(__DIR__) . '/assets/img/' . $nama;
    if (is_file($path)) @unlink($path);
}

/** "2 hari yang lalu", "3 minggu yang lalu", dst. */
function mp_waktu_relatif(string $tanggal): string {
    $detik = time() - strtotime($tanggal);
    if ($detik < 0)          return 'baru saja';
    if ($detik < 3600)       return max(1, (int)floor($detik / 60)) . ' menit yang lalu';
    if ($detik < 86400)      return (int)floor($detik / 3600) . ' jam yang lalu';
    if ($detik < 86400 * 7)  return (int)floor($detik / 86400) . ' hari yang lalu';
    if ($detik < 86400 * 30) return (int)floor($detik / (86400 * 7)) . ' minggu yang lalu';
    if ($detik < 86400 * 365) return (int)floor($detik / (86400 * 30)) . ' bulan yang lalu';
    return (int)floor($detik / (86400 * 365)) . ' tahun yang lalu';
}

/** Ikon bintang Font Awesome (penuh / setengah / kosong) */
function mp_bintang(float $nilai): string {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($nilai >= $i)            $html .= '<i class="fa-solid fa-star"></i>';
        elseif ($nilai >= $i - 0.5)  $html .= '<i class="fa-solid fa-star-half-stroke"></i>';
        else                         $html .= '<i class="fa-regular fa-star"></i>';
    }
    return $html;
}