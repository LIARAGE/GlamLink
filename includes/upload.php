<?php
/**
 * Menyimpan gambar upload ke assets/img.
 * Mengembalikan nama file jika sukses, atau false jika validasi/penyimpanan gagal.
 */
function simpan_gambar(array $f, string $prefix = 'img', int $maxMB = 10) {
    if (!isset($f['error'], $f['tmp_name'], $f['size']) || $f['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    if (!is_uploaded_file($f['tmp_name']) || $f['size'] <= 0 || $f['size'] > $maxMB * 1024 * 1024) {
        return false;
    }

    if (!class_exists('finfo')) {
        return false;
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
        return false;
    }

    // Path absolut, tidak bergantung pada working directory server.
    $dir = dirname(__DIR__) . '/assets/img/';
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        return false;
    }
    if (!is_writable($dir)) {
        return false;
    }

    $prefix = preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix) ?: 'img';
    try {
        $nama = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    } catch (Throwable $e) {
        return false;
    }

    return move_uploaded_file($f['tmp_name'], $dir . $nama) ? $nama : false;
}
