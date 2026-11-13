<?php
/**
 * LUPA PASSWORD - pengaturan & fungsi bantu
 * Dipakai oleh lupa_password.php dan reset_password.php (di folder utama).
 *
 * Alur:
 *  1. User isi email di lupa_password.php
 *  2. Sistem membuat token acak (berlaku 30 menit, sekali pakai), menyimpan HASH-nya
 *  3. Link reset dikirim lewat email. Di XAMPP belum ada mail server, jadi pada
 *     MODE DEMO link ditampilkan langsung di layar.
 *  4. User buka link -> isi password baru di reset_password.php
 */

// true  = link reset ditampilkan di layar (untuk XAMPP / demo tugas)
// false = link dikirim lewat email dengan fungsi mail() PHP (server harus sudah bisa kirim email)
const RESET_MODE_DEMO = true;

const RESET_BERLAKU_MENIT = 30;   // masa berlaku link
const RESET_MAKS_PER_JAM  = 3;    // batas permintaan per akun per jam (anti spam)
const PASSWORD_MIN        = 8;    // sama dengan aturan register

function rp_e($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** URL lengkap ke reset_password.php untuk token tertentu */
function rp_link(string $token): string {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $folder = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    return ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $folder . '/reset_password.php?token=' . $token;
}

/**
 * Buat token reset untuk email. Mengembalikan:
 *   ['status' => 'ok', 'token' => ..., 'user' => [...]]  jika berhasil
 *   ['status' => 'tidak_ada']                             jika email tidak terdaftar / akun admin
 *   ['status' => 'terlalu_sering']                         jika melebihi batas
 */
function rp_buat_token(mysqli $k, string $email): array {
    $st = $k->prepare("SELECT id_user, nama, email FROM users WHERE email = ? AND role IN ('mua','pelanggan') LIMIT 1");
    $st->bind_param('s', $email);
    $st->execute();
    $user = $st->get_result()->fetch_assoc();
    if (!$user) return ['status' => 'tidak_ada'];

    $id = (int)$user['id_user'];
    $st = $k->prepare("SELECT COUNT(*) AS n FROM password_resets WHERE id_user = ? AND dibuat_pada > NOW() - INTERVAL 1 HOUR");
    $st->bind_param('i', $id);
    $st->execute();
    if ((int)$st->get_result()->fetch_assoc()['n'] >= RESET_MAKS_PER_JAM) return ['status' => 'terlalu_sering'];

    // Link lama yang belum dipakai otomatis tidak berlaku lagi
    $st = $k->prepare("UPDATE password_resets SET kedaluwarsa = NOW() WHERE id_user = ? AND dipakai_pada IS NULL AND kedaluwarsa > NOW()");
    $st->bind_param('i', $id);
    $st->execute();

    $token = bin2hex(random_bytes(32));                    // 64 karakter acak
    $hash  = hash('sha256', $token);
    $ip    = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);
    $menit = RESET_BERLAKU_MENIT;
    $st = $k->prepare("INSERT INTO password_resets (id_user, token_hash, kedaluwarsa, ip) VALUES (?, ?, NOW() + INTERVAL ? MINUTE, ?)");
    $st->bind_param('isis', $id, $hash, $menit, $ip);
    $st->execute();

    return ['status' => 'ok', 'token' => $token, 'user' => $user];
}

/** Cari token yang masih berlaku. Mengembalikan baris reset + nama/email user, atau null. */
function rp_cek_token(mysqli $k, string $token): ?array {
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) return null;
    $hash = hash('sha256', $token);
    $st = $k->prepare("SELECT r.id_reset, r.id_user, u.nama, u.email
                       FROM password_resets r JOIN users u ON u.id_user = r.id_user
                       WHERE r.token_hash = ? AND r.dipakai_pada IS NULL AND r.kedaluwarsa > NOW() LIMIT 1");
    $st->bind_param('s', $hash);
    $st->execute();
    return $st->get_result()->fetch_assoc() ?: null;
}

/** Kirim email berisi link reset (dipakai jika RESET_MODE_DEMO = false) */
function rp_kirim_email(array $user, string $link): bool {
    $subjek = 'Atur Ulang Password GlamLink';
    $isi = "Halo {$user['nama']},\n\n"
         . "Kami menerima permintaan untuk mengatur ulang password akun GlamLink kamu.\n"
         . "Klik link berikut (berlaku " . RESET_BERLAKU_MENIT . " menit, hanya bisa dipakai sekali):\n\n$link\n\n"
         . "Jika kamu tidak meminta ini, abaikan email ini. Password kamu tidak akan berubah.\n\nSalam,\nTim GlamLink";
    $header = "From: GlamLink <no-reply@glamlink.local>\r\nContent-Type: text/plain; charset=UTF-8";
    return @mail($user['email'], $subjek, $isi, $header);
}