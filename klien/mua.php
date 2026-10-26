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
$porto   = q('SELECT * FROM portfolios WHERE id_mua = ? ORDER BY id_portfolio DESC', [$id]);
$kats    = array_column(q("SELECT DISTINCT kategori FROM services WHERE id_mua = ? AND kategori <> ''", [$id]), 'kategori');
$rt      = q('SELECT ROUND(AVG(rating), 1) AS rata, COUNT(*) AS n FROM bookings WHERE id_mua = ? AND rating > 0', [$id])[0];
$mrow    = ['rating' => $rt['rata'], 'n_ulasan' => $rt['n']];
$fav     = (bool)q('SELECT 1 FROM favorit WHERE id_pelanggan = ? AND id_mua = ?', [$id_klien, $id]);
$bio     = trim((string)$m['bio']);
$ringkas = mb_strimwidth(preg_replace('/\s+/', ' ', $bio), 0, 150, '...');
$cover   = $porto[0]['foto'] ?? '';
$link_booking = 'booking.php?mua=' . $id . ($tgl ? '&tanggal=' . urlencode($tgl) : '');
$info = ['fav_on' => ['ok', 'Ditambahkan ke favorit Anda.'], 'fav_off' => ['ok', 'Dihapus dari favorit.'], 'gagal' => ['err', 'Aksi gagal, silakan coba lagi.'],
         'lap_ok' => ['ok', 'Laporan terkirim. Tim GlamLink akan meninjaunya. Terima kasih sudah membantu menjaga keamanan platform.'],
         'lap_dup' => ['err', 'Laporan Anda sebelumnya untuk MUA ini masih diproses.'], 'lap_isi' => ['err', 'Pilih alasan dan isi detail laporan (minimal 10 karakter).'],
         'lap_bukti' => ['err', 'Bukti gagal diunggah. Gunakan foto JPG/PNG/WEBP maksimal 5 MB.'], 'lap_gagal' => ['err', 'Laporan gagal dikirim, silakan coba lagi.']];
?>
<div class="mua-banner">
    <?php if ($cover && file_exists(__DIR__ . '/../assets/img/' . basename($cover))) echo '<img class="banner-img" src="../assets/img/' . e(basename($cover)) . '" alt="">'; ?>
    <a class="banner-back" href="cari.php<?php echo $tgl ? '?tanggal=' . urlencode($tgl) : ''; ?>"><span><i class="fa-solid fa-arrow-left"></i></span> Kembali</a>
</div>

<section class="mua-head-card">
    <?php echo foto($m['foto_profil'], 'avatar xl'); ?>
    <div class="mua-head-main">
        <h1><?php echo e($m['nama_brand']); ?></h1>
        <div class="mua-head-meta"><span class="pill"><i class="fa-solid fa-location-dot"></i> <?php echo e($m['lokasi']); ?></span><?php echo rating_teks($mrow); ?></div>
        <p class="short"><?php echo e($ringkas); ?></p>
    </div>
    <div class="head-actions">
        <button type="button" class="lapor-btn" onclick="document.getElementById('dlg-lapor').showModal()"><i class="fa-regular fa-flag"></i> Laporkan MUA</button>
        <div class="head-actions-row">
            <form method="POST" action="favorit.php"><?php echo csrf_field(); ?>
                <input type="hidden" name="id_mua" value="<?php echo $id; ?>"><input type="hidden" name="tab" value="<?php echo e($tab); ?>"><input type="hidden" name="tanggal" value="<?php echo e($tgl); ?>">
                <button class="heart-btn<?php echo $fav ? ' on' : ''; ?>" type="submit" title="<?php echo $fav ? 'Hapus dari favorit' : 'Tambahkan ke favorit'; ?>" aria-pressed="<?php echo $fav ? 'true' : 'false'; ?>"><i class="fa-<?php echo $fav ? 'solid' : 'regular'; ?> fa-heart"></i></button></form>
            <a class="kb-fill" href="<?php echo $link_booking; ?>">Booking MUA Ini</a>
        </div>
    </div>
</section>

<?php if (isset($_GET['info'], $info[$_GET['info']])) echo '<div class="det-wrap" style="padding-bottom:0;padding-top:20px"><p class="alert-' . $info[$_GET['info']][0] . '" style="margin:0">' . e($info[$_GET['info']][1]) . '</p></div>'; ?>

<nav class="det-tabs"><?php foreach ($tabs as $k => $l) echo '<a href="?' . $qs . '&tab=' . $k . '" class="' . ($tab === $k ? 'on' : '') . '">' . $l . '</a>'; ?></nav>

