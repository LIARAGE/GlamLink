<?php
/**
 * NOTIFIKASI KLIEN (pasangan dari includes/mua_notifikasi.php milik sisi MUA, dengan cara kerja yang sama)
 * Notifikasi dibangun dari data booking yang sudah ada, jadi kode sisi MUA tidak perlu diubah.
 * Setiap kejadian punya `kunci` unik per klien -> tidak dobel, dan yang dihapus tidak muncul lagi.
 *
 *   klien_notif_sinkron($koneksi, $id)      -> buat notifikasi baru dari data booking terbaru
 *   klien_notif_jumlah_baru($koneksi, $id)  -> jumlah belum dibaca (badge lonceng navbar)
 *   klien_notif_daftar($koneksi, $id)       -> daftar untuk halaman klien/notifikasi.php
 *
 * Perhitungan waktu memakai jam MySQL (NOW()) agar konsisten walau zona waktu PHP berbeda.
 */

const KLIEN_NOTIF_BULAN = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
const KLIEN_NOTIF_IKON = [
    'booking'  => 'fa-regular fa-calendar',
    'bayar'    => 'fa-regular fa-credit-card',
    'konfirm'  => 'fa-regular fa-calendar-check',
    'tolak'    => 'fa-regular fa-calendar-xmark',
    'selesai'  => 'fa-regular fa-circle-check',
    'jadwal'   => 'fa-regular fa-clock',
];

