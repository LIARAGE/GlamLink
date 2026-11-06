<?php
/**
 * Pembantu halaman History (klien/history.php) dan Detail Booking (klien/detail_booking.php).
 * Panggil SETELAH Includes/klien.php. Semua aksi klien (batal, konfirmasi selesai, review) diproses di
 * aksi_booking() supaya aturannya satu tempat dan tidak berbeda antara dua halaman.
 */

/** Tab History: kunci => [label, status di database]. Urutan = urutan tab di layar */
const TAB_RIWAYAT = ['pending' => ['Pending', 'menunggu'], 'upcoming' => ['Upcoming', 'dikonfirmasi'], 'completed' => ['Completed', 'selesai'], 'cancelled' => ['Cancelled', 'ditolak']];
/** Nama tab lama (?tab=menunggu dst.) tetap diterima agar link lama tidak rusak */
const TAB_LAMA = ['menunggu' => 'pending', 'dikonfirmasi' => 'upcoming', 'selesai' => 'completed', 'ditolak' => 'cancelled'];

function tab_valid($t) { $t = TAB_LAMA[$t] ?? $t; return isset(TAB_RIWAYAT[$t]) ? $t : 'pending'; }
function tab_dari_status($st) { foreach (TAB_RIWAYAT as $k => $v) { if ($v[1] === $st) return $k; } return 'pending'; }

/** '2026-11-21' -> '21 November 2026' */
function tgl_lengkap($ymd) { $t = strtotime((string)$ymd); return date('j', $t) . ' ' . NAMA_BULAN[(int)date('n', $t)] . ' ' . date('Y', $t); }
/** '08:00:00' -> '08:00 - 10:00' bila tepat awal slot; data lama di luar slot ditampilkan apa adanya ('14:30') */
function jam_tampil($waktu) {
    $j = substr((string)$waktu, 0, 5); $i = slot_index($j);
    return ($i >= 0 && SLOT_JAM[$i][0] === $j) ? SLOT_JAM[$i][0] . ' - ' . SLOT_JAM[$i][1] : $j;
}

/** Label badge, kelas warna, dan pesan singkat untuk satu booking */
function info_status(array $b) {
    $st = $b['status'];
    if ($st === 'menunggu') return ['Menunggu Konfirmasi', 's-menunggu', 'Booking telah dibuat, menunggu konfirmasi dari MUA'];
    if ($st === 'dikonfirmasi') {
        return (!empty($b['is_mua_done']) && empty($b['is_client_done']))
            ? ['Menunggu Konfirmasi Anda', 's-menunggu-klien', 'MUA menandai sesi selesai. Mohon konfirmasi pesanan Anda.']
            : ['Terkonfirmasi', 's-dikonfirmasi', 'Booking dikonfirmasi MUA. Sampai jumpa di hari acara!'];
    }
    if ($st === 'selesai') return ['Selesai', 's-selesai', ''];
    $klien = ($b['dibatalkan_oleh'] ?? '') === 'klien';      // data lama (kosong) dianggap ditolak MUA
    return [$klien ? 'Dibatalkan' : 'Ditolak MUA', 's-ditolak', $klien ? 'Anda membatalkan booking ini.' : 'Booking dibatalkan oleh MUA.'];
}

/** Pesan notifikasi setelah aksi: kode => [jenis alert, teks] */
function pesan_riwayat($kode) {
    $p = [
        'batal'   => ['ok', 'Booking berhasil dibatalkan. Jadwal dilepas kembali dan pembayaran Anda dikembalikan.'],
        'selesai' => ['ok', 'Pesanan dikonfirmasi selesai. Terima kasih! Silakan beri review untuk MUA.'],
        'rating'  => ['ok', 'Review Anda tersimpan dan ikut menghitung rating MUA.'],
        'err'     => ['err', 'Aksi tidak dapat diproses. Status booking mungkin sudah berubah, silakan muat ulang halaman.'],
    ];
    return $p[$kode] ?? null;
}

/**
 * Memproses aksi POST klien lalu redirect (PRG). Aksi: batal | selesai | rating.
 * Setiap aksi: wajib token CSRF, booking harus milik klien, status harus sesuai, dan hasilnya diperiksa ulang
 * dari database (bukan sekadar percaya UPDATE berhasil) supaya balapan dengan MUA tidak menghasilkan pesan palsu.
 */
