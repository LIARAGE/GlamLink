<?php
session_start();
require __DIR__ . '/../Includes/klien.php';

/**
 * BOOKING MUA - wizard 4 langkah (sesuai desain): 1 Pilih Layanan -> 2 Jadwal -> 3 Detail Acara -> 4 Pembayaran.
 * Perpindahan antar langkah terjadi di browser (assets/js/booking.js); SEMUA data dikirim sekali di langkah 4
 * dan DIVALIDASI ULANG penuh di sini. Harga & biaya platform selalu dihitung server dari database.
 *
 * Bisa dibuka dari: ?mua=ID (kartu MUA) | ?mua=ID&layanan=ID (tab Layanan) | ?mua=ID&tanggal=YYYY-MM-DD&jam=HH:MM (tab Jadwal)
 */

/** Ambil input sebagai string (POST dulu, lalu GET); array dari URL nakal diabaikan */
function input($k) { $v = $_POST[$k] ?? $_GET[$k] ?? ''; return is_string($v) ? trim($v) : ''; }
function tgl_ok($t) { return (bool)preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $t, $m) && checkdate((int)$m[2], (int)$m[3], (int)$m[1]); }

/* ---------- 1. MUA & layanannya ---------- */
$id_layanan = (int)input('layanan');
$id_mua     = (int)input('mua');
if (!$id_mua && $id_layanan) { $id_mua = (int)(q('SELECT id_mua FROM services WHERE id_service = ?', [$id_layanan])[0]['id_mua'] ?? 0); }

$mua = q("SELECT id_user, nama_brand, lokasi FROM users WHERE id_user = ? AND role = 'mua' AND status_verifikasi = 'approved'", [$id_mua])[0] ?? null;
if (!$mua) { header('Location: cari.php'); exit(); }
$layanan = q('SELECT * FROM services WHERE id_mua = ? ORDER BY id_service', [$id_mua]);

$hari_ini = date('Y-m-d');
$skrg     = date('H:i');
$maks     = date('Y-m-t', strtotime('+12 months'));      // tanggal terjauh yang boleh dibooking (akhir bulan ke-12)
$mulai_slot = array_column(SLOT_JAM, 0);

/** Layanan terpilih: dari URL/form bila milik MUA ini, kalau tidak -> layanan pertama */
$by_id = []; foreach ($layanan as $s) { $by_id[(int)$s['id_service']] = $s; }
$layanan_dari_url = isset($by_id[$id_layanan]);
$pilih_id = $layanan_dari_url ? $id_layanan : (int)($layanan[0]['id_service'] ?? 0);

/* ---------- 2. Nilai awal form (dari URL), langkah awal ---------- */
$v = ['tanggal' => '', 'waktu' => '', 'nama_acara' => '', 'jenis' => '', 'kota' => '', 'alamat' => '', 'catatan' => '', 'metode' => 'Transfer Bank'];
if (tgl_ok(input('tanggal')) && input('tanggal') >= $hari_ini && input('tanggal') <= $maks) { $v['tanggal'] = input('tanggal'); }
if (in_array(input('jam'), $mulai_slot, true)) { $v['waktu'] = input('jam'); }
$langkah = $layanan_dari_url ? ($v['tanggal'] && $v['waktu'] ? 3 : 2) : 1;
$error = '';