<main class="det-wrap">
<?php if ($tab === 'tentang'):
    $wa = format_wa($m['nomor_telepon']);
    $area = array_filter(array_map('trim', preg_split('/\s*[,\/&]\s*|\s+dan\s+/i', (string)$m['lokasi']))); ?>
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

<?php elseif ($tab === 'layanan'):
    $cl = trim($_GET['cl'] ?? '');
    $tampil = array_filter($layanan, function ($s) use ($cl) { return $cl === '' || stripos($s['nama_layanan'] . ' ' . $s['deskripsi'] . ' ' . $s['kategori'], $cl) !== false; }); ?>
    <form class="svc-search" method="GET"><input type="hidden" name="id" value="<?php echo $id; ?>"><input type="hidden" name="tab" value="layanan"><?php if ($tgl) echo '<input type="hidden" name="tanggal" value="' . e($tgl) . '">'; ?>
        <div><i class="fa-solid fa-magnifying-glass"></i><input type="search" name="cl" value="<?php echo e($cl); ?>" placeholder="Cari Layanan"></div></form>
    <div class="svc-grid">
        <?php foreach ($tampil as $s): ?>
            <article class="svc-card">
                <h3><?php echo e($s['nama_layanan']); ?></h3>
                <p class="svc-sub"><?php echo e($s['deskripsi'] ?: $s['kategori']); ?></p>
                <?php if ($s['durasi']) echo '<p class="svc-dur"><i class="fa-regular fa-clock"></i> ' . e($s['durasi']) . ' pengerjaan</p>'; ?>
                <div class="svc-foot"><div><small>Tarif Paket</small><b><?php echo rupiah($s['harga']); ?></b></div>
                    <a class="kb-fill sm" href="booking.php?mua=<?php echo $id; ?>&layanan=<?php echo (int)$s['id_service']; ?><?php echo $tgl ? '&tanggal=' . urlencode($tgl) : ''; ?>">Booking Sekarang</a></div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if (!$tampil) echo '<div class="kosong"><i class="fa-regular fa-clipboard"></i><p>' . ($cl !== '' ? 'Tidak ada layanan yang cocok dengan "' . e($cl) . '".' : 'MUA ini belum menambahkan layanan.') . '</p></div>'; ?>

<?php elseif ($tab === 'portfolio'): ?>
    <div class="pf-grid">
        <?php foreach ($porto as $i => $p):
            $nama  = basename((string)$p['foto']);
            $judul = trim((string)$p['judul']) ?: (($kats[0] ?? 'Portfolio') . ' Look');                       // otomatis jika belum diisi
            $tags  = array_filter(array_map('trim', explode(',', (string)$p['tag']))) ?: array_slice($kats, 0, 2);
            $desk  = trim((string)$p['deskripsi']);
            $ada   = file_exists(__DIR__ . '/../assets/img/' . $nama); ?>
            <button type="button" class="pf-card" data-img="<?php echo $ada ? '../assets/img/' . e($nama) : ''; ?>" data-judul="<?php echo e($judul); ?>" data-desk="<?php echo e($desk); ?>" data-tags="<?php echo e(implode(',', $tags)); ?>">
                <?php echo foto($nama, 'pf-img', 'fa-image'); ?>
                <span class="pf-info"><b><?php echo e($judul); ?></b>
                    <span class="chips"><?php foreach ($tags as $t) echo '<span class="chip">#' . e(str_replace(' ', '', $t)) . '</span>'; ?></span>
                    <?php if ($desk !== '') echo '<span class="pf-desc">' . e(mb_strimwidth($desk, 0, 120, '...')) . '</span>'; ?></span>
                <span class="pf-zoom"><i class="fa-solid fa-expand"></i></span>
            </button>
        <?php endforeach; ?>
    </div>
    <?php if (!$porto) echo '<div class="kosong"><i class="fa-regular fa-image"></i><p>Belum ada portfolio.</p></div>'; ?>
    <dialog class="lightbox" id="lb"><div class="lb-box"><img alt=""><div class="lb-text"><h3></h3><div class="chips"></div><p></p></div></div></dialog>
    <script>
        var lb = document.getElementById('lb');
        document.querySelectorAll('.pf-card').forEach(function (c) {
            c.addEventListener('click', function () {
                if (!c.dataset.img) return;
                lb.querySelector('img').src = c.dataset.img;
                lb.querySelector('h3').textContent = c.dataset.judul;
                lb.querySelector('p').textContent = c.dataset.desk;
                var ch = lb.querySelector('.chips'); ch.innerHTML = '';
                c.dataset.tags.split(',').forEach(function (t) { if (t.trim()) { var s = document.createElement('span'); s.className = 'chip'; s.textContent = '#' + t.trim().replace(/\s+/g, ''); ch.appendChild(s); } });
                lb.showModal();
            });
        });
        lb.addEventListener('click', function (e) { if (e.target === lb || e.target.tagName === 'IMG') lb.close(); });
    </script>

