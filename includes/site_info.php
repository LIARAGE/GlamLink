<?php
/**
 * INFO KONTAK & MEDIA SOSIAL GLAMLINK (satu tempat untuk semua halaman)
 * Dipakai oleh footer.php dan halaman Kontak Bantuan (bantuan.php).
 *
 * ISI BAGIAN INI dengan data asli tim kalian. Selama masih kosong (''),
 * ikon di footer akan mengarah ke halaman Kontak Bantuan, dan halaman bantuan
 * menampilkan tulisan "belum diisi" (bukan alamat palsu).
 */

const SITE_EMAIL     = '';   // contoh: 'halo@namadomain.com'
const SITE_WHATSAPP  = '';   // format internasional TANPA + dan spasi, contoh: '6281234567890'
const SITE_INSTAGRAM = '';   // username tanpa @, contoh: 'glamlink.id'
const SITE_TIKTOK    = '';   // username tanpa @
const SITE_JAM_LAYANAN = 'Senin – Jumat, 09.00 – 17.00 WIB';

/** URL untuk tiap kanal kontak, atau null jika belum diisi */
function site_link(string $kanal): ?string {
    switch ($kanal) {
        case 'email':     return SITE_EMAIL !== '' ? 'mailto:' . SITE_EMAIL : null;
        case 'whatsapp':  return SITE_WHATSAPP !== '' ? 'https://wa.me/' . SITE_WHATSAPP : null;
        case 'instagram': return SITE_INSTAGRAM !== '' ? 'https://instagram.com/' . SITE_INSTAGRAM : null;
        case 'tiktok':    return SITE_TIKTOK !== '' ? 'https://www.tiktok.com/@' . SITE_TIKTOK : null;
    }
    return null;
}

/** Halaman tujuan tombol "mulai" sesuai siapa yang sedang login */
function site_link_mulai(string $kategori = ''): string {
    $role = $_SESSION['role'] ?? '';
    if ($role === 'pelanggan') return 'klien/cari.php' . ($kategori !== '' ? '?kategori=' . urlencode($kategori) : '');
    if ($role === 'mua')       return 'mua/index.php';
    if ($role === 'admin')     return 'admin/index.php';
    return 'register_form.php?role=pelanggan';
}

function site_e($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