/* ---------- 3. Proses pembayaran (POST dari langkah 4) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v = ['tanggal' => input('tanggal'), 'waktu' => input('waktu'), 'nama_acara' => input('nama_acara'), 'jenis' => input('jenis'),
          'kota' => input('kota'), 'alamat' => input('alamat'), 'catatan' => input('catatan'), 'metode' => input('metode')];
    $s = $by_id[$id_layanan] ?? null;              // layanan HARUS milik MUA ini
    $pilih_id = $s ? $id_layanan : $pilih_id;
    $err = function ($pesan, $step) use (&$error, &$langkah) { $error = $pesan; $langkah = $step; };

    if (!csrf_ok())                                                                   $err('Sesi formulir sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.', 1);
    elseif (!$s)                                                                      $err('Pilih layanan yang tersedia.', 1);
    elseif (!tgl_ok($v['tanggal']) || $v['tanggal'] < $hari_ini || $v['tanggal'] > $maks) $err('Pilih tanggal acara yang tersedia (hari ini sampai 12 bulan ke depan).', 2);
    elseif (!in_array($v['waktu'], $mulai_slot, true))                                $err('Pilih salah satu jam yang tersedia.', 2);
    elseif ($v['tanggal'] === $hari_ini && $v['waktu'] <= $skrg)                      $err('Jam tersebut sudah lewat. Pilih jam berikutnya.', 2);
    elseif (q('SELECT 1 FROM mua_libur WHERE id_mua = ? AND tanggal = ?', [$id_mua, $v['tanggal']])) $err('MUA sedang cuti pada tanggal tersebut. Silakan pilih tanggal lain.', 2);
    elseif (!in_array($v['jenis'], KATEGORI_LAYANAN, true))                           $err('Pilih jenis acara.', 3);
    elseif (!in_array($v['kota'], WILAYAH, true))                                     $err('Pilih kota lokasi acara.', 3);
    elseif ($v['alamat'] === '' || mb_strlen($v['alamat']) > 200)                     $err('Alamat lengkap wajib diisi (maksimal 200 karakter).', 3);
    elseif (mb_strlen($v['nama_acara']) > 150)                                        $err('Nama acara maksimal 150 karakter.', 3);
    elseif (mb_strlen($v['catatan']) > 1000)                                          $err('Catatan maksimal 1000 karakter.', 3);
    elseif (!array_key_exists($v['metode'], METODE_BAYAR))                            $err('Pilih metode pembayaran.', 4);
    else {
        [$a, $b] = SLOT_JAM[slot_index($v['waktu'])];
        $harga  = (int)round($s['harga']);
        $fee    = biaya_platform($harga);
        $lokasi = $v['alamat'] . ', ' . $v['kota'];          // lokasi_acara dipakai halaman MUA, jadi sudah memuat kota

        // Transaksi + kunci baris MUA: dua klien yang menekan "Bayar" bersamaan untuk slot yang sama TIDAK bisa sama-sama lolos
        $koneksi->begin_transaction();
        try {
            q('SELECT id_user FROM users WHERE id_user = ? FOR UPDATE', [$id_mua]);
            $bentrok = q("SELECT 1 FROM bookings WHERE id_mua = ? AND tanggal_booking = ? AND waktu_booking >= ? AND waktu_booking < ? AND status IN ('menunggu','dikonfirmasi')", [$id_mua, $v['tanggal'], $a . ':00', $b . ':00']);
            $libur   = q('SELECT 1 FROM mua_libur WHERE id_mua = ? AND tanggal = ?', [$id_mua, $v['tanggal']]);
            if ($bentrok || $libur) {
                $koneksi->rollback();
                $err($libur ? 'MUA sedang cuti pada tanggal tersebut. Silakan pilih tanggal lain.' : 'Jadwal tersebut baru saja terisi. Silakan pilih tanggal atau jam lain.', 2);
            } else {
                $invoice = '';
                for ($i = 0; $i < 10 && $invoice === ''; $i++) {
                    $kode = 'GL-' . random_int(1000, 9999);
                    if (!q('SELECT 1 FROM bookings WHERE no_invoice = ?', [$kode])) { $invoice = $kode; }
                }
                if ($invoice === '') { throw new RuntimeException('invoice'); }
                q("INSERT INTO bookings (no_invoice, id_mua, id_pelanggan, id_layanan, tanggal_booking, waktu_booking, lokasi_acara, catatan, total_harga, status,
                                         nama_acara, jenis_acara, kota_acara, biaya_platform, metode_bayar, dibayar_pada)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'menunggu', ?, ?, ?, ?, ?, NOW())",
                  [$invoice, $id_mua, $id_klien, $s['id_service'], $v['tanggal'], $v['waktu'] . ':00', $lokasi, $v['catatan'] !== '' ? $v['catatan'] : null, $harga,
                   $v['nama_acara'] !== '' ? $v['nama_acara'] : null, $v['jenis'], $v['kota'], $fee, $v['metode']]);
                $koneksi->commit();
                header('Location: history.php?ok=' . $invoice); exit();
            }
        } catch (Throwable $ex) {
            $koneksi->rollback();
            $err('Pembayaran gagal diproses. Tidak ada dana yang terpotong, silakan coba lagi.', 4);
        }
    }
}

/* ---------- 4. Data jadwal untuk kalender (cuti & slot terisi, hari ini s.d. 12 bulan) ---------- */
$libur = array_column(q('SELECT tanggal FROM mua_libur WHERE id_mua = ? AND tanggal BETWEEN ? AND ?', [$id_mua, $hari_ini, $maks]), 'tanggal');
$terisi = [];   // [tanggal] => [nomor slot, ...]; hanya nomor slot yang dibuka ke browser (tanpa data klien lain)
foreach (q("SELECT tanggal_booking, waktu_booking FROM bookings WHERE id_mua = ? AND status IN ('menunggu','dikonfirmasi') AND tanggal_booking BETWEEN ? AND ?", [$id_mua, $hari_ini, $maks]) as $r) {
    $i = slot_index($r['waktu_booking']); if ($i >= 0) $terisi[$r['tanggal_booking']][$i] = $i;
}
foreach ($terisi as $t => $m) { $terisi[$t] = array_values($m); }