function aksi_booking($id_klien) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $aksi = is_string($_POST['aksi'] ?? null) ? $_POST['aksi'] : '';
    $idb  = (int)(is_string($_POST['id_booking'] ?? null) ? $_POST['id_booking'] : 0);
    $dari = ($_POST['dari'] ?? '') === 'detail';
    $hasil = 'err';
    $b = csrf_ok() ? (q('SELECT * FROM bookings WHERE id_booking = ? AND id_pelanggan = ?', [$idb, $id_klien])[0] ?? null) : null;

    if ($b && $aksi === 'batal' && $b['status'] === 'menunggu') {
        // Klien hanya boleh membatalkan selama MUA belum mengonfirmasi
        q("UPDATE bookings SET status = 'ditolak', dibatalkan_oleh = 'klien', dibatalkan_pada = NOW() WHERE id_booking = ? AND id_pelanggan = ? AND status = 'menunggu'", [$idb, $id_klien]);
        $c = q('SELECT status, dibatalkan_oleh FROM bookings WHERE id_booking = ?', [$idb])[0] ?? [];
        if (($c['status'] ?? '') === 'ditolak' && ($c['dibatalkan_oleh'] ?? '') === 'klien') $hasil = 'batal';
    } elseif ($b && $aksi === 'selesai' && $b['status'] === 'dikonfirmasi') {
        // Hanya SETELAH MUA menandai selesai; kedua pihak sudah konfirmasi -> status 'selesai' (sama dengan mua/booking.php)
        q("UPDATE bookings SET is_client_done = 1, status = 'selesai' WHERE id_booking = ? AND id_pelanggan = ? AND status = 'dikonfirmasi' AND is_mua_done = 1 AND is_client_done = 0", [$idb, $id_klien]);
        if ((q('SELECT status FROM bookings WHERE id_booking = ?', [$idb])[0]['status'] ?? '') === 'selesai') $hasil = 'selesai';
    } elseif ($b && $aksi === 'rating' && $b['status'] === 'selesai') {
        $r  = (int)(is_string($_POST['rating'] ?? null) ? $_POST['rating'] : 0);
        $ul = trim(is_string($_POST['ulasan'] ?? null) ? $_POST['ulasan'] : '');
        if ($r >= 1 && $r <= 5 && mb_strlen($ul) <= 1000) {
            q("UPDATE bookings SET rating = ?, ulasan = ? WHERE id_booking = ? AND id_pelanggan = ? AND status = 'selesai'", [$r, $ul, $idb, $id_klien]);
            if ((int)(q('SELECT rating FROM bookings WHERE id_booking = ?', [$idb])[0]['rating'] ?? 0) === $r) $hasil = 'rating';
        }
    }

    if ($dari && $b) { header('Location: detail_booking.php?id=' . (int)$b['id_booking'] . '&msg=' . $hasil); exit(); }
    $tab = ['batal' => 'cancelled', 'selesai' => 'completed', 'rating' => 'completed'][$hasil] ?? tab_dari_status($b['status'] ?? 'menunggu');
    header('Location: history.php?tab=' . $tab . '&msg=' . $hasil); exit();
}

/** Pop-up beri/ubah review. Tombol pemicu: <button class="js-rate" data-id data-mua data-rating data-ulasan>. Panggil sekali per halaman. */
function dialog_rating($dari = '') { ?>
<dialog class="rate-dlg" id="dlg-rate">
    <form method="POST">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="aksi" value="rating"><input type="hidden" name="id_booking" id="rate-id"><input type="hidden" name="dari" value="<?php echo e($dari); ?>">
        <h3>Beri Review</h3><p class="muted" id="rate-mua" style="margin:0"></p>
        <div class="star-input">
            <?php for ($i = 5; $i >= 1; $i--) echo '<input type="radio" name="rating" id="st' . $i . '" value="' . $i . '" required><label for="st' . $i . '" title="' . $i . ' bintang"><i class="fa-solid fa-star"></i></label>'; ?>
        </div>
        <textarea name="ulasan" id="rate-ulasan" rows="4" maxlength="1000" placeholder="Ceritakan pengalaman Anda (opsional)"></textarea>
        <div class="dlg-actions"><button type="button" class="kb-outline" onclick="document.getElementById('dlg-rate').close()">Batal</button><button type="submit" class="kb-fill">Kirim</button></div>
    </form>
</dialog>
<script>
    (function () {
        var dlg = document.getElementById('dlg-rate');
        document.querySelectorAll('.js-rate').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('rate-id').value = btn.dataset.id;
                document.getElementById('rate-mua').textContent = btn.dataset.mua;
                document.getElementById('rate-ulasan').value = btn.dataset.ulasan;
                document.querySelectorAll('#dlg-rate .star-input input').forEach(function (r) { r.checked = (r.value === btn.dataset.rating); });
                dlg.showModal();
            });
        });
    })();
</script>
<?php }
