<?php
/**
 * PROFIL MUA (dibuka dari foto & nama di kanan atas navbar)
 * 5 tab: Tentang MUA | Layanan | Portfolio | Jadwal | Review
 *  - Tentang  : pratinjau profil publik (diubah lewat profil_edit.php)
 *  - Layanan  : pratinjau layanan (dikelola di halaman Services, supaya tidak dobel)
 *  - Portfolio: tambah (portfolio_tambah.php) & hapus karya
 *  - Jadwal   : tandai / buka tanggal CUTI (tabel mua_libur). Hari lain otomatis terbuka
 *               untuk pengajuan klien, dan MUA tetap memutuskan lewat Terima/Tolak.
 *  - Review   : rating & ulasan dari klien
 */
session_start();
require_once '../includes/auth_mua.php';   // koneksi + cek login MUA + $id_mua
require_once '../includes/mua_ui.php';     // toast
require_once '../includes/mua_profil.php'; // helper profil

$tab_valid = ['tentang', 'layanan', 'portfolio', 'jadwal', 'review'];
$hari_ini  = date('Y-m-d');

$jadwal_max = date('Y-m-t', strtotime('+12 months'));   // batas terjauh pengaturan jadwal

/**
 * Tandai banyak tanggal sebagai cuti sekaligus.
 * Tanggal yang sudah lewat atau masih punya pesanan aktif DILEWATI.
 * Mengembalikan ['baru' => n, 'sudah' => n, 'dilewati' => [tanggal...]]
 */
