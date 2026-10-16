<?php
// Simpan 1 gambar upload dengan aman. Return nama file baru, atau false jika gagal.
function simpan_gambar(array $f, string $prefix = 'img', int $maxMB = 5) {
    // 1. Upload harus sukses & ukuran tidak melebihi batas
    if (($f['error'] ?? 1) !== UPLOAD_ERR_OK || $f['size'] > $maxMB * 1024 * 1024) return false;

    // 2. Cek isi file yang SEBENARNYA (bukan hanya ekstensi): hanya JPG/PNG/WEBP
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext) return false;

    // 3. Path ABSOLUT (tidak bergantung dari mana server dijalankan) + buat folder jika belum ada
    $dir = __DIR__ . '/../assets/img/';
    if (!is_dir($dir)) mkdir($dir, 0775, true);

    // 4. Nama file diacak: tidak ada spasi/simbol aneh, dan tidak bisa bentrok
    $nama = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    return move_uploaded_file($f['tmp_name'], $dir . $nama) ? $nama : false;
}