$cfg = [
    'langkah' => $langkah, 'hariIni' => $hari_ini, 'jamSekarang' => $skrg, 'maxTanggal' => $maks, 'slot' => SLOT_JAM,
    'libur' => $libur, 'terisi' => (object)$terisi, 'persen' => BIAYA_PLATFORM_PERSEN,
    'awal' => ['tanggal' => $v['tanggal'], 'jam' => $v['waktu']],
];
$langkah_label = ['Pilih Layanan', 'Jadwal', 'Detail Acara', 'Pembayaran'];

head_klien('Booking MUA');
?>
<header class="bk-header"><a href="index.php"><img src="../assets/img/logo.png" alt="GlamLink"></a></header>
<div class="bk-band"><div class="bk-wrap bk-band-in">
    <a class="bk-back" href="mua.php?id=<?php echo $id_mua; ?>" aria-label="Kembali ke profil MUA"><i class="fa-solid fa-arrow-left"></i></a>
    <h1>Booking MUA</h1><span class="bk-mua"><?php echo e($mua['nama_brand']); ?></span>
</div></div>

<main class="bk-wrap">
<?php if (!$layanan): ?>
    <div class="kosong"><i class="fa-regular fa-face-frown"></i><p><b><?php echo e($mua['nama_brand']); ?></b> belum menambahkan layanan, sehingga belum bisa dibooking.</p><a class="kb-fill" href="cari.php">Cari MUA lain</a></div>