function terapkan_cuti(mysqli $koneksi, int $id_mua, array $daftar_tgl, ?string $ket): array {
    $hasil = ['baru' => 0, 'sudah' => 0, 'dilewati' => []];
    $hari_ini = date('Y-m-d');
    $daftar_tgl = array_values(array_unique(array_filter($daftar_tgl, fn($t) => $t >= $hari_ini)));
    if (!$daftar_tgl) return $hasil;
    sort($daftar_tgl);

    // Tanggal yang punya pesanan aktif (sekali query untuk seluruh rentang)
    $awal = $daftar_tgl[0];
    $akhir = end($daftar_tgl);
    $st = $koneksi->prepare("SELECT DISTINCT tanggal_booking FROM bookings WHERE id_mua = ? AND status IN ('menunggu','dikonfirmasi') AND tanggal_booking BETWEEN ? AND ?");
    $st->bind_param('iss', $id_mua, $awal, $akhir);
    $st->execute();
    $ada_pesanan = array_column($st->get_result()->fetch_all(MYSQLI_ASSOC), 'tanggal_booking');

    $koneksi->begin_transaction();
    $st = $koneksi->prepare("INSERT INTO mua_libur (id_mua, tanggal, keterangan) VALUES (?, ?, ?)
                             ON DUPLICATE KEY UPDATE keterangan = VALUES(keterangan)");
    foreach ($daftar_tgl as $tgl) {
        if (in_array($tgl, $ada_pesanan, true)) { $hasil['dilewati'][] = $tgl; continue; }
        $st->bind_param('iss', $id_mua, $tgl, $ket);
        $st->execute();
        if ($st->affected_rows === 1) $hasil['baru']++; else $hasil['sudah']++;
    }
    $koneksi->commit();
    return $hasil;
}

/** Kalimat ringkasan hasil terapkan_cuti() untuk toast */
function ringkas_cuti(array $h): array {
    $teks = $h['baru'] > 0 ? $h['baru'] . ' tanggal ditandai cuti.' : 'Tidak ada tanggal baru yang ditandai cuti.';
    if ($h['sudah'] > 0) $teks .= ' ' . $h['sudah'] . ' tanggal sudah cuti sebelumnya.';
    if ($h['dilewati']) {
        $contoh = array_map(fn($t) => date('d/m', strtotime($t)), array_slice($h['dilewati'], 0, 5));
        $teks .= ' ' . count($h['dilewati']) . ' tanggal dilewati karena ada pesanan aktif (' . implode(', ', $contoh) . (count($h['dilewati']) > 5 ? ', ...' : '') . ').';
    }
    return [$teks, $h['baru'] > 0 ? ($h['dilewati'] ? 'info' : 'success') : 'error'];
}

/* =========================================================
   PROSES FORM
   ========================================================= */

// --- Hapus portfolio ---
if (isset($_POST['hapus_portfolio'])) {
    $id_porto = (int)($_POST['id_portfolio'] ?? 0);
    $st = $koneksi->prepare("SELECT foto FROM portfolios WHERE id_portfolio = ? AND id_mua = ?");
    $st->bind_param('ii', $id_porto, $id_mua);
    $st->execute();
    $porto = $st->get_result()->fetch_assoc();

    if ($porto) {
        $st = $koneksi->prepare("DELETE FROM portfolios WHERE id_portfolio = ? AND id_mua = ?");
        $st->bind_param('ii', $id_porto, $id_mua);
        $st->execute();
        mp_hapus_foto($porto['foto']);
        mua_toast('Karya portfolio berhasil dihapus.', 'success');
    } else {
        mua_toast('Portfolio tidak ditemukan.', 'error');
    }
    header('Location: profil.php?tab=portfolio');
    exit();
}

// --- Tandai tanggal cuti ---
if (isset($_POST['tambah_libur'])) {
    $tgl = $_POST['tanggal'] ?? '';
    $ket = mb_substr(trim($_POST['keterangan'] ?? ''), 0, 150);
    $dt  = DateTime::createFromFormat('!Y-m-d', $tgl);

    if (!$dt || $dt->format('Y-m-d') !== $tgl || $tgl < $hari_ini) {
        mua_toast('Tanggal tidak valid atau sudah lewat.', 'error');
    } else {
        // Tidak boleh cuti di hari yang sudah ada pesanan aktif
        $st = $koneksi->prepare("SELECT COUNT(*) AS n FROM bookings WHERE id_mua = ? AND tanggal_booking = ? AND status IN ('menunggu','dikonfirmasi')");
        $st->bind_param('is', $id_mua, $tgl);
        $st->execute();
        $aktif = (int)$st->get_result()->fetch_assoc()['n'];

        if ($aktif > 0) {
            mua_toast('Ada pesanan aktif di tanggal ini. Tolak atau batalkan dulu di halaman Booking.', 'error');
        } else {
            $ket_db = $ket !== '' ? $ket : null;
            $st = $koneksi->prepare("INSERT INTO mua_libur (id_mua, tanggal, keterangan) VALUES (?, ?, ?)
                                     ON DUPLICATE KEY UPDATE keterangan = VALUES(keterangan)");
            $st->bind_param('iss', $id_mua, $tgl, $ket_db);
            $st->execute();
            mua_toast('Tanggal ' . $dt->format('d/m/Y') . ' ditandai cuti. Klien tidak bisa memesan di tanggal ini.', 'success');
        }
    }
    header('Location: profil.php?tab=jadwal&tgl=' . urlencode($tgl));
    exit();
}

// --- Cuti / buka untuk RENTANG tanggal (termasuk "sebulan penuh") ---
if (isset($_POST['cuti_rentang'])) {
    $mulai   = $_POST['mulai'] ?? '';
    $selesai = $_POST['selesai'] ?? '';
    $buka    = ($_POST['aksi'] ?? '') === 'buka';
    $ket     = mb_substr(trim($_POST['keterangan'] ?? ''), 0, 150);
    $ok = preg_match('/^\d{4}-\d{2}-\d{2}$/', $mulai) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $selesai)
          && strtotime($mulai) && strtotime($selesai);

    if (!$ok) {
        mua_toast('Rentang tanggal tidak valid.', 'error');
    } else {
        if ($mulai > $selesai) [$mulai, $selesai] = [$selesai, $mulai];
        $mulai   = max($mulai, $hari_ini);          // tanggal lewat tidak diubah
        $selesai = min($selesai, $jadwal_max);
        $jumlah  = $mulai <= $selesai ? (int)((strtotime($selesai) - strtotime($mulai)) / 86400) + 1 : 0;

        if ($jumlah < 1) {
            mua_toast('Rentang tanggal sudah lewat.', 'error');
        } elseif ($jumlah > 366) {
            mua_toast('Rentang maksimal 1 tahun.', 'error');
        } elseif ($buka) {
            $st = $koneksi->prepare("DELETE FROM mua_libur WHERE id_mua = ? AND tanggal BETWEEN ? AND ?");
            $st->bind_param('iss', $id_mua, $mulai, $selesai);
            $st->execute();
            mua_toast($st->affected_rows > 0 ? $st->affected_rows . ' tanggal cuti dibuka kembali.' : 'Tidak ada tanggal cuti di rentang ini.', $st->affected_rows > 0 ? 'success' : 'info');
        } else {
            $daftar = [];
            for ($t = strtotime($mulai); $t <= strtotime($selesai); $t = strtotime('+1 day', $t)) $daftar[] = date('Y-m-d', $t);
            [$teks, $tipe] = ringkas_cuti(terapkan_cuti($koneksi, $id_mua, $daftar, $ket !== '' ? $ket : null));
            mua_toast($teks, $tipe);
        }
    }
    header('Location: profil.php?tab=jadwal&tgl=' . urlencode($mulai));
    exit();
}

// --- Libur rutin mingguan, contoh: setiap Minggu selama 3 bulan ---
if (isset($_POST['cuti_rutin'])) {
    $hari  = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['hari'] ?? [])), fn($h) => $h >= 0 && $h <= 6)));
    $bulan = (int)($_POST['durasi'] ?? 0);
    $ket   = mb_substr(trim($_POST['keterangan'] ?? ''), 0, 150);

    if (!$hari || !in_array($bulan, [1, 3, 6, 12], true)) {
        mua_toast('Pilih minimal satu hari dan durasinya.', 'error');
    } else {
        $sampai = min(date('Y-m-d', strtotime("+$bulan months")), $jadwal_max);
        $daftar = [];
        for ($t = strtotime($hari_ini); date('Y-m-d', $t) <= $sampai; $t = strtotime('+1 day', $t)) {
            if (in_array((int)date('w', $t), $hari, true)) $daftar[] = date('Y-m-d', $t);
        }
        [$teks, $tipe] = ringkas_cuti(terapkan_cuti($koneksi, $id_mua, $daftar, $ket !== '' ? $ket : 'Libur rutin'));
        mua_toast($teks, $tipe);
    }
    header('Location: profil.php?tab=jadwal');
    exit();
}

