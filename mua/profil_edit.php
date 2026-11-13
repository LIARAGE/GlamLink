<?php
/**
 * EDIT PROFIL MUA
 * Mengubah: foto sampul, foto profil, nama brand, tagline, bio, spesialisasi,
 *           alamat, WhatsApp, email, dan area jangkauan.
 * Portfolio TIDAK diedit di sini, tapi langsung di tab Portfolio (profil.php?tab=portfolio).
 */
session_start();
require_once '../includes/auth_mua.php';   // koneksi + cek login MUA + $id_mua
require_once '../includes/upload.php';     // simpan_gambar(), pesan_upload()
require_once '../includes/mua_ui.php';     // toast
require_once '../includes/mua_profil.php'; // helper profil

$profil = mp_ambil_profil($koneksi, $id_mua);

// Nilai form (diisi dari database, atau dari input terakhir jika ada error)
$f = [
    'nama_brand'    => $profil['nama_brand'] ?? '',
    'tagline'       => $profil['tagline'] ?? '',
    'bio'           => $profil['bio'] ?? '',
    'spesialisasi'  => implode(',', $profil['spesial_list']),
    'alamat'        => $profil['alamat'] ?? '',
    'nomor_telepon' => $profil['nomor_telepon'] ?? '',
    'email'         => $profil['email'] ?? '',
    // Hanya wilayah yang ada di daftar (nilai lama di luar daftar tidak ikut tersimpan)
    'area'          => implode(',', array_intersect($profil['area_list'], MUA_WILAYAH)),
];