<?php else: ?>
    <ol class="bk-stepper" aria-label="Langkah booking">
        <?php foreach ($langkah_label as $i => $l) echo '<li class="bk-step" data-step="' . ($i + 1) . '"><span class="bk-dot">' . ($i + 1) . '</span><span class="bk-lbl">' . $l . '</span></li>'; ?>
    </ol>
    <noscript><p class="alert-err">Aktifkan JavaScript di browser Anda untuk melakukan booking.</p></noscript>
    <?php if ($error) echo '<p class="alert-err bk-alert" role="alert">' . e($error) . '</p>'; ?>

    <form id="bk-form" method="POST" action="booking.php?mua=<?php echo $id_mua; ?>" novalidate>
    <?php echo csrf_field(); ?>
    <input type="hidden" name="tanggal" id="f-tanggal" value="<?php echo e($v['tanggal']); ?>">
    <input type="hidden" name="waktu" id="f-waktu" value="<?php echo e($v['waktu']); ?>">
    <div class="bk-grid">
        <div class="bk-left">
            <!-- ===== Langkah 1: Pilih Layanan ===== -->
            <section class="bk-card bk-panel" data-step="1">
                <h2>Pilih Layanan</h2>
                <p class="bk-sub">Pilih layanan makeup yang sesuai dengan kebutuhan Anda.</p>
                <label class="bk-search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" id="bk-cari" placeholder="Cari Layanan" aria-label="Cari layanan" autocomplete="off"></label>
                <div class="bk-svc-list">
                <?php foreach ($layanan as $s): $sid = (int)$s['id_service'];
                    $ring = trim((string)strtok((string)$s['deskripsi'], "\n")); ?>
                    <label class="bk-svc" data-cari="<?php echo e(mb_strtolower($s['nama_layanan'] . ' ' . $s['kategori'] . ' ' . $s['deskripsi'])); ?>">
                        <input type="radio" name="layanan" value="<?php echo $sid; ?>" <?php echo $sid === $pilih_id ? 'checked' : ''; ?>
                               data-nama="<?php echo e($s['nama_layanan']); ?>" data-harga="<?php echo (int)round($s['harga']); ?>" data-kategori="<?php echo e($s['kategori']); ?>">
                        <span class="bk-radio" aria-hidden="true"></span>
                        <span class="bk-svc-main"><b><?php echo e($s['nama_layanan']); ?></b>
                            <?php if ($ring !== '') echo '<small>' . e(mb_strimwidth($ring, 0, 80, '…', 'UTF-8')) . '</small>'; ?>
                            <?php if (trim((string)$s['durasi']) !== '') echo '<span class="bk-dur"><i class="fa-regular fa-clock"></i> ' . e($s['durasi']) . ' pengerjaan</span>'; ?></span>
                        <span class="bk-harga"><?php echo rupiah($s['harga']); ?></span>
                    </label>
                <?php endforeach; ?>
                </div>
                <p class="bk-kosong" id="bk-cari-kosong" hidden>Tidak ada layanan yang cocok dengan pencarian Anda.</p>
            </section>

            <!-- ===== Langkah 2: Jadwal ===== -->
            <section class="bk-card bk-panel" data-step="2" hidden>
                <h2>Pilih Tanggal &amp; Waktu</h2>
                <div class="bk-jadwal">
                    <div class="bk-cal" id="bk-cal"></div>
                    <div class="bk-slots" id="bk-slots"></div>
                </div>
            </section>

            <!-- ===== Langkah 3: Detail Acara ===== -->
            <section class="bk-card bk-panel" data-step="3" hidden>
                <h2>Detail Acara</h2>
                <div class="bk-field"><label for="f-nama">Nama Acara</label><input type="text" id="f-nama" name="nama_acara" maxlength="150" placeholder="Opsional" value="<?php echo e($v['nama_acara']); ?>"></div>
                <div class="bk-two">
                    <div class="bk-field"><label for="f-jenis">Jenis Acara</label>
                        <select id="f-jenis" name="jenis"><option value="">Pilih Kategori</option>
                            <?php foreach (KATEGORI_LAYANAN as $k) echo '<option' . ($v['jenis'] === $k ? ' selected' : '') . '>' . e($k) . '</option>'; ?></select></div>
                    <div class="bk-field"><label for="f-kota">Lokasi</label>
                        <select id="f-kota" name="kota"><option value="">Pilih Kota</option>
                            <?php foreach (WILAYAH as $k) echo '<option' . ($v['kota'] === $k ? ' selected' : '') . '>' . e($k) . '</option>'; ?></select></div>
                </div>
                <div class="bk-field"><label for="f-alamat">Alamat Lengkap</label><textarea id="f-alamat" name="alamat" rows="4" maxlength="200" placeholder="Contoh: Hotel Mulia, Jl. Asia Afrika, Senayan"><?php echo e($v['alamat']); ?></textarea></div>
                <div class="bk-field"><label for="f-catatan">Catatan untuk MUA <span class="bk-opt">(opsional)</span></label><textarea id="f-catatan" name="catatan" rows="3" maxlength="1000" placeholder="Tema, preferensi makeup, jumlah orang yang dirias..."><?php echo e($v['catatan']); ?></textarea></div>
            </section>

            <!-- ===== Langkah 4: Pembayaran ===== -->
            <section class="bk-card bk-panel" data-step="4" hidden>
                <h2>Pembayaran</h2>
                <div class="bk-metode">
                <?php foreach (METODE_BAYAR as $nama => $ket): ?>
                    <label class="bk-svc bk-pay"><input type="radio" name="metode" value="<?php echo e($nama); ?>" <?php echo $v['metode'] === $nama ? 'checked' : ''; ?>>
                        <span class="bk-radio" aria-hidden="true"></span><span class="bk-svc-main"><b><?php echo e($nama); ?></b><small><?php echo e($ket); ?></small></span></label>
                <?php endforeach; ?>
                </div>
                <p class="bk-note"><i class="fa-regular fa-circle-check"></i> Setelah pembayaran berhasil, pesanan dikirim ke MUA untuk dikonfirmasi.</p>
            </section>

            <div class="bk-actions" id="bk-actions" hidden>
                <button type="button" class="bk-btn bk-btn-line" id="bk-back">Kembali</button>
                <button type="button" class="bk-btn bk-btn-fill" id="bk-next">Lanjutkan</button>
                <button type="submit" class="bk-btn bk-btn-fill" id="bk-bayar" hidden>Bayar Sekarang</button>
            </div>
        </div>

        <aside class="bk-right">
            <div class="bk-card bk-sum">
                <h2>Booking Summary</h2>
                <dl>
                    <div><dt>Jenis Layanan</dt><dd id="s-layanan">-</dd></div>
                    <div><dt>Tanggal</dt><dd id="s-tanggal">Belum Dipilih</dd></div>
                    <div><dt>Waktu</dt><dd id="s-waktu">Belum Dipilih</dd></div>
                    <div><dt>Lokasi</dt><dd id="s-lokasi">Belum Dipilih</dd></div>
                </dl>
                <dl class="bk-sum-biaya">
                    <div><dt>Biaya Layanan</dt><dd id="s-biaya">-</dd></div>
                    <div><dt>Biaya Platform (<?php echo BIAYA_PLATFORM_PERSEN; ?>%)</dt><dd id="s-fee">-</dd></div>
                </dl>
                <div class="bk-total"><span>Total Biaya</span><b id="s-total">-</b></div>
            </div>
            <button type="button" class="bk-btn bk-btn-fill bk-btn-wide" id="bk-next1">Lanjutkan</button>
        </aside>
    </div>
    </form>
    <script type="application/json" id="bk-data"><?php echo json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
    <script src="../assets/js/booking.js" defer></script>
<?php endif; ?>
</main>
</body></html>