// --- Buka kembali tanggal cuti ---
if (isset($_POST['hapus_libur'])) {
    $tgl = $_POST['tanggal'] ?? '';
    $st = $koneksi->prepare("DELETE FROM mua_libur WHERE id_mua = ? AND tanggal = ?");
    $st->bind_param('is', $id_mua, $tgl);
    $st->execute();
    if ($st->affected_rows > 0) mua_toast('Tanggal dibuka kembali untuk pesanan klien.', 'success');
    header('Location: profil.php?tab=jadwal&tgl=' . urlencode($tgl));
    exit();
}

/* =========================================================
   AMBIL DATA
   ========================================================= */
$profil = mp_ambil_profil($koneksi, $id_mua);
$tab_aktif = in_array($_GET['tab'] ?? '', $tab_valid, true) ? $_GET['tab'] : 'tentang';

// Layanan
$st = $koneksi->prepare("SELECT * FROM services WHERE id_mua = ? ORDER BY id_service DESC");
$st->bind_param('i', $id_mua);
$st->execute();
$layanan = $st->get_result()->fetch_all(MYSQLI_ASSOC);

// Portfolio
$st = $koneksi->prepare("SELECT * FROM portfolios WHERE id_mua = ? ORDER BY id_portfolio DESC");
$st->bind_param('i', $id_mua);
$st->execute();
$portfolio = $st->get_result()->fetch_all(MYSQLI_ASSOC);

// Jadwal: 3 bulan ke belakang s/d 12 bulan ke depan
$jadwal_min = date('Y-m-01', strtotime('-3 months'));

$st = $koneksi->prepare("
    SELECT b.no_invoice, b.tanggal_booking, b.waktu_booking, b.status, u.nama AS nama_klien, s.nama_layanan
    FROM bookings b
    LEFT JOIN users u ON u.id_user = b.id_pelanggan
    LEFT JOIN services s ON s.id_service = b.id_layanan
    WHERE b.id_mua = ? AND b.status IN ('menunggu','dikonfirmasi','selesai')
      AND b.tanggal_booking BETWEEN ? AND ?
    ORDER BY b.tanggal_booking, b.waktu_booking");
$st->bind_param('iss', $id_mua, $jadwal_min, $jadwal_max);
$st->execute();
$data_booking = [];
foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $b) {
    $data_booking[$b['tanggal_booking']][] = [
        'jam'     => substr($b['waktu_booking'], 0, 5),
        'klien'   => $b['nama_klien'] ?? 'Klien',
        'layanan' => $b['nama_layanan'] ?? '-',
        'status'  => $b['status'],
        'inv'     => $b['no_invoice'],
    ];
}

$st = $koneksi->prepare("SELECT tanggal, keterangan FROM mua_libur WHERE id_mua = ? AND tanggal BETWEEN ? AND ? ORDER BY tanggal");
$st->bind_param('iss', $id_mua, $jadwal_min, $jadwal_max);
$st->execute();
$data_libur = [];
foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $l) {
    $data_libur[$l['tanggal']] = (string)($l['keterangan'] ?? '');
}

// Cuti mendatang dikelompokkan: tanggal berurutan dengan keterangan sama = satu baris
$cuti_mendatang = [];
foreach ($data_libur as $tgl => $ket) {
    if ($tgl < $hari_ini) continue;
    $akhir = count($cuti_mendatang) - 1;
    if ($akhir >= 0 && $cuti_mendatang[$akhir]['ket'] === $ket
        && date('Y-m-d', strtotime($cuti_mendatang[$akhir]['selesai'] . ' +1 day')) === $tgl) {
        $cuti_mendatang[$akhir]['selesai'] = $tgl;
        $cuti_mendatang[$akhir]['jumlah']++;
    } else {
        $cuti_mendatang[] = ['mulai' => $tgl, 'selesai' => $tgl, 'ket' => $ket, 'jumlah' => 1];
    }
}
function format_rentang(string $a, string $b): string {
    $ta = strtotime($a); $tb = strtotime($b);
    if ($a === $b) return date('j', $ta) . ' ' . NAMA_BULAN_ID[(int)date('n', $ta)] . ' ' . date('Y', $ta);
    if (date('Y-m', $ta) === date('Y-m', $tb)) return date('j', $ta) . '–' . date('j', $tb) . ' ' . NAMA_BULAN_ID[(int)date('n', $tb)] . ' ' . date('Y', $tb);
    return date('j', $ta) . ' ' . mb_substr(NAMA_BULAN_ID[(int)date('n', $ta)], 0, 3) . (date('Y', $ta) !== date('Y', $tb) ? ' ' . date('Y', $ta) : '')
         . ' – ' . date('j', $tb) . ' ' . mb_substr(NAMA_BULAN_ID[(int)date('n', $tb)], 0, 3) . ' ' . date('Y', $tb);
}