function klien_notif_tgl(string $tgl): string {
    $t = strtotime($tgl);
    return date('j', $t) . ' ' . KLIEN_NOTIF_BULAN[(int)date('n', $t)] . ' ' . date('Y', $t);
}
function klien_notif_rp($n): string { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function klien_notif_relatif(int $detik): string {
    if ($detik < 60)         return 'Baru saja';
    if ($detik < 3600)       return floor($detik / 60) . ' menit lalu';
    if ($detik < 86400)      return floor($detik / 3600) . ' jam lalu';
    if ($detik < 86400 * 7)  return floor($detik / 86400) . ' hari lalu';
    if ($detik < 86400 * 30) return floor($detik / (86400 * 7)) . ' minggu lalu';
    return floor($detik / (86400 * 30)) . ' bulan lalu';
}
/** Hanya tautan internal yang dikenal yang boleh dibuka dari notifikasi (detail booking, Cari MUA, History, Chat) */
function klien_notif_link_aman(?string $l): string {
    return preg_match('/^(detail_booking\.php\?id=\d+|cari\.php|history\.php|chat\.php(\?dengan=\d+)?)$/', (string)$l) ? $l : 'history.php';
}

function klien_notif_sinkron(mysqli $koneksi, int $id_klien): void {
    static $sudah = [];                      // cukup sekali per halaman dibuka
    if (isset($sudah[$id_klien])) return;
    $sudah[$id_klien] = true;

    // Pertama kali dipakai: notifikasi lama langsung dianggap sudah dibaca (badge tidak membanjir)
    $st = $koneksi->prepare("SELECT COUNT(*) AS n FROM klien_notifikasi WHERE id_pelanggan = ?");
    $st->bind_param('i', $id_klien);
    $st->execute();
    $pertama = (int)$st->get_result()->fetch_assoc()['n'] === 0 ? 1 : 0;

    $ins = $koneksi->prepare("INSERT IGNORE INTO klien_notifikasi (id_pelanggan, kunci, tipe, judul, pesan, link, label_aksi, waktu, dibaca_pada)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, IF(? = 1, NOW(), NULL))");
    $tambah = function (string $kunci, string $tipe, string $judul, string $pesan, ?string $link, ?string $aksi, string $waktu, int $sudah_baca = 0)
              use ($ins, $id_klien, $pertama) {
        $baca = ($pertama || $sudah_baca) ? 1 : 0;
        $ins->bind_param('isssssssi', $id_klien, $kunci, $tipe, $judul, $pesan, $link, $aksi, $waktu, $baca);
        $ins->execute();
    };

    $st = $koneksi->prepare("
        SELECT b.id_booking, b.no_invoice, b.tanggal_booking, b.waktu_booking, b.total_harga, b.status, b.metode_bayar, b.dibayar_pada,
               b.dibatalkan_oleh, b.is_mua_done, b.is_client_done, b.rating, b.created_at,
               COALESCE(b.dibatalkan_pada, b.created_at) AS waktu_batal, NOW() AS sekarang,
               (b.tanggal_booking = CURDATE()) AS hari_ini, (b.tanggal_booking = CURDATE() + INTERVAL 1 DAY) AS besok,
               u.nama_brand, u.nama AS nama_mua, s.nama_layanan
        FROM bookings b
        LEFT JOIN users u ON u.id_user = b.id_mua
        LEFT JOIN services s ON s.id_service = b.id_layanan
        WHERE b.id_pelanggan = ? AND (b.created_at >= NOW() - INTERVAL 60 DAY OR b.tanggal_booking >= CURDATE() - INTERVAL 60 DAY)");
    $st->bind_param('i', $id_klien);
    $st->execute();
    foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $b) {
        $id    = (int)$b['id_booking'];
        $inv   = $b['no_invoice'];
        $mua   = $b['nama_brand'] ?: ($b['nama_mua'] ?: 'MUA');
        $lay   = $b['nama_layanan'] ?: 'layanan';
        $tgl   = klien_notif_tgl($b['tanggal_booking']) . ' pukul ' . substr($b['waktu_booking'], 0, 5) . ' WIB';
        $detail = 'detail_booking.php?id=' . $id;

        $tambah("baru:$id", 'booking', 'Booking Berhasil Dibuat',
            'Permintaan booking kamu berhasil dikirim. Silakan tunggu konfirmasi dari MUA',
            $detail, 'Lihat Detail', $b['created_at'], $b['status'] !== 'menunggu' ? 1 : 0);

        if (!empty($b['dibayar_pada'])) {
            $metode = $b['metode_bayar'] ? ' melalui ' . $b['metode_bayar'] : '';
            $tambah("bayar:$id", 'bayar', 'Pembayaran Berhasil',
                "Pembayaran booking $inv sebesar " . klien_notif_rp($b['total_harga']) . "$metode berhasil diterima.",
                $detail, 'Lihat Detail', $b['dibayar_pada'], 1);
        }

        if (in_array($b['status'], ['dikonfirmasi', 'selesai'], true) || (int)$b['is_mua_done'] === 1) {
            $tambah("konfirm:$id", 'konfirm', 'Booking Dikonfirmasi',
                "$mua menerima booking $inv ($lay) untuk $tgl. Sampai jumpa di hari acara!",
                $detail, 'Lihat Detail', $b['sekarang'], $b['status'] === 'selesai' ? 1 : 0);
        }

        if ($b['status'] === 'ditolak') {
            if ($b['dibatalkan_oleh'] === 'klien') {
                $tambah("batal:$id", 'tolak', 'Booking Dibatalkan', "Kamu membatalkan booking $inv ($lay).", $detail, 'Lihat Detail', $b['waktu_batal'], 1);
            } else {
                $tambah("tolak:$id", 'tolak', 'Booking Ditolak MUA', "$mua belum dapat menerima booking $inv. Silakan pilih MUA atau jadwal lain.", 'cari.php', 'Cari MUA Lain', $b['waktu_batal']);
            }
        }

        if ((int)$b['is_mua_done'] === 1 && (int)$b['is_client_done'] === 0 && $b['status'] === 'dikonfirmasi') {
            $tambah("mua_selesai:$id", 'selesai', 'MUA Telah Selesai Merias',
                "$mua menandai sesi $inv selesai. Mohon konfirmasi pesanan selesai di halaman History.",
                $detail, 'Konfirmasi Selesai', $b['sekarang']);
        }

        if ($b['status'] === 'selesai') {
            $tambah("selesai:$id", 'selesai', 'Pesanan Selesai',
                "Pesanan $inv selesai. " . (empty($b['rating']) ? "Bagikan pengalamanmu dengan memberi rating untuk $mua." : 'Terima kasih sudah memakai GlamLink!'),
                $detail, empty($b['rating']) ? 'Beri Rating' : 'Lihat Detail', $b['sekarang'], !empty($b['rating']) ? 1 : 0);
        }

        if ($b['status'] === 'dikonfirmasi' && ((int)$b['hari_ini'] === 1 || (int)$b['besok'] === 1)) {
            $hari = (int)$b['hari_ini'] === 1;
            $tambah("jadwal:$id:" . ($hari ? 'hari' : 'besok'), 'jadwal', $hari ? 'Pengingat Jadwal Hari Ini' : 'Pengingat Jadwal Besok',
                "Sesi $lay bersama $mua " . ($hari ? 'hari ini' : 'besok') . ' pukul ' . substr($b['waktu_booking'], 0, 5) . ' WIB.',
                $detail, 'Lihat Detail', $b['sekarang']);
        }
    }
}

/** Jumlah belum dibaca (sekaligus menyegarkan notifikasi). Tidak pernah membuat halaman error: jika tabel belum ada, hasilnya 0. */
function klien_notif_jumlah_baru(mysqli $koneksi, int $id_klien): int {
    try {
        klien_notif_sinkron($koneksi, $id_klien);
        $st = $koneksi->prepare("SELECT COUNT(*) AS n FROM klien_notifikasi WHERE id_pelanggan = ? AND dibaca_pada IS NULL AND dihapus_pada IS NULL");
        $st->bind_param('i', $id_klien);
        $st->execute();
        return (int)$st->get_result()->fetch_assoc()['n'];
    } catch (Throwable $e) {
        return 0;
    }
}

function klien_notif_daftar(mysqli $koneksi, int $id_klien, int $batas = 60): array {
    $st = $koneksi->prepare("SELECT id_notif, tipe, judul, pesan, link, label_aksi, dibaca_pada, TIMESTAMPDIFF(SECOND, waktu, NOW()) AS detik
                             FROM klien_notifikasi WHERE id_pelanggan = ? AND dihapus_pada IS NULL ORDER BY waktu DESC, id_notif DESC LIMIT " . (int)$batas);
    $st->bind_param('i', $id_klien);
    $st->execute();
    return $st->get_result()->fetch_all(MYSQLI_ASSOC);
}