<?php elseif ($tab === 'jadwal'):
    // ---------- Kalender + slot jam. Status hari: tersedia / penuh (semua slot terisi) / cuti / lewat ----------
    $hari_ini = date('Y-m-d'); $skrg = date('H:i');
    $awal = strtotime(date('Y-m-01')); $maks = strtotime('+12 months', $awal);
    $dipilih = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['d'] ?? '') ? $_GET['d'] : '';
    $basis = $dipilih ?: (isset($_GET['m'], $_GET['y']) ? '' : ($tgl >= $hari_ini ? $tgl : ''));
    $tgt = isset($_GET['m'], $_GET['y']) ? mktime(0, 0, 0, (int)$_GET['m'], 1, (int)$_GET['y']) : ($basis ? strtotime(date('Y-m-01', strtotime($basis))) : $awal);
    $tgt = min(max($tgt, $awal), $maks);
    $jml = (int)date('t', $tgt); $mulai_dow = (int)date('N', $tgt);

    $libur = array_column(q('SELECT tanggal FROM mua_libur WHERE id_mua = ? AND tanggal BETWEEN ? AND ?', [$id, date('Y-m-01', $tgt), date('Y-m-t', $tgt)]), 'tanggal');
    $terisi = [];   // [tanggal][nomor slot] = true
    foreach (q("SELECT tanggal_booking, waktu_booking FROM bookings WHERE id_mua = ? AND status IN ('menunggu','dikonfirmasi') AND tanggal_booking BETWEEN ? AND ?", [$id, date('Y-m-01', $tgt), date('Y-m-t', $tgt)]) as $r) {
        $si = slot_index($r['waktu_booking']); if ($si >= 0) $terisi[$r['tanggal_booking']][$si] = true;
    }
    $status_hari = function ($tg) use ($hari_ini, $libur, $terisi, $skrg) {
        if ($tg < $hari_ini) return 'lewat';
        if (in_array($tg, $libur, true)) return 'cuti';
        $bebas = 0; foreach (SLOT_JAM as $i => [$a, $b]) if (empty($terisi[$tg][$i]) && !($tg === $hari_ini && $a <= $skrg)) $bebas++;
        return $bebas > 0 ? 'tersedia' : 'penuh';
    };
    if (!$dipilih || date('Y-m', strtotime($dipilih)) !== date('Y-m', $tgt) || $status_hari($dipilih) === 'lewat') {   // pilih otomatis hari terdekat yang tersedia
        $dipilih = '';
        if ($tgl && date('Y-m', strtotime($tgl)) === date('Y-m', $tgt) && $status_hari($tgl) !== 'lewat') $dipilih = $tgl;
        else for ($d = 1; $d <= $jml; $d++) { $tg = date('Y-m-', $tgt) . str_pad($d, 2, '0', STR_PAD_LEFT); if ($status_hari($tg) === 'tersedia') { $dipilih = $tg; break; } }
    }
    $nav = function ($ts) use ($qs) { return '?' . $qs . '&tab=jadwal&m=' . date('n', $ts) . '&y=' . date('Y', $ts); }; ?>
    <div class="jd-grid">
        <div class="jd-card">
            <div class="jd-head"><a class="<?php echo $tgt <= $awal ? 'off' : ''; ?>" href="<?php echo $nav(strtotime('-1 month', $tgt)); ?>" aria-label="Bulan sebelumnya"><i class="fa-solid fa-chevron-left"></i></a>
                <h2><?php echo NAMA_BULAN[(int)date('n', $tgt)] . ' ' . date('Y', $tgt); ?></h2>
                <a class="<?php echo $tgt >= $maks ? 'off' : ''; ?>" href="<?php echo $nav(strtotime('+1 month', $tgt)); ?>" aria-label="Bulan berikutnya"><i class="fa-solid fa-chevron-right"></i></a></div>
            <div class="jd-cal">
                <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $h) echo '<div class="dow' . ($h === 'Sun' ? ' sun' : '') . '">' . $h . '</div>';
                for ($i = 1; $i < $mulai_dow; $i++) echo '<div></div>';
                for ($d = 1; $d <= $jml; $d++) {
                    $tg = date('Y-m-', $tgt) . str_pad($d, 2, '0', STR_PAD_LEFT); $st = $status_hari($tg);
                    $kelas = 'jd-day s-' . $st . ($tg === $dipilih ? ' sel' : '');
                    echo $st === 'lewat' ? '<div class="' . $kelas . '"><span>' . $d . '</span></div>'
                        : '<a class="' . $kelas . '" href="' . $nav($tgt) . '&d=' . $tg . '"><span>' . $d . '</span><i></i></a>';
                } ?>
            </div>
            <div class="jd-legend"><span><i class="dot t"></i>Tersedia</span><span><i class="dot c"></i>Cuti</span><span><i class="dot p"></i>Penuh</span></div>
        </div>

        <div class="jd-card">
            <?php if ($dipilih): $ts = strtotime($dipilih); $sd = $status_hari($dipilih); ?>
                <span class="jd-over">KETERSEDIAAN</span>
                <h2 class="jd-title"><?php echo NAMA_HARI[(int)date('w', $ts)] . ', ' . (int)date('j', $ts) . ' ' . NAMA_BULAN[(int)date('n', $ts)] . ' ' . date('Y', $ts); ?></h2>
                <?php if ($sd === 'cuti'): ?><p class="jd-note"><i class="fa-regular fa-calendar-xmark"></i> MUA sedang cuti pada tanggal ini dan tidak menerima pesanan.</p>
                <?php else: ?>
                    <p class="muted jd-sub">Jam yang tersedia</p>
                    <div class="slot-grid">
                    <?php foreach (SLOT_JAM as $i => [$a, $b]):
                        $penuh = !empty($terisi[$dipilih][$i]) || ($dipilih === $hari_ini && $a <= $skrg);
                        echo $penuh ? '<div class="slot full"><span>' . $a . ' - ' . $b . '</span><small>' . (!empty($terisi[$dipilih][$i]) ? 'Penuh' : 'Lewat') . '</small></div>'
                                    : '<a class="slot" href="booking.php?mua=' . $id . '&tanggal=' . $dipilih . '&jam=' . $a . '"><span>' . $a . ' - ' . $b . '</span><small>Tersedia</small></a>';
                    endforeach; ?>
                    </div>
                    <p class="muted small" style="margin:14px 0 0">Klik jam yang tersedia untuk lanjut booking.</p>
                <?php endif; ?>
            <?php else: ?>
                <span class="jd-over">KETERSEDIAAN</span><p class="jd-note"><i class="fa-regular fa-calendar"></i> Tidak ada tanggal tersedia bulan ini. Coba bulan berikutnya.</p>
            <?php endif; ?>
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

