<?php
/**
 * KEUANGAN MUA (dibuka dari kartu "Total Pendapatan" di Dashboard)
 *
 * Cara hitung (semua dari database, tidak ada angka yang ditulis manual):
 *   Pendapatan     = total_harga semua pesanan berstatus 'selesai'
 *   Saldo tersedia = Pendapatan - semua penarikan (diproses + berhasil)
 *   Saldo escrow   = pesanan yang SUDAH DIBAYAR klien tapi belum selesai (menunggu / dikonfirmasi)
 *
 * CATATAN: penarikan dana masih SIMULASI (langsung berstatus "berhasil").
 * Belum terhubung ke API bank / payment gateway sungguhan.
 */
session_start();
require_once '../includes/auth_mua.php';   // koneksi + cek login MUA + $id_mua
require_once '../includes/mua_ui.php';     // toast

const TUJUAN_TARIK = [
    'BCA' => 'bank', 'Mandiri' => 'bank', 'BRI' => 'bank', 'BNI' => 'bank',
    'GoPay' => 'ewallet', 'OVO' => 'ewallet', 'DANA' => 'ewallet',
];
const MIN_TARIK = 50000;

function rp($n): string { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function ke($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** Ringkasan angka keuangan MUA */
function ringkasan_keuangan(mysqli $k, int $id_mua): array {
    $st = $k->prepare("
        SELECT
          COALESCE(SUM(CASE WHEN status = 'selesai' THEN total_harga END), 0) AS pendapatan,
          COUNT(CASE WHEN status = 'selesai' THEN 1 END) AS sesi,
          COUNT(CASE WHEN status = 'selesai' AND foto_dokumentasi IS NOT NULL AND foto_dokumentasi <> '' THEN 1 END) AS terdokumentasi,
          COALESCE(SUM(CASE WHEN status IN ('menunggu','dikonfirmasi') AND dibayar_pada IS NOT NULL THEN total_harga END), 0) AS escrow,
          ROUND(AVG(CASE WHEN rating > 0 THEN rating END), 1) AS rating,
          COUNT(CASE WHEN rating > 0 THEN 1 END) AS jml_rating
        FROM bookings WHERE id_mua = ?");
    $st->bind_param('i', $id_mua);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();

    $st = $k->prepare("
        SELECT COALESCE(SUM(CASE WHEN status IN ('diproses','berhasil') THEN jumlah END), 0) AS terpakai,
               COALESCE(SUM(CASE WHEN status = 'berhasil' THEN jumlah END), 0) AS dicairkan,
               COUNT(CASE WHEN status = 'berhasil' THEN 1 END) AS kali
        FROM mua_penarikan WHERE id_mua = ?");
    $st->bind_param('i', $id_mua);
    $st->execute();
    $t = $st->get_result()->fetch_assoc();

    return [
        'pendapatan'     => (int)$r['pendapatan'],
        'sesi'           => (int)$r['sesi'],
        'terdokumentasi' => (int)$r['terdokumentasi'],
        'escrow'         => (int)$r['escrow'],
        'rating'         => $r['rating'] !== null ? (float)$r['rating'] : 0,
        'jml_rating'     => (int)$r['jml_rating'],
        'dicairkan'      => (int)$t['dicairkan'],
        'kali_tarik'     => (int)$t['kali'],
        'saldo'          => max(0, (int)$r['pendapatan'] - (int)$t['terpakai']),
    ];
}

/* ================= EKSPOR MUTASI (CSV, bisa dibuka di Excel) ================= */
if (isset($_GET['ekspor'])) {
    $mutasi = [];
    $st = $koneksi->prepare("SELECT b.tanggal_booking, b.no_invoice, b.total_harga, s.nama_layanan
                             FROM bookings b LEFT JOIN services s ON s.id_service = b.id_layanan
                             WHERE b.id_mua = ? AND b.status = 'selesai'");
    $st->bind_param('i', $id_mua);
    $st->execute();
    foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $b) {
        $mutasi[] = [$b['tanggal_booking'], 'Masuk', 'Pesanan selesai - ' . ($b['nama_layanan'] ?? '-'), $b['no_invoice'], (int)$b['total_harga']];
    }
    $st = $koneksi->prepare("SELECT dibuat_pada, no_referensi, tujuan, nomor_tujuan, jumlah, status FROM mua_penarikan WHERE id_mua = ?");
    $st->bind_param('i', $id_mua);
    $st->execute();
    foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $p) {
        $mutasi[] = [$p['dibuat_pada'], 'Keluar', 'Penarikan ke ' . $p['tujuan'] . ' ' . $p['nomor_tujuan'] . ' (' . $p['status'] . ')', $p['no_referensi'], -(int)$p['jumlah']];
    }
    usort($mutasi, fn($a, $b) => strcmp($a[0], $b[0]));

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="mutasi-glamlink-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");                                   // BOM agar Excel membaca UTF-8
    fputcsv($out, ['Tanggal', 'Jenis', 'Keterangan', 'Referensi', 'Jumlah (Rp)'], ';');
    foreach ($mutasi as $m) fputcsv($out, $m, ';');
    fputcsv($out, []);
    $sum = ringkasan_keuangan($koneksi, $id_mua);
    fputcsv($out, ['', '', 'Saldo tersedia saat ini', '', $sum['saldo']], ';');
    fclose($out);
    exit();
}

/* ================= TARIK DANA ================= */
if (isset($_POST['tarik_dana'])) {
    $tujuan = $_POST['tujuan'] ?? '';
    $nomor  = preg_replace('/[\s\-.]/', '', $_POST['nomor_tujuan'] ?? '');
    $nama   = trim($_POST['nama_penerima'] ?? '');
    $jumlah = (int)preg_replace('/\D/', '', $_POST['jumlah'] ?? '0');
    $jenis  = TUJUAN_TARIK[$tujuan] ?? null;

    $error = null;
    if (!$jenis) $error = 'Pilih tujuan pencairan.';
    elseif ($jenis === 'bank' && !preg_match('/^\d{8,20}$/', $nomor)) $error = 'Nomor rekening harus 8-20 digit angka.';
    elseif ($jenis === 'ewallet' && !preg_match('/^(08|628)\d{8,12}$/', $nomor)) $error = 'Nomor HP e-wallet tidak valid (contoh: 081234567890).';
    elseif (mb_strlen($nama) < 3 || mb_strlen($nama) > 100) $error = 'Nama penerima wajib diisi (3-100 karakter).';
    elseif ($jumlah < MIN_TARIK) $error = 'Minimal penarikan ' . rp(MIN_TARIK) . '.';

    if (!$error) {
        // Kunci baris user selama transaksi supaya dua klik bersamaan tidak menarik saldo dua kali
        $koneksi->begin_transaction();
        $st = $koneksi->prepare("SELECT id_user FROM users WHERE id_user = ? FOR UPDATE");
        $st->bind_param('i', $id_mua);
        $st->execute();
        $st->get_result()->free();                     // hasil harus dibaca dulu sebelum query berikutnya
        $saldo = ringkasan_keuangan($koneksi, $id_mua)['saldo'];

        if ($jumlah > $saldo) {
            $koneksi->rollback();
            $error = 'Saldo tidak cukup. Saldo tersedia ' . rp($saldo) . '.';
        } else {
            $cek = $koneksi->prepare("SELECT 1 FROM mua_penarikan WHERE no_referensi = ?");
            do {
                $ref = 'WD-' . date('Y') . '-' . random_int(1000, 9999);
                $cek->bind_param('s', $ref);
                $cek->execute();
            } while ($cek->get_result()->num_rows > 0);

            $st = $koneksi->prepare("INSERT INTO mua_penarikan (no_referensi, id_mua, tujuan, jenis, nomor_tujuan, nama_penerima, jumlah, status)
                                     VALUES (?, ?, ?, ?, ?, ?, ?, 'berhasil')");
            $st->bind_param('sissssi', $ref, $id_mua, $tujuan, $jenis, $nomor, $nama, $jumlah);
            $st->execute();
            $koneksi->commit();

            // Notifikasi "Penarikan Dana Berhasil" (jika fitur notifikasi sudah dipasang)
            try {
                $judul = 'Penarikan Dana Berhasil Diproses';
                $pesan = 'Penarikan saldo sebesar ' . rp($jumlah) . ' ke ' . ($jenis === 'bank' ? 'Rekening ' : '') . "$tujuan ($nomor) a/n $nama telah berhasil ditransfer.";
                $kunci = 'tarik:' . $ref;
                $st = $koneksi->prepare("INSERT IGNORE INTO mua_notifikasi (id_mua, kunci, tipe, judul, pesan, link, label_aksi, waktu, dibaca_pada)
                                         VALUES (?, ?, 'tarik', ?, ?, 'keuangan.php', 'Lihat Riwayat Penarikan', NOW(), NOW())");
                $st->bind_param('isss', $id_mua, $kunci, $judul, $pesan);
                $st->execute();
            } catch (Throwable $e) { /* tabel notifikasi belum ada: abaikan */ }

            mua_toast(rp($jumlah) . " berhasil ditarik ke $tujuan. No. referensi $ref.", 'success');
            header('Location: keuangan.php');
            exit();
        }
    }
    mua_toast($error, 'error');
    $_SESSION['tarik_lama'] = ['tujuan' => $tujuan, 'nomor_tujuan' => $nomor, 'nama_penerima' => $nama, 'jumlah' => $jumlah];
    header('Location: keuangan.php?tarik=1');
    exit();
}

/* ================= DATA HALAMAN ================= */
$k = ringkasan_keuangan($koneksi, $id_mua);

$st = $koneksi->prepare("SELECT *, DATE_FORMAT(dibuat_pada, '%Y-%m-%d %H:%i') AS waktu FROM mua_penarikan WHERE id_mua = ? ORDER BY dibuat_pada DESC, id_penarikan DESC");
$st->bind_param('i', $id_mua);
$st->execute();
$riwayat = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$st = $koneksi->prepare("SELECT b.no_invoice, b.tanggal_booking, b.total_harga, s.nama_layanan, u.nama AS klien
                         FROM bookings b LEFT JOIN services s ON s.id_service = b.id_layanan LEFT JOIN users u ON u.id_user = b.id_pelanggan
                         WHERE b.id_mua = ? AND b.status = 'selesai' ORDER BY b.tanggal_booking DESC, b.id_booking DESC");
$st->bind_param('i', $id_mua);
$st->execute();
$pemasukan = $st->get_result()->fetch_all(MYSQLI_ASSOC);

// Isi awal form tarik: input terakhir yang gagal, atau rekening penarikan terakhir
$lama = $_SESSION['tarik_lama'] ?? null;
unset($_SESSION['tarik_lama']);
if (!$lama && $riwayat) {
    $lama = ['tujuan' => $riwayat[0]['tujuan'], 'nomor_tujuan' => $riwayat[0]['nomor_tujuan'], 'nama_penerima' => $riwayat[0]['nama_penerima'], 'jumlah' => ''];
}
$buka_modal = isset($_GET['tarik']);
$persen_dok = $k['sesi'] ? round($k['terdokumentasi'] / $k['sesi'] * 100) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keuangan - GlamLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/mua_keuangan.css">
    <link rel="stylesheet" href="../assets/css/mua_ui.css">
</head>
<body class="ku-body">

    <?php include '../navbar.php'; ?>

    <div class="ku-topbar">
        <div class="ku-topbar-inner">
            <a href="index.php" class="ku-btn-back" aria-label="Kembali ke Dashboard"><i class="fa-solid fa-arrow-left"></i></a>
            <h1>Keuangan</h1>
        </div>
    </div>

    <main class="ku-main">

        <!-- DOMPET -->
        <section class="ku-wallet">
            <div class="ku-wallet-left">
                <div class="ku-wallet-title">
                    <span class="ku-wallet-icon"><i class="fa-solid fa-wallet"></i></span>
                    DOMPET PENGHASILAN MITRA MUA
                    <span class="ku-pill">Instan Payout</span>
                </div>
                <span class="ku-label">Saldo Tersedia Siap Ditarik</span>
                <div class="ku-saldo" data-countup="<?php echo $k['saldo']; ?>" data-prefix="Rp "><?php echo rp($k['saldo']); ?></div>
                <p>Penghasilan dari pesanan klien yang telah terselesaikan. Dapat dicairkan ke rekening bank lokal (BCA, Mandiri, BRI, BNI) atau E-Wallet (GoPay, OVO, DANA) tanpa biaya admin.</p>
            </div>
            <div class="ku-wallet-box">
                <div class="ku-wallet-info">
                    <span>Total Dana Dicairkan: <b><?php echo rp($k['dicairkan']); ?></b></span>
                    <span title="Pembayaran klien untuk pesanan yang belum selesai. Masuk ke saldo setelah pesanan selesai.">
                        Saldo Escrow Aktif: <b><?php echo rp($k['escrow']); ?></b> <i class="fa-regular fa-circle-question"></i>
                    </span>
                    <small><i class="fa-solid fa-check"></i> Bebas biaya transfer platform (Gratis 100%)</small>
                </div>
                <button type="button" class="ku-btn-tarik" id="kuBukaTarik" <?php echo $k['saldo'] < MIN_TARIK ? 'disabled title="Minimal penarikan ' . rp(MIN_TARIK) . '"' : ''; ?>>
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Tarik Sekarang
                </button>
            </div>
        </section>

        <!-- STATISTIK -->
        <section class="ku-stats">
            <div class="ku-stat">
                <div class="ku-stat-head"><span>Acara Terselesaikan</span><i class="fa-regular fa-circle-check ku-c-green"></i></div>
                <b><span data-countup="<?php echo $k['sesi']; ?>"><?php echo $k['sesi']; ?></span> Sesi</b>
                <small class="ku-c-green"><?php echo $k['sesi'] ? $persen_dok . '% dengan foto dokumentasi' : 'Belum ada sesi selesai'; ?></small>
            </div>
            <div class="ku-stat">
                <div class="ku-stat-head"><span>Total Nilai Pesanan Selesai</span><span class="ku-chip-lunas">Lunas</span></div>
                <b class="ku-c-maroon" data-countup="<?php echo $k['pendapatan']; ?>" data-prefix="Rp "><?php echo rp($k['pendapatan']); ?></b>
                <small>Akumulasi seluruh sesi selesai</small>
            </div>
            <div class="ku-stat">
                <div class="ku-stat-head"><span>Kepuasan Rata-rata Klien</span><i class="fa-solid fa-star ku-c-gold"></i></div>
                <b><?php echo $k['jml_rating'] ? number_format($k['rating'], 1) : '-'; ?> <em>/5.0</em></b>
                <small class="ku-c-green"><?php echo !$k['jml_rating'] ? 'Belum ada rating' : ($k['rating'] >= 5 ? 'Rating sempurna dari klien' : 'Dari ' . $k['jml_rating'] . ' ulasan klien'); ?></small>
            </div>
            <div class="ku-stat">
                <div class="ku-stat-head"><span>Riwayat Penarikan Dana</span><i class="fa-solid fa-building-columns ku-c-maroon"></i></div>
                <b><span data-countup="<?php echo $k['kali_tarik']; ?>"><?php echo $k['kali_tarik']; ?></span> Kali</b>
                <small>Pencairan sukses ke rekening</small>
            </div>
        </section>

        <!-- RIWAYAT -->
        <section class="ku-table-card">
            <div class="ku-table-head">
                <div>
                    <h2>Riwayat Keuangan</h2>
                    <p>Dana yang ditarik ke rekening pribadi atau e-wallet, dan pemasukan dari pesanan selesai.</p>
                </div>
                <a href="keuangan.php?ekspor=csv" class="ku-link-ekspor"><i class="fa-solid fa-download"></i> Ekspor Mutasi</a>
            </div>
            <div class="ku-tabs" role="tablist">
                <button type="button" class="ku-tab active" data-ku-tab="tarik">Pencairan Dana (<?php echo count($riwayat); ?>)</button>
                <button type="button" class="ku-tab" data-ku-tab="masuk">Pemasukan (<?php echo count($pemasukan); ?>)</button>
            </div>

            <div class="ku-tab-panel" data-ku-panel="tarik">
                <?php if ($riwayat): ?>
                <div class="ku-table-wrap">
                    <table class="ku-table">
                        <thead><tr><th>No. Referensi</th><th>Tujuan Pencairan</th><th>No. Rekening / HP</th><th>Nama Penerima</th><th>Jumlah Penarikan</th><th>Waktu Proses</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($riwayat as $r): ?>
                            <tr>
                                <td><b><?php echo ke($r['no_referensi']); ?></b></td>
                                <td><i class="<?php echo $r['jenis'] === 'bank' ? 'fa-solid fa-building-columns ku-c-maroon' : 'fa-solid fa-mobile-screen'; ?>"></i> <?php echo ke($r['tujuan']); ?></td>
                                <td class="ku-muted"><?php echo ke($r['nomor_tujuan']); ?></td>
                                <td><?php echo ke($r['nama_penerima']); ?></td>
                                <td class="ku-out">- <?php echo rp($r['jumlah']); ?></td>
                                <td class="ku-muted"><?php echo ke($r['waktu']); ?></td>
                                <td>
                                    <?php if ($r['status'] === 'berhasil'): ?><span class="ku-status ok"><i class="fa-solid fa-check"></i> Berhasil Ditransfer</span>
                                    <?php elseif ($r['status'] === 'diproses'): ?><span class="ku-status wait"><i class="fa-regular fa-clock"></i> Diproses</span>
                                    <?php else: ?><span class="ku-status fail"><i class="fa-solid fa-xmark"></i> Gagal</span><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="ku-empty"><i class="fa-solid fa-money-bill-transfer"></i><p>Belum ada penarikan dana.</p></div>
                <?php endif; ?>
            </div>

            <div class="ku-tab-panel" data-ku-panel="masuk" hidden>
                <?php if ($pemasukan): ?>
                <div class="ku-table-wrap">
                    <table class="ku-table">
                        <thead><tr><th>No. Invoice</th><th>Klien</th><th>Layanan</th><th>Tanggal Acara</th><th>Jumlah Masuk</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($pemasukan as $p): ?>
                            <tr>
                                <td><b><?php echo ke($p['no_invoice']); ?></b></td>
                                <td><?php echo ke($p['klien'] ?? '-'); ?></td>
                                <td><?php echo ke($p['nama_layanan'] ?? '-'); ?></td>
                                <td class="ku-muted"><?php echo date('Y-m-d', strtotime($p['tanggal_booking'])); ?></td>
                                <td class="ku-in">+ <?php echo rp($p['total_harga']); ?></td>
                                <td><span class="ku-status ok"><i class="fa-solid fa-check"></i> Masuk Saldo</span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="ku-empty"><i class="fa-regular fa-calendar-check"></i><p>Belum ada pesanan selesai.</p></div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- MODAL TARIK DANA -->
    <div class="modal-overlay ku-modal" id="kuModalTarik" role="dialog" aria-modal="true" aria-labelledby="kuTarikJudul">
        <div class="modal-box ku-modal-box">
            <button type="button" class="ku-modal-close" data-close-modal aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
            <h3 id="kuTarikJudul">Tarik Saldo</h3>
            <p class="ku-muted">Saldo tersedia <b class="ku-c-maroon"><?php echo rp($k['saldo']); ?></b></p>

            <form method="POST" action="keuangan.php" id="kuFormTarik" data-saldo="<?php echo $k['saldo']; ?>" data-min="<?php echo MIN_TARIK; ?>">
                <span class="ku-field-label">Tujuan Pencairan</span>
                <div class="ku-tujuan">
                    <?php foreach (TUJUAN_TARIK as $t => $j): ?>
                        <label class="ku-tujuan-item">
                            <input type="radio" name="tujuan" value="<?php echo $t; ?>" data-jenis="<?php echo $j; ?>" required <?php echo ($lama['tujuan'] ?? '') === $t ? 'checked' : ''; ?>>
                            <span><i class="<?php echo $j === 'bank' ? 'fa-solid fa-building-columns' : 'fa-solid fa-mobile-screen'; ?>"></i> <?php echo $t; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <label class="ku-field">
                    <span class="ku-field-label" id="kuNomorLabel">No. Rekening</span>
                    <input type="text" name="nomor_tujuan" id="kuNomor" class="ku-input" inputmode="numeric" maxlength="24" required
                           value="<?php echo ke($lama['nomor_tujuan'] ?? ''); ?>" placeholder="Contoh: 8205128391">
                </label>
                <label class="ku-field">
                    <span class="ku-field-label">Nama Penerima</span>
                    <input type="text" name="nama_penerima" class="ku-input" maxlength="100" required
                           value="<?php echo ke($lama['nama_penerima'] ?? ''); ?>" placeholder="Sesuai nama di rekening / akun">
                </label>
                <label class="ku-field">
                    <span class="ku-field-label">Jumlah Penarikan <small>min. <?php echo rp(MIN_TARIK); ?></small></span>
                    <div class="ku-input-rp">
                        <span>Rp</span>
                        <input type="text" name="jumlah" id="kuJumlah" class="ku-input" inputmode="numeric" required
                               value="<?php echo !empty($lama['jumlah']) ? number_format((int)$lama['jumlah'], 0, ',', '.') : ''; ?>" placeholder="0">
                        <button type="button" class="ku-btn-semua" id="kuTarikSemua">Tarik Semua</button>
                    </div>
                </label>
                <p class="ku-sisa" id="kuSisa">Saldo setelah penarikan: <b><?php echo rp($k['saldo']); ?></b></p>

                <div class="ku-modal-actions">
                    <button type="button" class="ku-btn-ghost" data-close-modal>Batal</button>
                    <button type="submit" name="tarik_dana" class="ku-btn-primary" id="kuSubmitTarik" data-loading-text="Memproses...">Tarik Dana</button>
                </div>
                <small class="ku-catatan"><i class="fa-solid fa-circle-info"></i> Mode simulasi: belum terhubung ke bank / payment gateway sungguhan.</small>
            </form>
        </div>
    </div>

    <?php mua_toast_render(); ?>
    <script src="../assets/js/mua_ui.js"></script>
    <script>
    (function () {
        // ----- Tab riwayat -----
        document.querySelectorAll('[data-ku-tab]').forEach(function (b) {
            b.addEventListener('click', function () {
                document.querySelectorAll('[data-ku-tab]').forEach(function (x) { x.classList.toggle('active', x === b); });
                document.querySelectorAll('[data-ku-panel]').forEach(function (p) {
                    var tampil = p.dataset.kuPanel === b.dataset.kuTab;
                    p.hidden = !tampil;
                    if (tampil) GL.fadeIn(p);
                });
            });
        });

        // ----- Modal tarik dana -----
        var modal = document.getElementById('kuModalTarik');
        var form = document.getElementById('kuFormTarik');
        var saldo = +form.dataset.saldo, min = +form.dataset.min;
        var jumlah = document.getElementById('kuJumlah'), nomor = document.getElementById('kuNomor');
        var fmt = function (n) { return n.toLocaleString('id-ID'); };
        var angka = function () { return +(jumlah.value.replace(/\D/g, '') || 0); };

        var tombol = document.getElementById('kuBukaTarik');
        if (tombol) tombol.addEventListener('click', function () { GL.openModal(modal); });
        modal.addEventListener('click', function (e) {
            if (e.target === modal || e.target.closest('[data-close-modal]')) GL.closeModal(modal);
        });

        function updateJenis() {
            var r = form.querySelector('input[name="tujuan"]:checked');
            var ew = r && r.dataset.jenis === 'ewallet';
            document.getElementById('kuNomorLabel').textContent = ew ? 'No. HP ' + r.value : 'No. Rekening';
            nomor.placeholder = ew ? 'Contoh: 081234567890' : 'Contoh: 8205128391';
        }
        form.addEventListener('change', function (e) { if (e.target.name === 'tujuan') updateJenis(); });
        updateJenis();

        // Format ribuan saat mengetik + hitung sisa saldo
        function updateJumlah() {
            var n = angka();
            jumlah.value = n ? fmt(n) : '';
            var sisa = saldo - n;
            var p = document.getElementById('kuSisa');
            p.innerHTML = '';
            p.classList.toggle('error', n > 0 && (n < min || sisa < 0));
            p.textContent = n > saldo ? 'Melebihi saldo tersedia (Rp ' + fmt(saldo) + ').'
                         : (n > 0 && n < min) ? 'Minimal penarikan Rp ' + fmt(min) + '.'
                         : 'Saldo setelah penarikan: Rp ' + fmt(Math.max(0, sisa));
        }
        jumlah.addEventListener('input', updateJumlah);
        nomor.addEventListener('input', function () { nomor.value = nomor.value.replace(/[^\d]/g, ''); });
        document.getElementById('kuTarikSemua').addEventListener('click', function () { jumlah.value = String(saldo); updateJumlah(); });
        updateJumlah();

        // Validasi sebelum kirim (server tetap memeriksa ulang)
        form.addEventListener('submit', function (e) {
            var n = angka();
            if (n < min || n > saldo) {
                e.preventDefault();
                e.stopImmediatePropagation();
                GL.toast(n > saldo ? 'Jumlah melebihi saldo tersedia.' : 'Minimal penarikan Rp ' + fmt(min) + '.', 'error');
                jumlah.focus();
            }
        }, true);

        <?php if ($buka_modal && $k['saldo'] >= MIN_TARIK): ?>GL.openModal(modal);<?php endif; ?>
    })();
    </script>
</body>
</html>