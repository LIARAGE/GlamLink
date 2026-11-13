<?php
/**
 * BUAT / EDIT POSTINGAN PORTFOLIO
 *   portfolio_tambah.php        -> buat postingan baru
 *   portfolio_tambah.php?id=12  -> edit postingan (foto boleh tidak diganti)
 * Foto bisa dipilih dari:
 *   1. Koleksi foto dokumentasi pesanan yang sudah diunggah MUA (bookings.foto_dokumentasi), atau
 *   2. Unggah foto baru dari perangkat.
 * Foto dari koleksi DISALIN menjadi file baru, sehingga menghapus portfolio
 * tidak ikut menghapus foto dokumentasi pesanan.
 */
session_start();
require_once '../includes/auth_mua.php';   // koneksi + cek login MUA + $id_mua
require_once '../includes/upload.php';     // simpan_gambar(), pesan_upload()
require_once '../includes/mua_ui.php';     // toast
require_once '../includes/mua_profil.php'; // helper profil

// --- Koleksi foto dokumentasi milik MUA ini (maks. 12 terbaru) ---
$st = $koneksi->prepare("
    SELECT b.foto_dokumentasi, b.tanggal_booking, s.nama_layanan
    FROM bookings b
    LEFT JOIN services s ON s.id_service = b.id_layanan
    WHERE b.id_mua = ? AND b.foto_dokumentasi IS NOT NULL AND b.foto_dokumentasi <> ''
    ORDER BY b.tanggal_booking DESC, b.id_booking DESC");
$st->bind_param('i', $id_mua);
$st->execute();
$koleksi = [];
foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $k) {
    $url = mp_url_foto($k['foto_dokumentasi']);
    if ($url && !isset($koleksi[$k['foto_dokumentasi']])) {
        $koleksi[$k['foto_dokumentasi']] = [
            'url'   => $url,
            'label' => ($k['nama_layanan'] ?? 'Pesanan') . ' · ' . date('d/m/Y', strtotime($k['tanggal_booking'])),
        ];
    }
    if (count($koleksi) >= 12) break;
}

$f = ['judul' => '', 'tag' => '', 'deskripsi' => '', 'koleksi' => ''];

// --- Mode edit: ambil portfolio milik MUA ini ---
$id_edit = (int)($_POST['id_portfolio'] ?? $_GET['id'] ?? 0);
$lama = null;
if ($id_edit > 0) {
    $st = $koneksi->prepare("SELECT * FROM portfolios WHERE id_portfolio = ? AND id_mua = ?");
    $st->bind_param('ii', $id_edit, $id_mua);
    $st->execute();
    $lama = $st->get_result()->fetch_assoc();
    if (!$lama) {
        mua_toast('Portfolio tidak ditemukan.', 'error');
        header('Location: profil.php?tab=portfolio');
        exit();
    }
    $f['judul'] = (string)$lama['judul'];
    $f['tag'] = implode(',', mp_pecah_tag($lama['tag'] ?? ''));
    $f['deskripsi'] = (string)$lama['deskripsi'];
}
$mode_edit = $lama !== null;
$url_foto_lama = $mode_edit ? mp_url_foto($lama['foto']) : null;

