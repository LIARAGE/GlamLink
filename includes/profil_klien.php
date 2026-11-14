<?php
/**
 * Pembantu halaman Profil klien (klien/profil.php dan klien/profil_edit.php).
 * Panggil SETELAH Includes/klien.php.
 * Catatan data: "Alamat" klien disimpan di kolom users.lokasi (kolom ini tidak dipakai klien, jadi tidak perlu migrasi DB).
 */

/** MUA favorit klien (hanya yang masih terverifikasi), yang terakhir difavoritkan paling atas. Format sama dengan daftar_mua(). */
function daftar_favorit($id_klien) {
    $id = (int)$id_klien;
    return daftar_mua('AND u.id_user IN (SELECT id_mua FROM favorit WHERE id_pelanggan = ?)', [$id],
        '(SELECT MAX(f2.created_at) FROM favorit f2 WHERE f2.id_mua = u.id_user AND f2.id_pelanggan = ' . $id . ') DESC', 50);
}

/** Satu baris MUA favorit. $dari = halaman asal ('profil' / 'profil_edit') agar tombol hati kembali ke halaman yang sama */
function kartu_favorit(array $m, $dari) {
    $id = (int)$m['id_user'];
    echo '<article class="pr-fav">' . foto($m['foto_profil'], 'avatar pr-fav-av');
    echo '<div class="pr-fav-info"><b>' . e($m['nama_brand']) . '</b>';
    echo '<div class="pr-fav-meta"><span class="pill"><i class="fa-solid fa-location-dot"></i> ' . e($m['lokasi'] ?: '-') . '</span>' . rating_teks($m) . '</div></div>';
    echo '<div class="pr-fav-aksi"><form method="POST" action="favorit.php">' . csrf_field()
       . '<input type="hidden" name="id_mua" value="' . $id . '"><input type="hidden" name="dari" value="' . e($dari) . '">'
       . '<button type="submit" class="pr-unfav" title="Hapus dari favorit" aria-label="Hapus ' . e($m['nama_brand']) . ' dari favorit"><i class="fa-solid fa-heart"></i></button></form>'
       . '<a class="kb-fill sm" href="mua.php?id=' . $id . '">Lihat Profil</a></div></article>';
}

/** Notifikasi dari ?msg= (profil/password) dan ?info= (hasil tombol hati): kode => [jenis, teks] */
function pesan_profil($msg, $info) {
    $p = ['profil' => ['ok', 'Profil berhasil diperbarui.'], 'password' => ['ok', 'Password berhasil diganti.']];
    $i = ['fav_off' => ['ok', 'MUA dihapus dari favorit.'], 'fav_on' => ['ok', 'MUA ditambahkan ke favorit.'], 'gagal' => ['err', 'Aksi favorit gagal, silakan coba lagi.']];
    return $p[$msg] ?? ($i[$info] ?? null);
}
