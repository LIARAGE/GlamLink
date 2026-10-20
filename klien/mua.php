<?php
session_start();
require __DIR__ . '/../Includes/klien.php';

$id  = (int)($_GET['id'] ?? 0);
$tgl = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['tanggal'] ?? '') ? $_GET['tanggal'] : '';
$m = q("SELECT * FROM users WHERE id_user = ? AND role = 'mua' AND status_verifikasi = 'approved'", [$id])[0] ?? null;

head_klien($m['nama_brand'] ?? 'MUA tidak ditemukan');
include __DIR__ . '/../navbar.php';
if (!$m) { echo '<main class="kl-wrap page-top"><div class="kosong"><i class="fa-regular fa-face-frown"></i><p>MUA tidak ditemukan atau belum terverifikasi.</p><a class="kb-fill" href="cari.php">Kembali ke Cari MUA</a></div></main>'; bottom_nav(); exit(); }

$tabs = ['tentang' => 'Tentang MUA', 'layanan' => 'Layanan', 'portfolio' => 'Portfolio', 'jadwal' => 'Jadwal', 'review' => 'Review'];
$tab  = isset($tabs[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'tentang';
$qs   = 'id=' . $id . ($tgl ? '&tanggal=' . urlencode($tgl) : '');

$layanan = q('SELECT * FROM services WHERE id_mua = ? ORDER BY harga', [$id]);
$porto   = q('SELECT foto FROM portfolios WHERE id_mua = ? ORDER BY id_portfolio DESC', [$id]);
$rt      = q('SELECT ROUND(AVG(rating), 1) AS rata, COUNT(*) AS n FROM bookings WHERE id_mua = ? AND rating > 0', [$id])[0];
$mrow    = ['rating' => $rt['rata'], 'n_ulasan' => $rt['n']];
$bio     = trim((string)$m['bio']);
$ringkas = mb_strimwidth(preg_replace('/\s+/', ' ', $bio), 0, 150, '...');
$cover   = $porto[0]['foto'] ?? '';
$link_booking = 'booking.php?mua=' . $id . ($tgl ? '&tanggal=' . urlencode($tgl) : '');
?>
<div class="mua-banner"><?php if ($cover && file_exists(__DIR__ . '/../assets/img/' . basename($cover))) echo '<img class="banner-img" src="../assets/img/' . e(basename($cover)) . '" alt="">'; ?></div>

<section class="mua-head-card">
    <?php echo foto($m['foto_profil'], 'avatar xl'); ?>
    <div class="mua-head-main">
        <h1><?php echo e($m['nama_brand']); ?></h1>
        <div class="mua-head-meta"><span class="pill"><i class="fa-solid fa-location-dot"></i> <?php echo e($m['lokasi']); ?></span><?php echo rating_teks($mrow); ?></div>
        <p class="short"><?php echo e($ringkas); ?></p>
    </div>
    <a class="kb-fill" href="<?php echo $link_booking; ?>">Booking MUA Ini</a>
</section>

<nav class="det-tabs"><?php foreach ($tabs as $k => $l) echo '<a href="?' . $qs . '&tab=' . $k . '" class="' . ($tab === $k ? 'on' : '') . '">' . $l . '</a>'; ?></nav>

<main class="det-wrap">
<?php if ($tab === 'tentang'):
    $wa = format_wa($m['nomor_telepon']);
    $area = array_filter(array_map('trim', preg_split('/\s*[,\/&]\s*|\s+dan\s+/i', (string)$m['lokasi'])));
    $kats = array_column(q("SELECT DISTINCT kategori FROM services WHERE id_mua = ? AND kategori <> ''", [$id]), 'kategori'); ?>
    <div class="det-grid">
        <div class="det-card">
            <h2>Tentang MUA</h2>
            <div class="isi"><?php echo $bio !== '' ? nl2br(e($bio)) : '<span class="muted">MUA ini belum menuliskan deskripsi.</span>'; ?></div>
            <?php if ($kats) { echo '<div class="chips">'; foreach ($kats as $k) echo '<span class="chip">' . e($k) . '</span>'; echo '</div>'; } ?>
        </div>
        <div class="det-card">
            <h2>Kontak &amp; Jangkauan</h2>
            <div class="kontak-item"><i class="fa-solid fa-location-dot"></i><div><b>Lokasi</b><span><?php echo e($m['lokasi'] ?: '-'); ?></span></div></div>
            <div class="kontak-item"><i class="fa-brands fa-whatsapp"></i><div><b>WhatsApp</b><?php echo $wa ? '<a href="' . e($wa['link']) . '" target="_blank" rel="noopener">' . e($wa['tampil']) . '</a>' : '<span>-</span>'; ?></div></div>
            <div class="kontak-item"><i class="fa-regular fa-envelope"></i><div><b>Email</b><a href="mailto:<?php echo e($m['email']); ?>"><?php echo e($m['email']); ?></a></div></div>
            <?php if ($area): ?><div class="area-box"><b>Area Jangkauan</b><div class="chips"><?php foreach ($area as $a) echo '<span class="chip">' . e($a) . '</span>'; ?></div></div><?php endif; ?>
        </div>
    </div>

<?php elseif ($tab === 'layanan'): ?>
    <div class="layanan-list">
        <?php foreach ($layanan as $s): $fas = json_decode((string)$s['fasilitas'], true) ?: []; ?>
            <div class="layanan-item">
                <div class="info">
                    <h3><?php echo e($s['nama_layanan']); ?> <?php if ($s['kategori']) echo '<span class="chip">#' . e($s['kategori']) . '</span>'; ?></h3>
                    <p class="muted"><?php echo e($s['deskripsi']); ?><?php echo $s['durasi'] ? ' &middot; ' . e($s['durasi']) : ''; ?></p>
                    <?php if ($fas): ?><ul class="fasilitas"><?php foreach ($fas as $f) echo '<li><i class="fa-solid fa-check"></i> ' . e($f) . '</li>'; ?></ul><?php endif; ?>
                </div>
                <div class="aksi"><b><?php echo rupiah($s['harga']); ?></b>
                    <a class="kb-fill" href="booking.php?mua=<?php echo $id; ?>&layanan=<?php echo (int)$s['id_service']; ?><?php echo $tgl ? '&tanggal=' . urlencode($tgl) : ''; ?>">Booking</a></div>
            </div>
        <?php endforeach; ?>
        <?php if (!$layanan) echo '<div class="kosong"><i class="fa-regular fa-clipboard"></i><p>MUA ini belum menambahkan layanan.</p></div>'; ?>
    </div>

<?php elseif ($tab === 'portfolio'): ?>
    <div class="galeri">
        <?php foreach ($porto as $f):
            $nama = basename((string)$f['foto']);
            echo file_exists(__DIR__ . '/../assets/img/' . $nama) ? '<a href="../assets/img/' . e($nama) . '" target="_blank">' . foto($nama, 'porto-img', 'fa-image') . '</a>' : '<div>' . foto('', 'porto-img', 'fa-image') . '</div>';
        endforeach; ?>
    </div>
    <?php if (!$porto) echo '<div class="kosong"><i class="fa-regular fa-image"></i><p>Belum ada portfolio.</p></div>'; ?>
    <dialog class="lightbox" id="lb"><img alt=""></dialog>
    <script>
        // Klik foto -> tampil besar; klik di mana saja untuk menutup
        var lb = document.getElementById('lb');
        document.querySelectorAll('.galeri a').forEach(function (a) { a.addEventListener('click', function (e) { e.preventDefault(); lb.querySelector('img').src = a.href; lb.showModal(); }); });
        lb.addEventListener('click', function () { lb.close(); });
    </script>

<?php elseif ($tab === 'jadwal'):
    // ---- Kalender ketersediaan: tanggal yang sudah ada booking aktif (menunggu/dikonfirmasi) ditandai ----
    $nama_bln = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $awal = strtotime(date('Y-m-01')); $maks = strtotime('+12 months', $awal);
    $tgt  = mktime(0, 0, 0, (int)($_GET['m'] ?? date('n')), 1, (int)($_GET['y'] ?? date('Y')));
    $tgt  = min(max($tgt, $awal), $maks);
    $jml  = (int)date('t', $tgt); $mulai = (int)date('N', $tgt); $hari_ini = date('Y-m-d');
    $rows = q("SELECT tanggal_booking, waktu_booking FROM bookings WHERE id_mua = ? AND status IN ('menunggu','dikonfirmasi') AND tanggal_booking BETWEEN ? AND ? ORDER BY tanggal_booking, waktu_booking", [$id, date('Y-m-01', $tgt), date('Y-m-t', $tgt)]);
    $per = []; foreach ($rows as $r) $per[(int)date('j', strtotime($r['tanggal_booking']))][] = substr($r['waktu_booking'], 0, 5);
    $prev = strtotime('-1 month', $tgt); $next = strtotime('+1 month', $tgt);
    $nav = function ($ts) use ($qs) { return '?' . $qs . '&tab=jadwal&m=' . date('n', $ts) . '&y=' . date('Y', $ts); }; ?>
    <div class="det-grid">
        <div class="det-card">
            <div class="cal-head"><h2><?php echo $nama_bln[(int)date('n', $tgt)] . ' ' . date('Y', $tgt); ?></h2>
                <div class="cal-nav"><a class="<?php echo $tgt <= $awal ? 'off' : ''; ?>" href="<?php echo $nav($prev); ?>"><i class="fa-solid fa-chevron-left"></i></a><a class="<?php echo $tgt >= $maks ? 'off' : ''; ?>" href="<?php echo $nav($next); ?>"><i class="fa-solid fa-chevron-right"></i></a></div></div>
            <div class="cal">
                <?php foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $h) echo '<div class="dow">' . $h . '</div>'; ?>
                <?php for ($i = 1; $i < $mulai; $i++) echo '<div class="day kosong"></div>';
                for ($d = 1; $d <= $jml; $d++) {
                    $tg = date('Y-m-', $tgt) . str_pad($d, 2, '0', STR_PAD_LEFT);
                    $kelas = 'day' . ($tg < $hari_ini ? ' past' : '') . ($tg === $hari_ini ? ' today' : '') . (isset($per[$d]) ? ' terisi' : '');
                    $isi = $d . (isset($per[$d]) ? '<small>' . count($per[$d]) . ' jadwal</small>' : '');
                    echo $tg < $hari_ini ? '<div class="' . $kelas . '">' . $isi . '</div>' : '<a class="' . $kelas . '" href="booking.php?mua=' . $id . '&tanggal=' . $tg . '" title="Booking tanggal ini">' . $isi . '</a>';
                } ?>
            </div>
            <div class="legend"><span><i style="background:#FBF8F4;border:1px solid #eadfe0"></i>Tersedia</span><span><i style="background:#FBF1DE"></i>Sudah ada jadwal (jam lain mungkin masih kosong)</span></div>
        </div>
        <div class="det-card">
            <h2>Jam yang sudah terisi</h2>
            <?php if ($per): ?><ul class="slot-list"><?php foreach ($per as $d => $jam) echo '<li><span>' . $d . ' ' . $nama_bln[(int)date('n', $tgt)] . '</span><b>' . e(implode(', ', $jam)) . ' WIB</b></li>'; ?></ul>
            <?php else: ?><p class="muted">Belum ada jadwal terisi bulan ini. Semua tanggal masih tersedia.</p><?php endif; ?>
            <a class="kb-fill" style="display:block;margin-top:18px" href="<?php echo $link_booking; ?>">Booking MUA Ini</a>
        </div>
    </div>

<?php else: // ---- review ----
    $dist = array_fill(1, 5, 0); foreach (q('SELECT rating, COUNT(*) AS n FROM bookings WHERE id_mua = ? AND rating > 0 GROUP BY rating', [$id]) as $r) $dist[(int)$r['rating']] = (int)$r['n'];
    $ulasan = q('SELECT b.rating, b.ulasan, b.tanggal_booking, s.nama_layanan, u.nama, u.foto_profil FROM bookings b JOIN users u ON u.id_user = b.id_pelanggan JOIN services s ON s.id_service = b.id_layanan WHERE b.id_mua = ? AND b.rating > 0 ORDER BY b.tanggal_booking DESC, b.id_booking DESC LIMIT 30', [$id]); ?>
    <div class="det-card">
        <?php if ($rt['n'] > 0): ?>
        <div class="rev-summary">
            <div class="rev-big"><b><?php echo number_format((float)$rt['rata'], 1); ?></b><?php echo bintang((float)$rt['rata']); ?><span class="muted small"><?php echo (int)$rt['n']; ?> ulasan</span></div>
            <div><?php for ($s = 5; $s >= 1; $s--) echo '<div class="rev-bar"><span>' . $s . '<i class="fa-solid fa-star" style="color:var(--star);font-size:.7rem"></i></span><div class="bar"><i style="width:' . round($dist[$s] / $rt['n'] * 100) . '%"></i></div><span class="muted">' . $dist[$s] . '</span></div>'; ?></div>
        </div>
        <?php else: ?><div class="kosong" style="padding:30px"><i class="fa-regular fa-star"></i><p>Belum ada ulasan. Ulasan muncul setelah klien menyelesaikan pesanan dan memberi rating.</p></div><?php endif; ?>
    </div>
    <?php if ($ulasan): ?><div class="det-card">
        <?php foreach ($ulasan as $u): ?>
            <div class="rev-item">
                <div class="rev-top"><?php echo foto($u['foto_profil'], 'avatar'); ?><div><h4><?php echo e(nama_singkat($u['nama'])); ?></h4><span class="muted"><?php echo e(date('d/m/Y', strtotime($u['tanggal_booking']))); ?></span></div><?php echo bintang((int)$u['rating']); ?></div>
                <span class="rev-svc"><?php echo e($u['nama_layanan']); ?></span>
                <?php if (trim((string)$u['ulasan']) !== '') echo '<p>' . nl2br(e($u['ulasan'])) . '</p>'; ?>
            </div>
        <?php endforeach; ?></div><?php endif; ?>
<?php endif; ?>
</main>
<?php bottom_nav();