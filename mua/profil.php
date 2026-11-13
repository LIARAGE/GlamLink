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
$jadwal_max = date('Y-m-t', strtotime('+12 months'));

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

$st = $koneksi->prepare("SELECT tanggal, keterangan FROM mua_libur WHERE id_mua = ? AND tanggal BETWEEN ? AND ?");
$st->bind_param('iss', $id_mua, $jadwal_min, $jadwal_max);
$st->execute();
$data_libur = [];
foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $l) {
    $data_libur[$l['tanggal']] = (string)($l['keterangan'] ?? '');
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
                    <div class="pf-porto-card">
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
                </div>

                <div class="pf-card pf-day-card" id="pfDayPanel" aria-live="polite">
                    <span class="pf-label">KETERSEDIAAN</span>
                    <h3 id="pfDayTitle">-</h3>
                    <div id="pfDayStatus"></div>
                    <div id="pfDayList" class="pf-day-list"></div>
                    <div id="pfDayAction" class="pf-day-action"></div>
                </div>
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