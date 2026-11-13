<?php
/**
 * NOTIFIKASI MUA
 * Notifikasi dibangun dari data yang sudah ada (bookings & mua_subscriptions),
 * sehingga kode di sisi klien tidak perlu diubah. Setiap sumber punya `kunci`
 * unik, jadi notifikasi tidak dobel dan notifikasi yang dihapus tidak muncul lagi.
 *
 * Fungsi:
 *   mua_notif_sinkron($koneksi, $id_mua, $paksa)  -> buat notifikasi baru dari data terbaru
 *   mua_notif_jumlah_baru($koneksi, $id_mua)      -> jumlah belum dibaca (untuk badge navbar)
 *   MUA_NOTIF_TIPE                                 -> label, ikon & warna tiap tipe
 *
 * Semua perhitungan waktu memakai jam MySQL (NOW(), CURDATE()) supaya sama
 * dengan kolom created_at / dibayar_pada, walaupun zona waktu PHP berbeda.
 */

const MUA_NOTIF_TIPE = [
    'pesanan'   => ['label' => 'Pesanan Masuk',      'ikon' => 'fa-regular fa-calendar',     'warna' => 'rose'],
    'bayar'     => ['label' => 'Pembayaran Masuk',   'ikon' => 'fa-regular fa-credit-card',  'warna' => 'blue'],
    'jadwal'    => ['label' => 'Jadwal',             'ikon' => 'fa-regular fa-clock',        'warna' => 'indigo'],
    'langganan' => ['label' => 'Langganan Mitra',    'ikon' => 'fa-solid fa-crown',          'warna' => 'amber'],
    'batal'     => ['label' => 'Pesanan Dibatalkan', 'ikon' => 'fa-solid fa-ban',            'warna' => 'red'],
    'selesai'   => ['label' => 'Sesi Selesai',       'ikon' => 'fa-regular fa-circle-check', 'warna' => 'green'],
    'ulasan'    => ['label' => 'Ulasan Baru',        'ikon' => 'fa-regular fa-star',         'warna' => 'orange'],
    'dana'      => ['label' => 'Uang Diterima',      'ikon' => 'fa-solid fa-wallet',         'warna' => 'green'],
    'tarik'     => ['label' => 'Dana Ditarik',       'ikon' => 'fa-solid fa-arrow-up-right-from-square', 'warna' => 'teal'],
];

const MUA_NOTIF_BULAN = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

/** "18 Okt 2026" */
function mua_notif_tgl(string $tgl): string {
    $t = strtotime($tgl);
    return date('j', $t) . ' ' . MUA_NOTIF_BULAN[(int)date('n', $t)] . ' ' . date('Y', $t);
}

function mua_notif_rp($n): string {
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}

/** Detik -> "10 menit lalu" */
function mua_notif_relatif(int $detik): string {
    if ($detik < 60)          return 'Baru saja';
    if ($detik < 3600)        return floor($detik / 60) . ' menit lalu';
    if ($detik < 86400)       return floor($detik / 3600) . ' jam lalu';
    if ($detik < 86400 * 7)   return floor($detik / 86400) . ' hari lalu';
    if ($detik < 86400 * 30)  return floor($detik / (86400 * 7)) . ' minggu lalu';
    return floor($detik / (86400 * 30)) . ' bulan lalu';
}

/**
 * Buat notifikasi baru dari data terbaru.
 * Dibatasi sekali per 60 detik per sesi (kecuali $paksa = true) agar navbar tetap ringan.
 */