if (isset($_POST['publikasi'])) {
    $f['judul']     = trim($_POST['judul'] ?? '');
    $f['tag']       = mp_gabung_tag(explode(',', $_POST['tag'] ?? ''), 6, 30);
    $f['deskripsi'] = trim($_POST['deskripsi'] ?? '');
    $f['koleksi']   = basename(trim($_POST['koleksi'] ?? ''));
    $ada_upload     = ($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    $error = [];
    if (mb_strlen($f['judul']) < 3 || mb_strlen($f['judul']) > 100) $error[] = 'Judul karya wajib diisi (3-100 karakter).';
    if (strlen($f['tag']) > 150)            $error[] = 'Tagar terlalu panjang.';
    if (mb_strlen($f['deskripsi']) > 500)   $error[] = 'Deskripsi maksimal 500 karakter.';
    if (!$ada_upload && $f['koleksi'] === '' && !$mode_edit) $error[] = 'Pilih foto dari koleksi atau unggah foto baru.';
    if (!$ada_upload && $f['koleksi'] !== '' && !isset($koleksi[$f['koleksi']])) $error[] = 'Foto koleksi tidak ditemukan.';

    $nama_foto = null;
    if (!$error && ($ada_upload || $f['koleksi'] !== '')) {   // saat edit, foto boleh tidak diganti
        if ($ada_upload) {
            $alasan = null;
            $nama_foto = simpan_gambar($_FILES['foto'], 'porto', 10, $alasan);
            if (!$nama_foto) $error[] = 'Foto: ' . strip_tags(pesan_upload($alasan));
        } else {
            // Salin foto dokumentasi menjadi file portfolio baru
            $asal = dirname(__DIR__) . '/assets/img/' . $f['koleksi'];
            $info = @getimagesize($asal);
            $ext  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$info['mime'] ?? ''] ?? null;
            if (!$ext) {
                $error[] = 'Foto koleksi tidak valid.';
            } else {
                $nama_foto = 'porto_' . bin2hex(random_bytes(8)) . '.' . $ext;
                if (!@copy($asal, dirname(__DIR__) . '/assets/img/' . $nama_foto)) {
                    $nama_foto = null;
                    $error[] = 'Foto gagal disalin. Cek izin folder assets/img.';
                }
            }
        }
    }

    if (!$error) {
        $tag  = $f['tag'] !== '' ? $f['tag'] : null;
        $desk = $f['deskripsi'] !== '' ? $f['deskripsi'] : null;
        if ($mode_edit) {
            $foto_simpan = $nama_foto ?? $lama['foto'];
            $st = $koneksi->prepare("UPDATE portfolios SET foto = ?, judul = ?, deskripsi = ?, tag = ? WHERE id_portfolio = ? AND id_mua = ?");
            $st->bind_param('ssssii', $foto_simpan, $f['judul'], $desk, $tag, $id_edit, $id_mua);
        } else {
            $st = $koneksi->prepare("INSERT INTO portfolios (id_mua, foto, judul, deskripsi, tag) VALUES (?, ?, ?, ?, ?)");
            $st->bind_param('issss', $id_mua, $nama_foto, $f['judul'], $desk, $tag);
        }
        if ($st->execute()) {
            if ($mode_edit && $nama_foto) mp_hapus_foto($lama['foto']);   // foto lama diganti -> hapus filenya
            mua_toast($mode_edit ? 'Perubahan portfolio berhasil disimpan.' : 'Karya berhasil dipublikasikan ke portfolio.', 'success');
            header('Location: profil.php?tab=portfolio' . ($mode_edit ? '&lihat=' . $id_edit : ''));
            exit();
        }
        mp_hapus_foto($nama_foto);
        $error[] = 'Gagal menyimpan ke database. Silakan coba lagi.';
    }

    foreach ($error as $e) mua_toast($e, 'error');
}