$tgl_pilih = $_GET['tgl'] ?? $hari_ini;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_pilih) || $tgl_pilih < $jadwal_min || $tgl_pilih > $jadwal_max) $tgl_pilih = $hari_ini;

$data_jadwal = [
    'hariIni' => $hari_ini,
    'pilih'   => $tgl_pilih,
    'min'     => $jadwal_min,
    'max'     => $jadwal_max,
    'libur'   => (object)$data_libur,
    'booking' => (object)$data_booking,
];

// Review
$st = $koneksi->prepare("
    SELECT b.rating, b.ulasan, b.tanggal_booking, s.nama_layanan, u.nama, u.foto_profil
    FROM bookings b
    LEFT JOIN users u ON u.id_user = b.id_pelanggan
    LEFT JOIN services s ON s.id_service = b.id_layanan
    WHERE b.id_mua = ? AND b.rating > 0
    ORDER BY b.tanggal_booking DESC, b.id_booking DESC");
$st->bind_param('i', $id_mua);
$st->execute();
$ulasan = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$distribusi = array_fill(1, 5, 0);
foreach ($ulasan as $u) { $r = (int)$u['rating']; if ($r >= 1 && $r <= 5) $distribusi[$r]++; }

// Variabel tampilan header
$nama_brand = $profil['nama_brand'] ?: ($profil['nama'] ?? 'MUA');
$foto_profil = mp_url_foto($profil['foto_profil'] ?? '');
$foto_sampul = mp_url_foto($profil['foto_sampul'] ?? '');
$lokasi_utama = $profil['area_list'][0] ?? '';
$label_tab = ['tentang' => 'Tentang MUA', 'layanan' => 'Layanan', 'portfolio' => 'Portfolio', 'jadwal' => 'Jadwal', 'review' => 'Review'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil <?php echo mp_e($nama_brand); ?> - GlamLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/mua_profil.css">
    <link rel="stylesheet" href="../assets/css/mua_ui.css">
</head>
<body class="pf-body">

    <?php include '../navbar.php'; ?>

    <!-- FOTO SAMPUL -->
    <div class="pf-cover <?php echo $foto_sampul ? '' : 'pf-cover--default'; ?>"
         <?php if ($foto_sampul): ?>style="background-image: url('<?php echo mp_e($foto_sampul); ?>');"<?php endif; ?>></div>

    <main class="pf-main">

        <!-- KARTU HEADER PROFIL -->
        <section class="pf-head-card">
            <div class="pf-avatar">
                <?php if ($foto_profil): ?>
                    <img src="<?php echo mp_e($foto_profil); ?>" alt="<?php echo mp_e($nama_brand); ?>">
                <?php else: ?>
                    <span><i class="fa-solid fa-user"></i></span>
                <?php endif; ?>
            </div>
            <div class="pf-head-info">
                <h1><?php echo mp_e($nama_brand); ?></h1>
                <div class="pf-head-meta">
                    <?php if ($lokasi_utama !== ''): ?>
                        <span class="pf-pill"><i class="fa-solid fa-location-dot"></i> <?php echo mp_e($lokasi_utama); ?></span>
                    <?php endif; ?>
                    <span class="pf-rating">
                        <i class="fa-solid fa-star"></i>
                        <b><?php echo $profil['rating_jumlah'] > 0 ? number_format($profil['rating_rata'], 1) : '-'; ?></b>
                        <small>(<?php echo $profil['rating_jumlah']; ?> ulasan)</small>
                    </span>
                </div>
                <p class="pf-tagline">
                    <?php echo !empty($profil['tagline']) ? mp_e($profil['tagline']) : '<span class="pf-muted">Belum ada tagline. Tambahkan lewat Edit Profil.</span>'; ?>
                </p>
            </div>
            <a href="profil_edit.php" class="pf-btn-outline"><i class="fa-regular fa-pen-to-square"></i> Edit Profil</a>
        </section>

        <!-- TAB MENU -->
        <nav class="pf-tabs" role="tablist">
            <?php foreach ($label_tab as $key => $label): ?>
                <button type="button" class="pf-tab <?php echo $tab_aktif === $key ? 'active' : ''; ?>"
                        role="tab" data-tab="<?php echo $key; ?>" aria-selected="<?php echo $tab_aktif === $key ? 'true' : 'false'; ?>">
                    <?php echo $label; ?>
                </button>
            <?php endforeach; ?>
            <span class="pf-tab-indicator"></span>
        </nav>

        <!-- =====================================================
             TAB 1: TENTANG MUA
        ====================================================== -->
        <section class="pf-panel <?php echo $tab_aktif === 'tentang' ? 'active' : ''; ?>" data-panel="tentang" role="tabpanel">
            <div class="pf-grid-about">
                <div class="pf-card">
                    <h3 class="pf-card-title">Tentang MUA</h3>
                    <div class="pf-bio">
                        <?php echo trim((string)$profil['bio']) !== '' ? nl2br(mp_e($profil['bio'])) : '<span class="pf-muted">Belum ada bio. Ceritakan pengalaman dan gaya riasmu lewat Edit Profil.</span>'; ?>
                    </div>
                    <?php if ($profil['spesial_list']): ?>
                        <div class="pf-divider"></div>
                        <div class="pf-chips">
                            <?php foreach ($profil['spesial_list'] as $s): ?><span class="pf-chip"><?php echo mp_e($s); ?></span><?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="pf-card">
                    <h3 class="pf-card-title">Kontak &amp; Jangkauan</h3>
                    <ul class="pf-contact">
                        <li><i class="fa-solid fa-location-dot"></i><div><b>Lokasi</b><span><?php echo mp_e($profil['alamat'] ?: ($profil['lokasi'] ?: '-')); ?></span></div></li>
                        <li><i class="fa-solid fa-phone"></i><div><b>WhatsApp</b><span><?php echo mp_e($profil['nomor_telepon'] ?: '-'); ?></span></div></li>
                        <li><i class="fa-regular fa-envelope"></i><div><b>Email</b><span><?php echo mp_e($profil['email']); ?></span></div></li>
                    </ul>
                    <div class="pf-divider"></div>
                    <b class="pf-sub-title">Area Jangkauan</b>
                    <div class="pf-chips">
                        <?php if ($profil['area_list']): foreach ($profil['area_list'] as $a): ?>
                            <span class="pf-chip pf-chip-sm"><?php echo mp_e($a); ?></span>
                        <?php endforeach; else: ?>
                            <span class="pf-muted">Belum diatur.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="pf-preview-note"><span class="pf-dot-live"></span> Ini adalah pratinjau langsung profil publik yang dapat dilihat oleh calon klien di marketplace GlamLink.</div>
        </section>

        <!-- =====================================================
             TAB 2: LAYANAN (pratinjau, dikelola di halaman Services)
        ====================================================== -->
        <section class="pf-panel <?php echo $tab_aktif === 'layanan' ? 'active' : ''; ?>" data-panel="layanan" role="tabpanel">
            <div class="pf-panel-head">
                <div>
                    <h3>Layanan di Profilmu (<?php echo count($layanan); ?>)</h3>
                    <p class="pf-muted">Begini daftar layananmu terlihat oleh klien. Tambah, ubah, atau hapus layanan di halaman Services.</p>
                </div>
                <a href="services.php" class="pf-btn-outline pf-btn-sm"><i class="fa-solid fa-sliders"></i> Kelola Layanan</a>
            </div>
            <?php if ($layanan): ?>
                <div class="pf-grid-3">
                    <?php foreach ($layanan as $l): ?>
                        <div class="pf-card pf-service">
                            <div>
                                <h4><?php echo mp_e($l['nama_layanan']); ?></h4>
                                <p class="pf-muted"><?php echo mp_e($l['kategori'] ?: 'Layanan'); ?></p>
                                <?php if (!empty($l['durasi'])): ?>
                                    <p class="pf-service-dur"><i class="fa-regular fa-clock"></i> <?php echo mp_e($l['durasi']); ?> pengerjaan</p>
                                <?php endif; ?>
                            </div>
                            <div class="pf-service-foot">
                                <small>Tarif Paket</small>
                                <b>Rp <?php echo number_format((float)$l['harga'], 0, ',', '.'); ?></b>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="pf-empty">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    <p>Belum ada layanan. Klien belum bisa memesan sebelum kamu menambahkan layanan.</p>
                    <a href="tambah_layanan.php" class="pf-btn-filled pf-btn-sm"><i class="fa-solid fa-plus"></i> Tambah Layanan</a>
                </div>
            <?php endif; ?>
        </section>

        <!-- =====================================================
             TAB 3: PORTFOLIO
        ====================================================== -->
        <section class="pf-panel <?php echo $tab_aktif === 'portfolio' ? 'active' : ''; ?>" data-panel="portfolio" role="tabpanel">
            <div class="pf-panel-head">
                <div>
                    <h3>Portfolio Riasan (<?php echo count($portfolio); ?>)</h3>
                    <p class="pf-muted">Karya terbaikmu yang dilihat klien sebelum memesan.</p>
                </div>
                <a href="portfolio_tambah.php" class="pf-btn-filled pf-btn-sm"><i class="fa-solid fa-plus"></i> Buat Postingan</a>
            </div>
            <div class="pf-porto-grid">
                <a href="portfolio_tambah.php" class="pf-porto-add" aria-label="Tambah karya portfolio">
                    <i class="fa-solid fa-plus"></i>
                    <span>Tambah Karya</span>
                </a>
                <?php foreach ($portfolio as $p):
                    $url = mp_url_foto($p['foto']);
                    $tags = mp_pecah_tag($p['tag'] ?? '');
                    $judul = trim((string)$p['judul']) !== '' ? $p['judul'] : 'Tanpa Judul'; ?>
                    <div class="pf-porto-card" role="button" tabindex="0" aria-label="Lihat detail <?php echo mp_e($judul); ?>"
                         data-id="<?php echo (int)$p['id_portfolio']; ?>"
                         data-judul="<?php echo mp_e($judul); ?>"
                         data-img="<?php echo mp_e($url ?? ''); ?>"
                         data-tags="<?php echo mp_e(implode(',', $tags)); ?>"
                         data-desk="<?php echo mp_e($p['deskripsi'] ?? ''); ?>">
                        <?php if ($url): ?>
                            <img src="<?php echo mp_e($url); ?>" alt="<?php echo mp_e($judul); ?>" loading="lazy">
                        <?php else: ?>
                            <div class="pf-porto-missing"><i class="fa-regular fa-image"></i></div>
                        <?php endif; ?>
                        <button type="button" class="pf-porto-del" title="Hapus karya"
                                data-id="<?php echo (int)$p['id_portfolio']; ?>" data-judul="<?php echo mp_e($judul); ?>">
                            <i class="fa-regular fa-trash-can"></i>
                        </button>
                        <div class="pf-porto-info">
                            <b><?php echo mp_e($judul); ?></b>
                            <?php if ($tags): ?>
                                <div class="pf-porto-tags">
                                    <?php foreach (array_slice($tags, 0, 3) as $t): ?><span>#<?php echo mp_e(str_replace(' ', '', $t)); ?></span><?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- =====================================================
             TAB 4: JADWAL (cuti)
        ====================================================== -->
        <section class="pf-panel <?php echo $tab_aktif === 'jadwal' ? 'active' : ''; ?>" data-panel="jadwal" role="tabpanel">
            <div class="pf-info-box">
                <i class="fa-solid fa-circle-info"></i>
                <p><b>Semua tanggal otomatis terbuka untuk pengajuan klien.</b> Kamu cukup menandai tanggal <b>cuti</b>.
                   Setiap pengajuan tetap masuk ke halaman Booking dan kamu yang memutuskan Terima atau Tolak.</p>
            </div>
            <div class="pf-grid-jadwal">
                <div class="pf-card pf-cal-card">
                    <!-- Mode pilih: satu tanggal atau rentang -->
                    <div class="pf-mode" role="group" aria-label="Mode pilih tanggal">
                        <button type="button" class="pf-mode-btn active" data-mode="satu"><i class="fa-regular fa-calendar"></i> Satu Tanggal</button>
                        <button type="button" class="pf-mode-btn" data-mode="rentang"><i class="fa-solid fa-arrows-left-right"></i> Rentang Tanggal</button>
                    </div>
                    <div class="pf-cal-head">
                        <button type="button" class="pf-cal-nav" data-cal="prev" aria-label="Bulan sebelumnya"><i class="fa-solid fa-chevron-left"></i></button>
                        <h3 id="pfCalTitle">-</h3>
                        <button type="button" class="pf-cal-nav" data-cal="next" aria-label="Bulan berikutnya"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                    <div class="pf-cal-grid pf-cal-days">
                        <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span class="pf-sun">Sun</span>
                    </div>
                    <div class="pf-cal-grid" id="pfCalDates"></div>
                    <div class="pf-cal-legend">
                        <span><i class="pf-lg pf-lg-book"></i> Ada pesanan</span>
                        <span><i class="pf-lg pf-lg-cuti"></i> Cuti</span>
                        <span><i class="pf-lg pf-lg-today"></i> Hari ini</span>
                    </div>
                    <!-- Pintasan supaya MUA tidak perlu klik satu per satu -->
                    <div class="pf-shortcuts">
                        <span class="pf-label" style="margin:0;">PINTASAN</span>
                        <button type="button" class="pf-chip pf-shortcut" data-shortcut="bulan"><i class="fa-regular fa-calendar-xmark"></i> Cuti sebulan penuh</button>
                        <button type="button" class="pf-chip pf-shortcut" data-shortcut="rutin"><i class="fa-solid fa-repeat"></i> Libur rutin mingguan</button>
                    </div>
                    <p class="pf-cal-tip"><i class="fa-regular fa-lightbulb"></i> Tip: tahan <kbd>Shift</kbd> lalu klik tanggal lain untuk langsung memilih rentang.</p>
                </div>

                <div class="pf-card pf-day-card" id="pfDayPanel" aria-live="polite">
                    <span class="pf-label">KETERSEDIAAN</span>
                    <h3 id="pfDayTitle">-</h3>
                    <div id="pfDayStatus"></div>
                    <div id="pfDayList" class="pf-day-list"></div>
                    <div id="pfDayAction" class="pf-day-action"></div>
                </div>
            </div>

            <!-- DAFTAR CUTI MENDATANG -->
            <div class="pf-card pf-upcoming">
                <div class="pf-panel-head" style="margin-bottom:14px;">
                    <div>
                        <h3 style="font-size:1.05rem;">Cuti Mendatang</h3>
                        <p class="pf-muted">Tanggal berurutan otomatis digabung jadi satu baris.</p>
                    </div>
                </div>
                <?php if ($cuti_mendatang): ?>
                    <ul class="pf-upcoming-list">
                        <?php foreach ($cuti_mendatang as $c): ?>
                            <li>
                                <i class="fa-solid fa-umbrella-beach"></i>
                                <div>
                                    <b><?php echo format_rentang($c['mulai'], $c['selesai']); ?></b>
                                    <small><?php echo $c['jumlah']; ?> hari<?php echo $c['ket'] !== '' ? ' · ' . mp_e($c['ket']) : ''; ?></small>
                                </div>
                                <form method="POST" action="profil.php?tab=jadwal">
                                    <input type="hidden" name="mulai" value="<?php echo $c['mulai']; ?>">
                                    <input type="hidden" name="selesai" value="<?php echo $c['selesai']; ?>">
                                    <input type="hidden" name="aksi" value="buka">
                                    <button type="submit" name="cuti_rentang" class="pf-btn-ghost pf-btn-sm" data-loading-text="Membuka..."><i class="fa-solid fa-door-open"></i> Buka</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="pf-muted" style="margin:0;font-size:0.88rem;">Belum ada cuti terjadwal. Semua tanggal terbuka untuk pengajuan klien.</p>
                <?php endif; ?>
            </div>

            <script id="pfJadwalData" type="application/json"><?php echo json_encode($data_jadwal, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
        </section>

        <!-- =====================================================
             TAB 5: REVIEW
        ====================================================== -->
        <section class="pf-panel <?php echo $tab_aktif === 'review' ? 'active' : ''; ?>" data-panel="review" role="tabpanel">
            <div class="pf-card pf-review-summary">
                <div>
                    <span class="pf-label">RATING KESELURUHAN</span>
                    <div class="pf-review-score">
                        <b><?php echo $profil['rating_jumlah'] > 0 ? number_format($profil['rating_rata'], 1) : '-'; ?></b>
                        <span class="pf-stars"><?php echo mp_bintang($profil['rating_rata']); ?></span>
                    </div>
                    <span class="pf-muted"><?php echo $profil['rating_jumlah']; ?> ulasan</span>
                </div>
                <div class="pf-review-bars">
                    <?php for ($i = 5; $i >= 1; $i--):
                        $persen = $profil['rating_jumlah'] > 0 ? round($distribusi[$i] / $profil['rating_jumlah'] * 100) : 0; ?>
                        <div class="pf-bar-row">
                            <span><?php echo $i; ?> <i class="fa-solid fa-star"></i></span>
                            <div class="pf-bar"><i style="width: <?php echo $persen; ?>%;"></i></div>
                            <small><?php echo $distribusi[$i]; ?></small>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <?php if ($ulasan): ?>
                <?php foreach ($ulasan as $u):
                    $foto_klien = mp_url_foto($u['foto_profil'] ?? ''); ?>
                    <div class="pf-card pf-review">
                        <div class="pf-review-top">
                            <div class="pf-review-avatar">
                                <?php if ($foto_klien): ?><img src="<?php echo mp_e($foto_klien); ?>" alt=""><?php else: ?><i class="fa-solid fa-user"></i><?php endif; ?>
                            </div>
                            <div class="pf-review-who">
                                <b><?php echo mp_e($u['nama'] ?? 'Klien'); ?></b>
                                <small>Layanan: <?php echo mp_e($u['nama_layanan'] ?? '-'); ?></small>
                                <span class="pf-stars pf-stars-sm"><?php echo mp_bintang((float)$u['rating']); ?></span>
                            </div>
                            <small class="pf-review-time"><?php echo mp_waktu_relatif($u['tanggal_booking']); ?></small>
                        </div>
                        <p><?php echo trim((string)$u['ulasan']) !== '' ? nl2br(mp_e($u['ulasan'])) : '<span class="pf-muted">Klien memberi rating tanpa ulasan.</span>'; ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="pf-empty">
                    <i class="fa-regular fa-star"></i>
                    <p>Belum ada ulasan. Ulasan muncul setelah klien menyelesaikan pesanan dan memberi rating.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- MODAL DETAIL PORTFOLIO -->
    <div class="modal-overlay pf-modal" id="pfModalPorto" aria-modal="true" role="dialog" aria-labelledby="pfPortoJudul">
        <div class="modal-box pf-porto-box">
            <button type="button" class="pf-porto-close" data-close-modal aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
            <div class="pf-porto-view">
                <img id="pfPortoImg" src="" alt="">
                <div class="pf-porto-missing" id="pfPortoNoImg" hidden><i class="fa-regular fa-image"></i></div>
                <button type="button" class="pf-porto-nav prev" data-porto-nav="-1" aria-label="Sebelumnya"><i class="fa-solid fa-chevron-left"></i></button>
                <button type="button" class="pf-porto-nav next" data-porto-nav="1" aria-label="Berikutnya"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
            <div class="pf-porto-side">
                <span class="pf-label">PORTFOLIO <span id="pfPortoPos"></span></span>
                <h3 id="pfPortoJudul"></h3>
                <div class="pf-porto-tags pf-porto-tags-light" id="pfPortoTags"></div>
                <p id="pfPortoDesk"></p>
                <div class="pf-porto-actions">
                    <a href="#" id="pfPortoEdit" class="pf-btn-outline pf-btn-sm"><i class="fa-regular fa-pen-to-square"></i> Edit</a>
                    <button type="button" id="pfPortoHapus" class="pf-btn-ghost pf-btn-sm pf-text-danger"><i class="fa-regular fa-trash-can"></i> Hapus</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL LIBUR RUTIN MINGGUAN -->
    <div class="modal-overlay pf-modal" id="pfModalRutin" aria-modal="true" role="dialog">
        <div class="modal-box pf-modal-box pf-rutin-box">
            <div class="pf-modal-icon pf-modal-icon-soft"><i class="fa-solid fa-repeat"></i></div>
            <h3>Libur Rutin Mingguan</h3>
            <p class="pf-muted">Tandai hari yang sama setiap minggu sebagai cuti, sekaligus.</p>
            <form method="POST" action="profil.php?tab=jadwal" id="pfFormRutin">
                <div class="pf-weekdays">
                    <?php foreach ([1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 0 => 'Min'] as $n => $h): ?>
                        <label class="pf-weekday"><input type="checkbox" name="hari[]" value="<?php echo $n; ?>"><span><?php echo $h; ?></span></label>
                    <?php endforeach; ?>
                </div>
                <label class="pf-field" style="text-align:left;">
                    <span>Selama</span>
                    <select name="durasi" class="pf-input" id="pfRutinDurasi">
                        <option value="1">1 bulan ke depan</option>
                        <option value="3" selected>3 bulan ke depan</option>
                        <option value="6">6 bulan ke depan</option>
                        <option value="12">12 bulan ke depan</option>
                    </select>
                </label>
                <label class="pf-field" style="text-align:left;">
                    <span>Keterangan <small>opsional</small></span>
                    <input type="text" name="keterangan" class="pf-input" maxlength="150" placeholder="Libur rutin">
                </label>
                <p class="pf-rutin-preview" id="pfRutinPreview">Pilih minimal satu hari.</p>
                <div class="pf-modal-actions">
                    <button type="button" class="pf-btn-ghost" data-close-modal>Batal</button>
                    <button type="submit" name="cuti_rutin" class="pf-btn-danger" id="pfRutinSubmit" data-loading-text="Menyimpan..." disabled>Terapkan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL KONFIRMASI HAPUS PORTFOLIO -->
    <div class="modal-overlay pf-modal" id="pfModalHapus">
        <div class="modal-box pf-modal-box">
            <div class="pf-modal-icon"><i class="fa-regular fa-trash-can"></i></div>
            <h3>Hapus karya ini?</h3>
            <p class="pf-muted">"<span id="pfHapusJudul"></span>" akan dihapus dari profil dan tidak bisa dikembalikan.</p>
            <form method="POST" action="profil.php?tab=portfolio" class="pf-modal-actions">
                <input type="hidden" name="id_portfolio" id="pfHapusId">
                <button type="button" class="pf-btn-ghost" data-close-modal>Batal</button>
                <button type="submit" name="hapus_portfolio" class="pf-btn-danger" data-loading-text="Menghapus...">Ya, Hapus</button>
            </form>
        </div>
    </div>

    <?php mua_toast_render(); ?>
    <script src="../assets/js/mua_ui.js"></script>
    <script src="../assets/js/mua_profil.js"></script>
</body>
</html>