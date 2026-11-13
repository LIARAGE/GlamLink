<?php
/**
 * HALAMAN NOTIFIKASI MUA (ikon lonceng di navbar)
 * - Belum dibaca: kartu putih, garis tepi pink, titik merah
 * - Sudah dibaca: kartu abu muda
 * - Tandai satu / semua dibaca, hapus notifikasi
 * - Tombol aksi menandai dibaca lalu membuka halaman tujuan
 * Aksi dikirim lewat fetch (tanpa reload). Jika JavaScript mati, form biasa tetap berfungsi.
 */
session_start();
require_once '../includes/auth_mua.php';       // koneksi + cek login MUA + $id_mua
require_once '../includes/mua_ui.php';         // toast
require_once '../includes/mua_notifikasi.php'; // sinkronisasi & tipe notifikasi

$ajax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';

/** Jumlah belum dibaca (tanpa sinkron ulang) */
function hitung_baru(mysqli $koneksi, int $id_mua): int {
    $st = $koneksi->prepare("SELECT COUNT(*) AS n FROM mua_notifikasi WHERE id_mua = ? AND dibaca_pada IS NULL AND dihapus_pada IS NULL");
    $st->bind_param('i', $id_mua);
    $st->execute();
    return (int)$st->get_result()->fetch_assoc()['n'];
}

/** Balas JSON (untuk fetch) atau redirect (tanpa JavaScript) */
function selesai(bool $ajax, array $data, string $pesan_toast = '', string $tipe = 'success'): void {
    global $koneksi, $id_mua;
    if ($ajax) {
        header('Content-Type: application/json');
        echo json_encode($data + ['belum_dibaca' => hitung_baru($koneksi, $id_mua)]);
        exit();
    }
    if ($pesan_toast !== '') mua_toast($pesan_toast, $tipe);
    header('Location: notifikasi.php' . (($_GET['f'] ?? '') === 'belum' ? '?f=belum' : ''));
    exit();
}

/* ================= AKSI ================= */

// Tandai semua dibaca
if (isset($_POST['baca_semua'])) {
    $st = $koneksi->prepare("UPDATE mua_notifikasi SET dibaca_pada = NOW() WHERE id_mua = ? AND dibaca_pada IS NULL AND dihapus_pada IS NULL");
    $st->bind_param('i', $id_mua);
    $st->execute();
    selesai($ajax, ['ok' => true, 'diubah' => $st->affected_rows], 'Semua notifikasi ditandai dibaca.');
}

// Tandai satu dibaca
if (isset($_POST['baca'])) {
    $id = (int)$_POST['id_notif'];
    $st = $koneksi->prepare("UPDATE mua_notifikasi SET dibaca_pada = NOW() WHERE id_notif = ? AND id_mua = ? AND dibaca_pada IS NULL");
    $st->bind_param('ii', $id, $id_mua);
    $st->execute();
    selesai($ajax, ['ok' => true]);
}

// Hapus (soft delete, supaya tidak dibuat ulang oleh sinkronisasi)
if (isset($_POST['hapus'])) {
    $id = (int)$_POST['id_notif'];
    $st = $koneksi->prepare("UPDATE mua_notifikasi SET dihapus_pada = NOW(), dibaca_pada = COALESCE(dibaca_pada, NOW()) WHERE id_notif = ? AND id_mua = ?");
    $st->bind_param('ii', $id, $id_mua);
    $st->execute();
    selesai($ajax, ['ok' => $st->affected_rows > 0], 'Notifikasi dihapus.');
}

// Buka tombol aksi: tandai dibaca lalu arahkan ke tujuan
if (isset($_GET['buka'])) {
    $id = (int)$_GET['buka'];
    $st = $koneksi->prepare("SELECT link FROM mua_notifikasi WHERE id_notif = ? AND id_mua = ?");
    $st->bind_param('ii', $id, $id_mua);
    $st->execute();
    $n = $st->get_result()->fetch_assoc();
    if ($n) {
        $st = $koneksi->prepare("UPDATE mua_notifikasi SET dibaca_pada = COALESCE(dibaca_pada, NOW()) WHERE id_notif = ? AND id_mua = ?");
        $st->bind_param('ii', $id, $id_mua);
        $st->execute();
    }
    $link = $n['link'] ?? '';
    // Hanya halaman MUA sendiri atau Google Maps (mencegah open redirect)
    $aman = preg_match('#^[a-z_]+\.php(\?[A-Za-z0-9_=&%.\-]*)?$#', $link) || str_starts_with($link, 'https://www.google.com/maps/');
    header('Location: ' . ($aman ? $link : 'notifikasi.php'));
    exit();
}