if (isset($_POST['simpan_profil'])) {
    $f['nama_brand']    = trim($_POST['nama_brand'] ?? '');
    $f['tagline']       = trim($_POST['tagline'] ?? '');
    $f['bio']           = trim(str_replace("\r\n", "\n", $_POST['bio'] ?? ''));
    $f['spesialisasi']  = mp_gabung_tag(explode(',', $_POST['spesialisasi'] ?? ''), 8, 30);
    $f['alamat']        = trim($_POST['alamat'] ?? '');
    $f['nomor_telepon'] = trim($_POST['nomor_telepon'] ?? '');
    $f['email']         = trim($_POST['email'] ?? '');
    // Area jangkauan hanya boleh dari daftar wilayah (supaya cocok dengan filter pencarian klien)
    $area = array_values(array_intersect(mp_pecah_tag($_POST['area'] ?? ''), MUA_WILAYAH));
    $f['area']          = implode(', ', $area);

    // --- Validasi ---
    $error = [];
    $len = fn($s) => mb_strlen($s);
    if ($len($f['nama_brand']) < 2 || $len($f['nama_brand']) > 150) $error[] = 'Nama brand wajib diisi (2-150 karakter).';
    if ($len($f['tagline']) > 150)  $error[] = 'Tagline maksimal 150 karakter.';
    if ($len($f['bio']) > 2000)     $error[] = 'Bio maksimal 2000 karakter.';
    if ($len($f['alamat']) > 255)   $error[] = 'Alamat maksimal 255 karakter.';
    if ($f['nomor_telepon'] !== '') {
        $digit = preg_replace('/\D/', '', $f['nomor_telepon']);
        if (!preg_match('/^[0-9+\-\s()]{8,20}$/', $f['nomor_telepon']) || strlen($digit) < 9 || strlen($digit) > 15) {
            $error[] = 'Nomor WhatsApp tidak valid (9-15 digit).';
        }
    }
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL) || $len($f['email']) > 100) {
        $error[] = 'Format email tidak valid.';
    } else {
        $st = $koneksi->prepare("SELECT 1 FROM users WHERE email = ? AND id_user <> ?");
        $st->bind_param('si', $f['email'], $id_mua);
        $st->execute();
        if ($st->get_result()->num_rows > 0) $error[] = 'Email sudah dipakai akun lain.';
    }
    if (!$area) $error[] = 'Pilih minimal satu area jangkauan.';
    if (strlen($f['area']) > 150) $error[] = 'Area jangkauan terlalu banyak.';

    // Cek file dulu (tanpa menyimpan) supaya pesan error muncul sebelum data disimpan
    $ada_foto_profil = ($_FILES['foto_profil']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    $ada_foto_sampul = ($_FILES['foto_sampul']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    if (!$error) {
        $foto_profil_baru = null;
        $foto_sampul_baru = null;
        if ($ada_foto_profil) {
            $alasan = null;
            $foto_profil_baru = simpan_gambar($_FILES['foto_profil'], 'profil', 10, $alasan);
            if (!$foto_profil_baru) $error[] = 'Foto profil: ' . strip_tags(pesan_upload($alasan));
        }
        if (!$error && $ada_foto_sampul) {
            $alasan = null;
            $foto_sampul_baru = simpan_gambar($_FILES['foto_sampul'], 'sampul', 10, $alasan);
            if (!$foto_sampul_baru) $error[] = 'Foto sampul: ' . strip_tags(pesan_upload($alasan));
        }
        if ($error) {                     // salah satu upload gagal: buang file yang sempat tersimpan
            mp_hapus_foto($foto_profil_baru);
            mp_hapus_foto($foto_sampul_baru);
        }
    }

    if (!$error) {
        $telp   = $f['nomor_telepon'] !== '' ? $f['nomor_telepon'] : null;
        $foto_u = $foto_profil_baru ?? $profil['foto_profil'];

        $st = $koneksi->prepare("UPDATE users SET nama_brand = ?, bio = ?, lokasi = ?, nomor_telepon = ?, email = ?, foto_profil = ? WHERE id_user = ?");
        $st->bind_param('ssssssi', $f['nama_brand'], $f['bio'], $f['area'], $telp, $f['email'], $foto_u, $id_mua);
        $st->execute();

        $tagline = $f['tagline'] !== '' ? $f['tagline'] : null;
        $alamat  = $f['alamat'] !== '' ? $f['alamat'] : null;
        $spes    = $f['spesialisasi'] !== '' ? $f['spesialisasi'] : null;
        $sampul  = $foto_sampul_baru ?? ($profil['foto_sampul'] ?? null);
        $st = $koneksi->prepare("INSERT INTO mua_profil (id_mua, tagline, alamat, spesialisasi, foto_sampul) VALUES (?, ?, ?, ?, ?)
                                 ON DUPLICATE KEY UPDATE tagline = VALUES(tagline), alamat = VALUES(alamat),
                                 spesialisasi = VALUES(spesialisasi), foto_sampul = VALUES(foto_sampul)");
        $st->bind_param('issss', $id_mua, $tagline, $alamat, $spes, $sampul);
        $st->execute();

        // Hapus file lama setelah data baru tersimpan
        if ($foto_profil_baru) mp_hapus_foto($profil['foto_profil']);
        if ($foto_sampul_baru) mp_hapus_foto($profil['foto_sampul'] ?? '');

        mua_toast('Profil berhasil disimpan.', 'success');
        header('Location: profil.php?tab=tentang');
        exit();
    }

    foreach ($error as $e) mua_toast($e, 'error');
}

$foto_profil = mp_url_foto($profil['foto_profil'] ?? '');
$foto_sampul = mp_url_foto($profil['foto_sampul'] ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - GlamLink</title>
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
            <a href="profil.php" class="pf-btn-back" aria-label="Kembali ke profil"><i class="fa-solid fa-arrow-left"></i></a>
            <h2>Edit Profil</h2>
        </div>
    </div>

    <main class="pf-edit-main">
        <form method="POST" enctype="multipart/form-data" action="profil_edit.php">

            <!-- SAMPUL, FOTO PROFIL, NAMA & TAGLINE -->
            <section class="pf-edit-cover-card">
                <div class="pf-edit-cover <?php echo $foto_sampul ? '' : 'pf-cover--default'; ?>" id="pfCoverPrev"
                     <?php if ($foto_sampul): ?>style="background-image: url('<?php echo mp_e($foto_sampul); ?>');"<?php endif; ?>>
                    <label class="pf-cover-btn">
                        <i class="fa-solid fa-image"></i> Ganti Sampul
                        <input type="file" name="foto_sampul" accept="image/jpeg,image/png,image/webp" hidden data-preview-target="#pfCoverPrev">
                    </label>
                </div>
                <div class="pf-edit-identity">
                    <div class="pf-edit-avatar">
                        <img src="<?php echo mp_e($foto_profil ?? ''); ?>" alt="Foto profil" id="pfAvatarPrev" <?php echo $foto_profil ? '' : 'hidden'; ?>>
                        <div class="pf-avatar-ph" <?php echo $foto_profil ? 'hidden' : ''; ?>><i class="fa-solid fa-user"></i></div>
                        <label class="pf-avatar-btn" title="Ganti foto profil">
                            <i class="fa-solid fa-camera"></i>
                            <input type="file" name="foto_profil" accept="image/jpeg,image/png,image/webp" hidden data-preview-target="#pfAvatarPrev">
                        </label>
                    </div>
                    <div class="pf-edit-names">
                        <input type="text" name="nama_brand" class="pf-input-title" maxlength="150" required
                               value="<?php echo mp_e($f['nama_brand']); ?>" placeholder="Nama Brand / Studio" aria-label="Nama brand">
                        <input type="text" name="tagline" class="pf-input-sub" maxlength="150"
                               value="<?php echo mp_e($f['tagline']); ?>" placeholder="Tulis tagline singkat, contoh: Professional makeup artist specializing in bridal looks." aria-label="Tagline">
                    </div>
                </div>
            </section>

            <div class="pf-edit-grid">
                <!-- BIO & SPESIALISASI -->
                <section class="pf-card">
                    <h3 class="pf-card-title">Filosofi &amp; Bio Seniman Rias</h3>
                    <label class="pf-field">
                        <span>Bio <small id="pfBioCount">0/2000</small></span>
                        <textarea name="bio" class="pf-textarea" rows="6" maxlength="2000" data-counter="#pfBioCount"
                                  placeholder="Ceritakan pengalaman, keahlian, dan gaya riasmu."><?php echo mp_e($f['bio']); ?></textarea>
                    </label>
                    <div class="pf-divider"></div>
                    <div class="pf-field">
                        <span>Spesialisasi <small>maks. 8</small></span>
                        <input type="hidden" name="spesialisasi" id="pfSpesial" value="<?php echo mp_e($f['spesialisasi']); ?>">
                        <div class="pf-chip-input" data-target="pfSpesial" data-max="8" data-maxlen="30" data-list="pfListSpesial" data-placeholder="Contoh: Korean Dewy"></div>
                        <datalist id="pfListSpesial">
                            <?php foreach (MUA_SPESIALISASI as $s): ?><option value="<?php echo mp_e($s); ?>"><?php endforeach; ?>
                        </datalist>
                    </div>
                </section>

                <!-- KONTAK & JANGKAUAN + SIMPAN -->
                <div class="pf-edit-side">
                    <section class="pf-card">
                        <h3 class="pf-card-title">Kontak &amp; Jangkauan</h3>
                        <div class="pf-field-icon pf-field">
                            <i class="fa-solid fa-location-dot"></i>
                            <label><span class="pf-field-lbl">Lokasi / Alamat Studio</span>
                                <input type="text" name="alamat" class="pf-input" maxlength="255" value="<?php echo mp_e($f['alamat']); ?>" placeholder="Jl. Kebon Jeruk Raya No. 27, Jakarta Barat">
                            </label>
                        </div>
                        <div class="pf-field-icon pf-field">
                            <i class="fa-solid fa-phone"></i>
                            <label><span class="pf-field-lbl">WhatsApp</span>
                                <input type="tel" name="nomor_telepon" class="pf-input" maxlength="20" value="<?php echo mp_e($f['nomor_telepon']); ?>" placeholder="0812-3456-7890">
                            </label>
                        </div>
                        <div class="pf-field-icon pf-field">
                            <i class="fa-regular fa-envelope"></i>
                            <label><span class="pf-field-lbl">Email</span>
                                <input type="email" name="email" class="pf-input" maxlength="100" required value="<?php echo mp_e($f['email']); ?>">
                            </label>
                        </div>
                        <small class="pf-help" style="margin:-6px 0 0 30px;">Email ini juga dipakai untuk login.</small>
                        <div class="pf-divider"></div>
                        <div class="pf-field">
                            <span>Area Jangkauan <small>pilih dari daftar</small></span>
                            <input type="hidden" name="area" id="pfArea" value="<?php echo mp_e($f['area']); ?>">
                            <div class="pf-chip-input" data-target="pfArea" data-max="9" data-maxlen="40" data-list="pfListArea" data-strict data-placeholder="Pilih wilayah"></div>
                            <datalist id="pfListArea">
                                <?php foreach (MUA_WILAYAH as $w): ?><option value="<?php echo mp_e($w); ?>"><?php endforeach; ?>
                            </datalist>
                            <small class="pf-help">Area pertama tampil sebagai lokasi utama di profilmu.</small>
                        </div>
                    </section>

                    <button type="submit" name="simpan_profil" class="pf-btn-filled pf-btn-save" data-loading-text="Menyimpan...">Simpan</button>
                </div>
            </div>
        </form>
    </main>

    <?php mua_toast_render(); ?>
    <script src="../assets/js/mua_ui.js"></script>
    <script src="../assets/js/mua_profil.js"></script>
</body>
</html>