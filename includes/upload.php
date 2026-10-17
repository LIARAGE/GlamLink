<?php
/**
 * Menyimpan gambar upload ke assets/img.
 * Sukses  -> mengembalikan nama file baru.
 * Gagal   -> mengembalikan false, dan $alasan berisi kode penyebabnya.
 */
function simpan_gambar(array $f, string $prefix = 'img', int $maxMB = 10, &$alasan = null) {
    // 1. Apakah upload dari browser ke PHP berhasil?
    $kode = $f['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($kode !== UPLOAD_ERR_OK) {
        $alasan = ($kode === UPLOAD_ERR_INI_SIZE || $kode === UPLOAD_ERR_FORM_SIZE) ? 'ukuran_php' : 'upload_' . $kode;
        return false;
    }
    // 2. Ukuran
    if ($f['size'] <= 0 || $f['size'] > $maxMB * 1024 * 1024) { $alasan = 'ukuran'; return false; }

    // 3. Cek isi file. getimagesize() bawaan inti PHP: TIDAK butuh ekstensi fileinfo
    $info    = @getimagesize($f['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!$info || !isset($allowed[$info['mime']])) { $alasan = 'format'; return false; }

    // 4. Folder tujuan (path absolut). Tidak memakai is_writable(): di Windows/OneDrive hasilnya sering salah
    $dir = dirname(__DIR__) . '/assets/img/';
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) { $alasan = 'folder'; return false; }

    // 5. Nama acak lalu pindahkan (move_uploaded_file sudah memastikan file memang hasil upload)
    $prefix = preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix) ?: 'img';
    $nama   = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$info['mime']];
    if (!move_uploaded_file($f['tmp_name'], $dir . $nama)) {
        // Simpan pesan error ASLI dari PHP/Windows supaya penyebab pastinya bisa dilihat
        $err = error_get_last();
        if (session_status() === PHP_SESSION_ACTIVE) { $_SESSION['upload_detail'] = ($err['message'] ?? 'tidak ada pesan') . ' | tujuan: ' . $dir; }
        $alasan = 'simpan';
        return false;
    }
    return $nama;
}

/** Ubah kode alasan menjadi kalimat untuk pengguna */
function pesan_upload($alasan) {
    $p = [
        'ukuran_php' => 'ukuran file melebihi batas server (upload_max_filesize di php.ini)',
        'ukuran'     => 'ukuran file lebih dari 10 MB',
        'format'     => 'format harus JPG, PNG, atau WEBP',
        'folder'     => 'folder assets/img tidak ditemukan dan tidak bisa dibuat',
        'simpan'     => 'file tidak bisa disimpan ke folder assets/img (cek izin folder)',
    ];
    $teks = $p[$alasan] ?? 'upload gagal (' . htmlspecialchars((string)$alasan) . ')';
    // Detail teknis hanya ditampilkan saat dijalankan di komputer sendiri (localhost)
    $lokal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1'], true);
    if ($lokal && !empty($_SESSION['upload_detail'])) {
        $teks .= '<br><small>Detail teknis: ' . htmlspecialchars($_SESSION['upload_detail']) . '</small>';
    }
    return $teks;
}