$tags_awal = mp_pecah_tag($f['tag']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $mode_edit ? 'Edit Postingan' : 'Buat Postingan'; ?> Portfolio - GlamLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/mua_profil.css">
    <link rel="stylesheet" href="../assets/css/mua_ui.css">
</head>
<body class="pf-body">

    <?php include '../navbar.php'; ?>

    <div class="pf-topbar">
        <div class="pf-topbar-inner">
            <a href="profil.php?tab=portfolio" class="pf-btn-back" aria-label="Kembali ke portfolio"><i class="fa-solid fa-arrow-left"></i></a>
            <h2><?php echo $mode_edit ? 'Edit Postingan' : 'Buat Postingan Baru'; ?></h2>
        </div>
    </div>

    <main class="pf-edit-main">
        <div class="pf-post-grid">

            <!-- FORM -->
            <form method="POST" enctype="multipart/form-data" action="portfolio_tambah.php<?php echo $mode_edit ? '?id=' . $id_edit : ''; ?>" class="pf-card pf-post-form" id="pfFormPorto" <?php echo $mode_edit ? 'data-edit' : ''; ?>>
                <?php if ($mode_edit): ?><input type="hidden" name="id_portfolio" value="<?php echo $id_edit; ?>"><?php endif; ?>
                <label class="pf-field">
                    <span>Judul Karya Riasan <b class="pf-req">*</b></span>
                    <input type="text" name="judul" id="pfJudul" class="pf-input" maxlength="100" required
                           value="<?php echo mp_e($f['judul']); ?>" placeholder="Contoh: Modern Royal Siger · Velvet Glowing Bride">
                </label>

                <div class="pf-field pf-foto-section">
                    <span>Foto Dokumentasi Karya <?php echo $mode_edit ? '<small style="text-transform:none;letter-spacing:0;">kosongkan jika tidak diganti</small>' : '<b class="pf-req">*</b>'; ?></span>
                    <?php if ($koleksi): ?>
                        <small class="pf-help" style="margin:0 0 10px;">Pilih dari koleksi foto dokumentasi pesananmu, atau unggah foto baru:</small>
                        <div class="pf-koleksi">
                            <?php foreach ($koleksi as $file => $k): ?>
                                <button type="button" class="pf-koleksi-item <?php echo $f['koleksi'] === $file ? 'selected' : ''; ?>"
                                        data-file="<?php echo mp_e($file); ?>" data-url="<?php echo mp_e($k['url']); ?>" data-label="<?php echo mp_e($k['label']); ?>"
                                        title="<?php echo mp_e($k['label']); ?>" aria-pressed="<?php echo $f['koleksi'] === $file ? 'true' : 'false'; ?>">
                                    <img src="<?php echo mp_e($k['url']); ?>" alt="<?php echo mp_e($k['label']); ?>" loading="lazy">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <small class="pf-help" style="margin:0 0 10px;">Unggah foto hasil riasanmu. Foto dokumentasi pesanan yang sudah selesai juga akan muncul di sini sebagai pilihan.</small>
                    <?php endif; ?>
                    <input type="hidden" name="koleksi" id="pfKoleksi" value="<?php echo mp_e($f['koleksi']); ?>">
                    <div class="pf-upload-row">
                        <label class="pf-btn-ghost pf-btn-sm pf-upload-btn">
                            <i class="fa-solid fa-arrow-up-from-bracket"></i> Unggah Foto
                            <input type="file" name="foto" id="pfFotoFile" accept="image/jpeg,image/png,image/webp" hidden>
                        </label>
                        <div class="pf-input pf-upload-name" id="pfFotoNama">
                            <?php echo $f['koleksi'] !== '' && isset($koleksi[$f['koleksi']]) ? 'Dari dokumentasi: ' . mp_e($koleksi[$f['koleksi']]['label']) : ($mode_edit ? 'Memakai foto saat ini' : 'Belum ada foto dipilih'); ?>
                        </div>
                    </div>
                    <small class="pf-help">JPG, PNG, atau WEBP. Maksimal 10 MB.</small>
                </div>

                <div class="pf-field">
                    <span>Tagar / Hashtag <small style="text-transform:none;letter-spacing:0;">maks. 6, Enter untuk menambah</small></span>
                    <input type="hidden" name="tag" id="pfTagHidden" value="<?php echo mp_e(implode(',', $tags_awal)); ?>">
                    <div class="pf-chip-input" data-target="pfTagHidden" data-max="6" data-maxlen="30" data-list="pfListTag" data-placeholder="#BridalLook"></div>
                    <datalist id="pfListTag">
                        <?php foreach (['Bridal', 'SoftGlam', 'Wisuda', 'Natural', 'KoreanDewy', 'Hairdo', 'Adat', 'Hijab', 'Photoshoot', 'FlawlessSkin'] as $t): ?><option value="<?php echo $t; ?>"><?php endforeach; ?>
                    </datalist>
                </div>

                <label class="pf-field">
                    <span>Deskripsi Singkat <small style="text-transform:none;letter-spacing:0;" id="pfDeskCount">0/500</small></span>
                    <textarea name="deskripsi" class="pf-textarea" rows="3" maxlength="500" data-counter="#pfDeskCount" style="min-height:90px;"
                              placeholder="Opsional. Contoh: Riasan akad dengan siger Sunda, tahan 12 jam."><?php echo mp_e($f['deskripsi']); ?></textarea>
                </label>

                <div class="pf-form-actions">
                    <a href="profil.php?tab=portfolio" class="pf-btn-ghost pf-btn-sm">Batal</a>
                    <?php if ($mode_edit): ?>
                        <button type="submit" name="publikasi" class="pf-btn-filled pf-btn-sm" data-loading-text="Menyimpan...">
                            <i class="fa-regular fa-floppy-disk"></i> Simpan Perubahan
                        </button>
                    <?php else: ?>
                        <button type="submit" name="publikasi" class="pf-btn-filled pf-btn-sm" data-loading-text="Mempublikasikan...">
                            <i class="fa-regular fa-paper-plane"></i> Publikasikan ke Galeri Feeds
                        </button>
                    <?php endif; ?>
                </div>
            </form>

            <!-- PRATINJAU -->
            <aside>
                <p class="pf-preview-label"><i class="fa-regular fa-eye"></i> Pratinjau Kartu Portofolio</p>
                <div class="pf-preview-card">
                    <div class="pf-preview-img">
                        <?php $url_awal = ($f['koleksi'] !== '' && isset($koleksi[$f['koleksi']])) ? $koleksi[$f['koleksi']]['url'] : ($url_foto_lama ?? ''); ?>
                        <img id="pfPrevImg" data-awal="<?php echo mp_e($url_foto_lama ?? ''); ?>" src="<?php echo mp_e($url_awal); ?>" alt="Pratinjau" <?php echo $url_awal ? '' : 'hidden'; ?>>
                        <div class="pf-preview-empty" id="pfPrevKosong" <?php echo $url_awal ? 'hidden' : ''; ?>>
                            <i class="fa-regular fa-image"></i> Foto akan tampil di sini
                        </div>
                        <span class="pf-preview-badge" id="pfPrevBadge">Portfolio</span>
                    </div>
                    <div class="pf-preview-body">
                        <b id="pfPrevJudul">Judul Look Portofolio Baru</b>
                        <div class="pf-preview-tags" id="pfPrevTags"></div>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <?php mua_toast_render(); ?>
    <script src="../assets/js/mua_ui.js"></script>
    <script src="../assets/js/mua_profil.js"></script>
</body>
</html>