/* ================= DATA ================= */
mua_notif_sinkron($koneksi, $id_mua, true);

$filter_belum = ($_GET['f'] ?? '') === 'belum';
$sql = "SELECT *, TIMESTAMPDIFF(SECOND, waktu, NOW()) AS detik FROM mua_notifikasi
        WHERE id_mua = ? AND dihapus_pada IS NULL" . ($filter_belum ? " AND dibaca_pada IS NULL" : "") . "
        ORDER BY waktu DESC, id_notif DESC LIMIT 100";
$st = $koneksi->prepare($sql);
$st->bind_param('i', $id_mua);
$st->execute();
$daftar = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$jumlah_baru = hitung_baru($koneksi, $id_mua);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - GlamLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/mua_notifikasi.css">
    <link rel="stylesheet" href="../assets/css/mua_ui.css">
</head>
<body class="nt-body">

    <?php include '../navbar.php'; ?>

    <header class="nt-header">
        <div class="nt-header-inner">
            <h1>Notifications</h1>
            <form method="POST" class="nt-readall-form" id="ntReadAllForm" data-no-loading <?php echo $jumlah_baru ? '' : 'hidden'; ?>>
                <button type="submit" name="baca_semua" class="nt-btn-readall">
                    <i class="fa-solid fa-check"></i> Tandai Semua Dibaca (<span id="ntCountBtn"><?php echo $jumlah_baru; ?></span>)
                </button>
            </form>
        </div>
    </header>

    <main class="nt-main">
        <nav class="nt-filter">
            <a href="notifikasi.php" class="<?php echo $filter_belum ? '' : 'active'; ?>">Semua</a>
            <a href="notifikasi.php?f=belum" class="<?php echo $filter_belum ? 'active' : ''; ?>">Belum Dibaca <span class="nt-filter-count" id="ntCountFilter" <?php echo $jumlah_baru ? '' : 'hidden'; ?>><?php echo $jumlah_baru; ?></span></a>
        </nav>

        <div class="nt-list" id="ntList">
            <?php foreach ($daftar as $n):
                $meta = MUA_NOTIF_TIPE[$n['tipe']] ?? MUA_NOTIF_TIPE['pesanan'];
                $baru = $n['dibaca_pada'] === null;
                $eksternal = str_starts_with((string)$n['link'], 'https://'); ?>
                <article class="nt-card <?php echo $baru ? 'is-unread' : ''; ?>" data-id="<?php echo (int)$n['id_notif']; ?>">
                    <div class="nt-icon nt-c-<?php echo $meta['warna']; ?>"><i class="<?php echo $meta['ikon']; ?>"></i></div>
                    <div class="nt-content">
                        <div class="nt-meta">
                            <span class="nt-badge nt-c-<?php echo $meta['warna']; ?>"><?php echo htmlspecialchars($meta['label']); ?></span>
                            <?php if ($baru): ?><span class="nt-dot" aria-label="Belum dibaca"></span><?php endif; ?>
                            <time><?php echo mua_notif_relatif((int)$n['detik']); ?></time>
                        </div>
                        <h3><?php echo htmlspecialchars($n['judul']); ?></h3>
                        <p><?php echo htmlspecialchars($n['pesan']); ?></p>
                    </div>
                    <div class="nt-actions">
                        <?php if (!empty($n['link'])): ?>
                            <a href="notifikasi.php?buka=<?php echo (int)$n['id_notif']; ?>" class="nt-btn-action" <?php echo $eksternal ? 'target="_blank" rel="noopener"' : ''; ?>>
                                <?php echo htmlspecialchars($n['label_aksi'] ?: 'Lihat'); ?> <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        <?php endif; ?>
                        <form method="POST" class="nt-del-form" data-no-loading>
                            <input type="hidden" name="id_notif" value="<?php echo (int)$n['id_notif']; ?>">
                            <button type="submit" name="hapus" class="nt-btn-del" title="Hapus notifikasi" aria-label="Hapus notifikasi">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="nt-empty" id="ntEmpty" <?php echo $daftar ? 'hidden' : ''; ?>>
            <i class="fa-regular fa-bell-slash"></i>
            <h3><?php echo $filter_belum ? 'Semua sudah dibaca' : 'Belum ada notifikasi'; ?></h3>
            <p><?php echo $filter_belum ? 'Tidak ada notifikasi baru. Kerja bagus!' : 'Pesanan baru, pembayaran, dan pengingat jadwal akan muncul di sini.'; ?></p>
        </div>
    </main>

    <?php mua_toast_render(); ?>
    <script src="../assets/js/mua_ui.js"></script>
    <script src="../assets/js/mua_notifikasi.js"></script>
</body>
</html>