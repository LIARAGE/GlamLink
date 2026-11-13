<?php
/**
 * FITUR CHAT KLIEN <-> MUA (dipakai bersama oleh mua/chat.php & klien/chat.php)
 *
 * Aturan siapa boleh chat dengan siapa:
 *   - Klien boleh memulai chat dengan MUA mana pun yang sudah diverifikasi (tanya-tanya sebelum booking).
 *   - MUA boleh chat dengan klien yang pernah memesan ke dia ATAU pernah mengirim chat ke dia.
 *
 * API JSON (dipanggil assets/js/chat.js lewat fetch, header X-Requested-With: fetch):
 *   GET  ?api=list                          -> daftar percakapan
 *   GET  ?api=pesan&dengan=ID&setelah=ID    -> pesan baru (sekaligus menandai dibaca)
 *   POST ?api=kirim  (dengan, pesan)        -> kirim pesan
 *
 * Waktu memakai jam MySQL (DATE_FORMAT, CURDATE) agar konsisten.
 */

const CHAT_MAKS_PANJANG = 2000;
const CHAT_MAKS_PER_MENIT = 30;   // cegah spam

function chat_e($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** URL foto profil di assets/img, null jika tidak ada */
function chat_foto(?string $nama, string $prefix = '../'): ?string {
    $nama = basename((string)$nama);
    if ($nama === '' || !is_file(dirname(__DIR__) . '/assets/img/' . $nama)) return null;
    return $prefix . 'assets/img/' . rawurlencode($nama);
}

/** Bentuk data lawan bicara untuk ditampilkan */
function chat_format_user(array $u): array {
    $mua = $u['role'] === 'mua';
    return [
        'id'    => (int)$u['id_user'],
        'nama'  => $mua ? ($u['nama_brand'] ?: $u['nama']) : $u['nama'],
        'label' => $mua ? 'Make Up Artist' : 'Klien',
        'foto'  => chat_foto($u['foto_profil'] ?? ''),
    ];
}

/** Cek apakah $saya boleh chat dengan $lawan_id. Mengembalikan data lawan atau null. */
function chat_lawan(mysqli $k, int $saya, string $role_saya, int $lawan_id): ?array {
    if ($lawan_id <= 0 || $lawan_id === $saya) return null;
    $st = $k->prepare("SELECT id_user, nama, nama_brand, foto_profil, role, status_verifikasi FROM users WHERE id_user = ?");
    $st->bind_param('i', $lawan_id);
    $st->execute();
    $u = $st->get_result()->fetch_assoc();
    if (!$u) return null;

    if ($role_saya === 'pelanggan') {
        if ($u['role'] !== 'mua' || $u['status_verifikasi'] !== 'approved') return null;
    } elseif ($role_saya === 'mua') {
        if ($u['role'] !== 'pelanggan') return null;
        $st = $k->prepare("SELECT 1 FROM bookings WHERE id_mua = ? AND id_pelanggan = ?
                           UNION SELECT 1 FROM chats WHERE id_pengirim = ? AND id_penerima = ? LIMIT 1");
        $st->bind_param('iiii', $saya, $lawan_id, $lawan_id, $saya);
        $st->execute();
        if ($st->get_result()->num_rows === 0) return null;
    } else {
        return null;
    }
    return chat_format_user($u);
}

/** Daftar percakapan: lawan, pesan terakhir, jumlah belum dibaca */
function chat_percakapan(mysqli $k, int $saya, string $role_saya): array {
    $role_lawan = $role_saya === 'mua' ? 'pelanggan' : 'mua';
    $st = $k->prepare("
        SELECT u.id_user, u.nama, u.nama_brand, u.foto_profil, u.role,
               m.pesan, m.id_pengirim, m.id_chat,
               IF(DATE(m.waktu_kirim) = CURDATE(), DATE_FORMAT(m.waktu_kirim, '%H:%i'),
                  IF(DATE(m.waktu_kirim) = CURDATE() - INTERVAL 1 DAY, 'Kemarin', DATE_FORMAT(m.waktu_kirim, '%d/%m/%y'))) AS waktu,
               (SELECT COUNT(*) FROM chats c2 WHERE c2.id_pengirim = x.lawan AND c2.id_penerima = ? AND c2.dibaca_pada IS NULL) AS belum
        FROM (SELECT IF(id_pengirim = ?, id_penerima, id_pengirim) AS lawan, MAX(id_chat) AS id_akhir
              FROM chats WHERE id_pengirim = ? OR id_penerima = ? GROUP BY lawan) x
        JOIN chats m ON m.id_chat = x.id_akhir
        JOIN users u ON u.id_user = x.lawan AND u.role = ?
        ORDER BY m.id_chat DESC");
    $st->bind_param('iiiis', $saya, $saya, $saya, $saya, $role_lawan);
    $st->execute();
    $hasil = [];
    foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
        $hasil[] = chat_format_user($r) + [
            'cuplikan' => mb_strimwidth(preg_replace('/\s+/', ' ', $r['pesan']), 0, 60, '…'),
            'dari_saya' => (int)$r['id_pengirim'] === $saya,
            'waktu'    => $r['waktu'],
            'belum'    => (int)$r['belum'],
            'id_akhir' => (int)$r['id_chat'],
        ];
    }
    return $hasil;
}

/** Pesan antara $saya dan $lawan dengan id > $setelah. Pesan dari lawan otomatis ditandai dibaca. */
function chat_pesan(mysqli $k, int $saya, int $lawan, int $setelah = 0): array {
    $st = $k->prepare("UPDATE chats SET dibaca_pada = NOW() WHERE id_pengirim = ? AND id_penerima = ? AND dibaca_pada IS NULL");
    $st->bind_param('ii', $lawan, $saya);
    $st->execute();

    $st = $k->prepare("
        SELECT * FROM (
            SELECT id_chat, id_pengirim, pesan, dibaca_pada IS NOT NULL AS dibaca,
                   DATE_FORMAT(waktu_kirim, '%H.%i') AS jam, DATE(waktu_kirim) AS tanggal
            FROM chats
            WHERE id_chat > ? AND ((id_pengirim = ? AND id_penerima = ?) OR (id_pengirim = ? AND id_penerima = ?))
            ORDER BY id_chat DESC LIMIT 300
        ) t ORDER BY id_chat ASC");
    $st->bind_param('iiiii', $setelah, $saya, $lawan, $lawan, $saya);
    $st->execute();
    $pesan = [];
    foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
        $pesan[] = ['id' => (int)$r['id_chat'], 'saya' => (int)$r['id_pengirim'] === $saya, 'pesan' => $r['pesan'],
                    'jam' => $r['jam'], 'tanggal' => $r['tanggal'], 'dibaca' => (bool)$r['dibaca']];
    }

    // Pesan saya terakhir yang sudah dibaca lawan (untuk memperbarui centang dua)
    $st = $k->prepare("SELECT COALESCE(MAX(id_chat), 0) AS n FROM chats WHERE id_pengirim = ? AND id_penerima = ? AND dibaca_pada IS NOT NULL");
    $st->bind_param('ii', $saya, $lawan);
    $st->execute();
    $dibaca_sampai = (int)$st->get_result()->fetch_assoc()['n'];

    return ['pesan' => $pesan, 'dibaca_sampai' => $dibaca_sampai];
}

/** Kirim pesan. Mengembalikan [pesan_baru, error] */
function chat_kirim(mysqli $k, int $saya, int $lawan, string $teks): array {
    $teks = trim(str_replace("\r\n", "\n", $teks));
    if ($teks === '') return [null, 'Pesan tidak boleh kosong.'];
    if (mb_strlen($teks) > CHAT_MAKS_PANJANG) return [null, 'Pesan maksimal ' . CHAT_MAKS_PANJANG . ' karakter.'];

    $st = $k->prepare("SELECT COUNT(*) AS n FROM chats WHERE id_pengirim = ? AND waktu_kirim > NOW() - INTERVAL 1 MINUTE");
    $st->bind_param('i', $saya);
    $st->execute();
    if ((int)$st->get_result()->fetch_assoc()['n'] >= CHAT_MAKS_PER_MENIT) return [null, 'Terlalu banyak pesan. Tunggu sebentar.'];

    $st = $k->prepare("INSERT INTO chats (id_pengirim, id_penerima, pesan) VALUES (?, ?, ?)");
    $st->bind_param('iis', $saya, $lawan, $teks);
    $st->execute();
    $id = (int)$k->insert_id;

    $st = $k->prepare("SELECT DATE_FORMAT(waktu_kirim, '%H.%i') AS jam, DATE(waktu_kirim) AS tanggal FROM chats WHERE id_chat = ?");
    $st->bind_param('i', $id);
    $st->execute();
    $w = $st->get_result()->fetch_assoc();
    return [['id' => $id, 'saya' => true, 'pesan' => $teks, 'jam' => $w['jam'], 'tanggal' => $w['tanggal'], 'dibaca' => false], null];
}

/**
 * Saran "Mulai percakapan": orang dari riwayat pesanan yang belum pernah di-chat.
 * MUA -> klien yang pernah memesan; Klien -> MUA yang pernah dipesan.
 */
function chat_saran(mysqli $k, int $saya, string $role_saya): array {
    $kolom_saya  = $role_saya === 'mua' ? 'id_mua' : 'id_pelanggan';
    $kolom_lawan = $role_saya === 'mua' ? 'id_pelanggan' : 'id_mua';
    $st = $k->prepare("
        SELECT u.id_user, u.nama, u.nama_brand, u.foto_profil, u.role, MAX(b.created_at) AS terakhir
        FROM bookings b JOIN users u ON u.id_user = b.$kolom_lawan
        WHERE b.$kolom_saya = ?
          AND NOT EXISTS (SELECT 1 FROM chats c WHERE (c.id_pengirim = ? AND c.id_penerima = u.id_user) OR (c.id_pengirim = u.id_user AND c.id_penerima = ?))
        GROUP BY u.id_user ORDER BY terakhir DESC LIMIT 6");
    $st->bind_param('iii', $saya, $saya, $saya);
    $st->execute();
    return array_map('chat_format_user', $st->get_result()->fetch_all(MYSQLI_ASSOC));
}

/** Jumlah pesan belum dibaca (badge navbar). Aman: 0 jika SQL revisi belum dijalankan. */
function chat_belum_dibaca(mysqli $k, int $saya): int {
    try {
        $st = $k->prepare("SELECT COUNT(*) AS n FROM chats WHERE id_penerima = ? AND dibaca_pada IS NULL");
        $st->bind_param('i', $saya);
        $st->execute();
        return (int)$st->get_result()->fetch_assoc()['n'];
    } catch (Throwable $e) {
        return 0;
    }
}

/** Tanggal server (untuk pemisah "Hari ini" / "Kemarin") */
function chat_tanggal_server(mysqli $k): array {
    $r = $k->query("SELECT CURDATE() AS hari_ini, CURDATE() - INTERVAL 1 DAY AS kemarin")->fetch_assoc();
    return ['hariIni' => $r['hari_ini'], 'kemarin' => $r['kemarin']];
}

/**
 * Tangani permintaan API. Dipanggil di awal halaman chat setelah cek login.
 * Tidak melakukan apa-apa jika bukan permintaan API.
 */
function chat_api(mysqli $k, int $saya, string $role): void {
    if (!isset($_GET['api'])) return;
    header('Content-Type: application/json');
    header('Cache-Control: no-store');

    // Hanya dari fetch() halaman ini sendiri (header khusus tidak bisa dikirim form dari situs lain)
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'fetch') {
        http_response_code(400);
        echo json_encode(['error' => 'Permintaan tidak valid.']);
        exit();
    }

    $api = $_GET['api'];
    if ($api === 'list') {
        echo json_encode(['percakapan' => chat_percakapan($k, $saya, $role), 'belum' => chat_belum_dibaca($k, $saya)]);
        exit();
    }

    $lawan_id = (int)($_GET['dengan'] ?? $_POST['dengan'] ?? 0);
    $lawan = chat_lawan($k, $saya, $role, $lawan_id);
    if (!$lawan) {
        http_response_code(403);
        echo json_encode(['error' => 'Kamu tidak bisa mengirim pesan ke pengguna ini.']);
        exit();
    }

    if ($api === 'pesan') {
        echo json_encode(chat_pesan($k, $saya, $lawan_id, max(0, (int)($_GET['setelah'] ?? 0))) + ['belum' => chat_belum_dibaca($k, $saya)]);
        exit();
    }

    if ($api === 'kirim' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        [$pesan, $error] = chat_kirim($k, $saya, $lawan_id, (string)($_POST['pesan'] ?? ''));
        if ($error) { http_response_code(422); echo json_encode(['error' => $error]); exit(); }
        echo json_encode(['pesan' => $pesan]);
        exit();
    }

    http_response_code(404);
    echo json_encode(['error' => 'API tidak dikenal.']);
    exit();
}