function mua_notif_sinkron(mysqli $koneksi, int $id_mua, bool $paksa = false): void {
    $kunci_sesi = 'mua_notif_sync_' . $id_mua;
    if (!$paksa && isset($_SESSION[$kunci_sesi]) && $_SESSION[$kunci_sesi] > time() - 60) return;
    $_SESSION[$kunci_sesi] = time();

    // Pertama kali dipakai: notifikasi lama langsung dianggap sudah dibaca (badge tidak membanjir)
    $st = $koneksi->prepare("SELECT COUNT(*) AS n FROM mua_notifikasi WHERE id_mua = ?");
    $st->bind_param('i', $id_mua);
    $st->execute();
    $pertama = (int)$st->get_result()->fetch_assoc()['n'] === 0 ? 1 : 0;

    $ins = $koneksi->prepare("INSERT IGNORE INTO mua_notifikasi (id_mua, kunci, tipe, judul, pesan, link, label_aksi, waktu, dibaca_pada)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, IF(? = 1, NOW(), NULL))");
    $tambah = function (string $kunci, string $tipe, string $judul, string $pesan, ?string $link, ?string $aksi, string $waktu, int $sudah_baca = 0)
              use ($ins, $id_mua, $pertama) {
        $baca = ($pertama || $sudah_baca) ? 1 : 0;
        $ins->bind_param('isssssssi', $id_mua, $kunci, $tipe, $judul, $pesan, $link, $aksi, $waktu, $baca);
        $ins->execute();
    };

    // ---------- 1. Pesanan: masuk, dibayar, dibatalkan klien, dikonfirmasi selesai, ulasan ----------
    $st = $koneksi->prepare("
        SELECT b.id_booking, b.no_invoice, b.tanggal_booking, b.waktu_booking, b.lokasi_acara, b.total_harga, b.status,
               b.metode_bayar, b.dibayar_pada, b.dibatalkan_oleh, b.is_client_done, b.is_mua_done, b.rating,
               b.created_at, COALESCE(b.dibatalkan_pada, b.created_at) AS waktu_batal, NOW() AS sekarang,
               u.nama AS klien, s.nama_layanan
        FROM bookings b
        LEFT JOIN users u ON u.id_user = b.id_pelanggan
        LEFT JOIN services s ON s.id_service = b.id_layanan
        WHERE b.id_mua = ? AND (b.created_at >= NOW() - INTERVAL 60 DAY OR b.tanggal_booking >= CURDATE() - INTERVAL 60 DAY)");
    $st->bind_param('i', $id_mua);
    $st->execute();
    foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $b) {
        $id = (int)$b['id_booking'];
        $klien = $b['klien'] ?: 'Klien';
        $layanan = $b['nama_layanan'] ?: 'layanan';
        $jadwal = mua_notif_tgl($b['tanggal_booking']) . ' pukul ' . substr($b['waktu_booking'], 0, 5) . ' WIB';
        $lokasi = trim((string)$b['lokasi_acara']) !== '' ? ' di ' . $b['lokasi_acara'] : '';

        $tambah("pesanan:$id", 'pesanan', 'Permintaan Pesanan Baru Masuk!',
            "$klien mengajukan pesanan baru: $layanan untuk tanggal $jadwal$lokasi.",
            'booking.php?tab=scheduled', 'Tinjau & Konfirmasi', $b['created_at'], $b['status'] !== 'menunggu' ? 1 : 0);

        if (!empty($b['dibayar_pada'])) {
            $metode = $b['metode_bayar'] ? ' melalui ' . $b['metode_bayar'] : '';
            $tambah("bayar:$id", 'bayar', 'Pembayaran Pesanan Diterima',
                'Pembayaran pesanan ' . $b['no_invoice'] . ' sebesar ' . mua_notif_rp($b['total_harga']) . " dari $klien$metode telah diterima GlamLink.",
                'booking.php?tab=scheduled', 'Lihat Pesanan', $b['dibayar_pada']);
        }

        if ($b['dibatalkan_oleh'] === 'klien') {
            $tambah("batal:$id", 'batal', 'Pesanan Dibatalkan Klien',
                "$klien membatalkan pesanan " . $b['no_invoice'] . " ($layanan, $jadwal). Tanggal tersebut kini terbuka kembali.",
                'booking.php?tab=cancelled', 'Lihat Detail', $b['waktu_batal']);
        }

        if ((int)$b['is_client_done'] === 1 && (int)$b['is_mua_done'] === 0 && $b['status'] === 'dikonfirmasi') {
            $tambah("selesai:$id", 'selesai', 'Klien Mengonfirmasi Sesi Selesai',
                "$klien sudah mengonfirmasi sesi " . $b['no_invoice'] . ' selesai. Unggah foto dokumentasi untuk menyelesaikan pesanan.',
                'booking.php?tab=ongoing', 'Unggah Dokumentasi', $b['sekarang']);
        }

        if ($b['status'] === 'selesai') {
            $tambah("dana:$id", 'dana', 'Dana Masuk ke Saldo Dompet!',
                "Klien & MUA telah mengonfirmasi sesi " . $b['no_invoice'] . ' selesai. Saldo sebesar ' . mua_notif_rp($b['total_harga'])
                  . ' telah masuk ke saldo dompet dan siap ditarik ke rekening bank atau e-wallet.',
                'keuangan.php', 'Tarik Saldo Sekarang', $b['sekarang']);
        }

        if (!empty($b['rating'])) {
            $tambah("ulasan:$id", 'ulasan', 'Ulasan Baru ' . str_repeat('★', (int)$b['rating']),
                "$klien memberi rating " . (int)$b['rating'] . "/5 untuk $layanan.",
                'profil.php?tab=review', 'Lihat Ulasan', $b['sekarang']);
        }
    }

    // ---------- 2. Pengingat jadwal hari ini & besok ----------
    $st = $koneksi->prepare("
        SELECT b.id_booking, b.tanggal_booking, b.waktu_booking, b.lokasi_acara, u.nama AS klien, s.nama_layanan,
               (b.tanggal_booking = CURDATE()) AS hari_ini, NOW() AS sekarang
        FROM bookings b
        LEFT JOIN users u ON u.id_user = b.id_pelanggan
        LEFT JOIN services s ON s.id_service = b.id_layanan
        WHERE b.id_mua = ? AND b.status = 'dikonfirmasi' AND b.tanggal_booking BETWEEN CURDATE() AND CURDATE() + INTERVAL 1 DAY");
    $st->bind_param('i', $id_mua);
    $st->execute();
    foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $j) {
        $hari = (int)$j['hari_ini'] === 1;
        $lok = trim((string)$j['lokasi_acara']);
        $link = $lok !== '' ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($lok) : 'booking.php?tab=scheduled';
        $tambah('jadwal:' . $j['id_booking'] . ':' . ($hari ? 'hari' : 'besok'), 'jadwal',
            $hari ? 'Pengingat Jadwal Hari Ini' : 'Pengingat Jadwal Besok',
            'Anda memiliki sesi ' . ($j['nama_layanan'] ?: 'makeup') . ' bersama ' . ($j['klien'] ?: 'klien') . ' '
              . ($hari ? 'hari ini' : 'besok') . ' pukul ' . substr($j['waktu_booking'], 0, 5) . ' WIB' . ($lok !== '' ? " di $lok" : '') . '.',
            $link, $lok !== '' ? 'Buka Navigasi Lokasi' : 'Lihat Jadwal', $j['sekarang']);
    }

    // ---------- 3. Langganan: segera berakhir (≤ 7 hari) / sudah berakhir ----------
    $st = $koneksi->prepare("
        SELECT ms.id_transaksi, ms.tanggal_berakhir, sp.nama_paket,
               DATEDIFF(ms.tanggal_berakhir, CURDATE()) AS sisa, NOW() AS sekarang
        FROM mua_subscriptions ms
        JOIN subscription_packages sp ON sp.id_paket = ms.id_paket
        WHERE ms.id_mua = ? AND ms.status IN ('aktif','kedaluwarsa')
        ORDER BY ms.tanggal_berakhir DESC LIMIT 1");
    $st->bind_param('i', $id_mua);
    $st->execute();
    if ($l = $st->get_result()->fetch_assoc()) {
        $sisa = (int)$l['sisa'];
        if ($sisa >= 0 && $sisa <= 7) {
            $tambah('langganan:' . $l['id_transaksi'] . ':segera', 'langganan', 'Langganan Mitra Segera Berakhir',
                'Masa aktif paket ' . $l['nama_paket'] . ' berakhir pada ' . mua_notif_tgl($l['tanggal_berakhir'])
                  . '. Perpanjang sekarang agar layanan langgananmu tidak terputus.',
                'subscription_pay.php', 'Perpanjang Langganan', $l['sekarang']);
        } elseif ($sisa < 0 && $sisa >= -30) {
            $tambah('langganan:' . $l['id_transaksi'] . ':habis', 'langganan', 'Langganan Mitra Telah Berakhir',
                'Paket ' . $l['nama_paket'] . ' berakhir pada ' . mua_notif_tgl($l['tanggal_berakhir'])
                  . '. Pilih paket baru untuk kembali menikmati fitur langganan.',
                'subscription.php', 'Pilih Paket', $l['sekarang']);
        }
    }

    // Pesanan yang sudah diterima/ditolak: otomatis ditandai dibaca, dan tombol
    // "Tinjau & Konfirmasi" diganti sesuai status terbaru (supaya tidak menyesatkan)
    $st = $koneksi->prepare("
        UPDATE mua_notifikasi n
        JOIN bookings b ON n.kunci = CONCAT('pesanan:', b.id_booking)
        SET n.dibaca_pada = COALESCE(n.dibaca_pada, NOW()),
            n.label_aksi  = CASE b.status WHEN 'dikonfirmasi' THEN 'Lihat Jadwal'
                                          WHEN 'selesai'      THEN 'Lihat Riwayat'
                                          ELSE 'Lihat Detail' END,
            n.link        = CASE b.status WHEN 'dikonfirmasi' THEN 'booking.php?tab=scheduled'
                                          WHEN 'selesai'      THEN 'booking.php?tab=done'
                                          ELSE 'booking.php?tab=cancelled' END
        WHERE n.id_mua = ? AND b.status <> 'menunggu' AND n.label_aksi = 'Tinjau & Konfirmasi'");
    $st->bind_param('i', $id_mua);
    $st->execute();
}

/** Jumlah notifikasi belum dibaca. Aman dipanggil di navbar: mengembalikan 0 jika tabel belum dibuat. */
function mua_notif_jumlah_baru(mysqli $koneksi, int $id_mua): int {
    try {
        mua_notif_sinkron($koneksi, $id_mua);
        $st = $koneksi->prepare("SELECT COUNT(*) AS n FROM mua_notifikasi WHERE id_mua = ? AND dibaca_pada IS NULL AND dihapus_pada IS NULL");
        $st->bind_param('i', $id_mua);
        $st->execute();
        return (int)$st->get_result()->fetch_assoc()['n'];
    } catch (Throwable $e) {
        return 0;   // misalnya revisi_database_notifikasi.sql belum dijalankan
    }
}