<!-- Pop-up Laporkan MUA -->
<dialog class="lapor-dlg" id="dlg-lapor">
    <form method="POST" action="laporan.php" enctype="multipart/form-data">
        <?php echo csrf_field(); ?><input type="hidden" name="id_mua" value="<?php echo $id; ?>">
        <h3>Laporkan MUA</h3>
        <p class="lapor-sub">Bantu kami menjaga GlamLink tetap aman dengan menjelaskan masalah yang kamu alami</p>
        <h4>Apa masalah yang Anda temukan? <span class="req">*</span></h4>
        <?php foreach (ALASAN_LAPORAN as $i => $a) echo '<label class="radio-row"><input type="radio" name="alasan" value="' . e($a) . '"' . ($i === 0 ? ' required' : '') . '><span class="rd"></span>' . e($a) . '</label>'; ?>
        <h4 style="margin-top:22px">Detail Laporan <span class="req">*</span></h4>
        <textarea name="detail" rows="4" required minlength="10" maxlength="2000" placeholder="Ceritakan kejadian yang dialami..."></textarea>
        <label class="bukti-row"><i class="fa-solid fa-link"></i>
            <span><b>Tambah bukti</b> <em>(opsional)</em><small id="bukti-nama">Screenshot, foto, atau bukti pendukung</small></span>
            <span class="plus"><i class="fa-solid fa-plus"></i></span>
            <input type="file" name="bukti" id="bukti-file" accept="image/jpeg,image/png,image/webp" hidden></label>
        <div class="dlg-actions"><button type="button" class="kb-outline" onclick="document.getElementById('dlg-lapor').close()">Batal</button><button type="submit" class="kb-fill">Kirim Laporan</button></div>
    </form>
</dialog>
<script>
    document.getElementById('bukti-file').addEventListener('change', function () { document.getElementById('bukti-nama').textContent = this.files.length ? this.files[0].name : 'Screenshot, foto, atau bukti pendukung'; });
</script>
<?php bottom_nav();