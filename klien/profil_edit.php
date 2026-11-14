<?php
session_start();
require __DIR__ . '/../includes/klien.php';
require __DIR__ . '/../includes/upload.php';
require __DIR__ . '/../includes/profil_klien.php';

/** Input teks dari POST (string saja, dipangkas spasi) dan input password (string saja, TIDAK dipangkas) */
function pv($k) { $v = $_POST[$k] ?? ''; return is_string($v) ? trim($v) : ''; }
function pw($k) { $v = $_POST[$k] ?? ''; return is_string($v) ? $v : ''; }

$u = q('SELECT nama, email, nomor_telepon, lokasi, bio, foto_profil FROM users WHERE id_user = ?', [$id_klien])[0];
$pesan_profil = ''; $pesan_pw = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = pv('aksi');

    if ($aksi === 'profil') {
        $nama = pv('nama'); $hp = preg_replace('/[\s\-]/', '', pv('nomor_telepon')); $alamat = pv('alamat'); $bio = pv('bio');
        $u['nama'] = $nama; $u['nomor_telepon'] = pv('nomor_telepon'); $u['lokasi'] = $alamat; $u['bio'] = $bio;   // tampilkan lagi isian bila ada yang salah

        if (!csrf_ok())                                              $pesan_profil = 'Sesi formulir sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.';
        elseif ($nama === '' || mb_strlen($nama) > 100)              $pesan_profil = 'Nama wajib diisi (maksimal 100 karakter).';
        elseif (!preg_match('/^(\+62|62|0)8[0-9]{8,11}$/', $hp))     $pesan_profil = 'Nomor telepon tidak valid. Contoh: 081234567890.';
        elseif (mb_strlen($alamat) > 150)                            $pesan_profil = 'Alamat maksimal 150 karakter.';
        elseif (mb_strlen($bio) > 160)                               $pesan_profil = 'Bio maksimal 160 karakter.';
        else {
            $foto = '';
            if (!empty($_FILES['foto']['name'])) {
                $foto = simpan_gambar($_FILES['foto'], 'profil', 5, $alasan);
                if (!$foto) { $pesan_profil = 'Foto gagal diunggah: ' . pesan_upload($alasan) . '.'; }
            }
            if ($pesan_profil === '') {
                q("UPDATE users SET nama = ?, nomor_telepon = ?, lokasi = ?, bio = ?, foto_profil = COALESCE(NULLIF(?, ''), foto_profil) WHERE id_user = ?",
                  [$nama, $hp, $alamat !== '' ? $alamat : null, $bio !== '' ? $bio : null, $foto, $id_klien]);
                $_SESSION['nama'] = $nama;
                header('Location: profil.php?msg=profil'); exit();
            }
        }
    } elseif ($aksi === 'password') {
        $row  = q('SELECT password FROM users WHERE id_user = ?', [$id_klien])[0];
        $lama = pw('lama'); $baru = pw('baru');
        if (!csrf_ok())                                         $pesan_pw = 'Sesi formulir sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.';
        elseif (!password_verify($lama, $row['password']))      $pesan_pw = 'Password lama salah.';
        elseif (strlen($baru) < 8)                              $pesan_pw = 'Password baru minimal 8 karakter.';
        elseif ($baru !== pw('konfirmasi'))                     $pesan_pw = 'Konfirmasi password baru tidak cocok.';
        elseif (password_verify($baru, $row['password']))       $pesan_pw = 'Password baru tidak boleh sama dengan password lama.';
        else {
            q('UPDATE users SET password = ? WHERE id_user = ?', [password_hash($baru, PASSWORD_DEFAULT), $id_klien]);
            header('Location: profil.php?msg=password'); exit();
        }
    }
}

$fav = daftar_favorit($id_klien);
// Kolom yang langsung difokuskan bila datang dari ikon pensil di halaman Profil
$fokus = ['telp' => 'f-telp', 'alamat' => 'f-alamat'][is_string($_GET['fokus'] ?? null) ? $_GET['fokus'] : ''] ?? '';
$notif_fav = pesan_profil('', is_string($_GET['info'] ?? null) ? $_GET['info'] : '');

head_klien('Edit Profil');
?>
<header class="bk-header"><a href="index.php"><img src="../assets/img/logo.png" alt="GlamLink"></a></header>
<div class="bk-band"><div class="bk-wrap bk-band-in">
    <a class="bk-back" href="profil.php" aria-label="Kembali ke Profil"><i class="fa-solid fa-arrow-left"></i></a>
    <h1>Edit Profil</h1>
</div></div>

<main class="pr-page pr-edit">
    <?php if ($notif_fav) echo '<p class="alert-' . $notif_fav[0] . '" role="status">' . e($notif_fav[1]) . '</p>'; ?>
    <div class="pr-grid">
        <form class="pr-card" method="POST" enctype="multipart/form-data" novalidate>
            <h2>Informasi Pribadi</h2>
            <?php if ($pesan_profil) echo '<p class="alert-err" role="alert">' . e($pesan_profil) . '</p>'; ?>
            <?php echo csrf_field(); ?><input type="hidden" name="aksi" value="profil">

            <div class="pr-foto">
                <label class="pr-avatar" for="f-foto" title="Ganti foto profil"><span id="pr-prev"><?php echo foto($u['foto_profil'], 'avatar xl'); ?></span><span class="pr-cam"><i class="fa-solid fa-camera"></i></span></label>
                <input type="file" id="f-foto" name="foto" class="pr-file" accept="image/jpeg,image/png,image/webp">
                <div><b>Foto Profil</b><small class="muted">Klik foto untuk mengganti. JPG, PNG, atau WebP, maks. 5 MB.</small><small class="pr-fotoerr" id="pr-fotoerr" hidden></small></div>
            </div>

            <div class="pr-item"><label class="pr-lbl" for="f-nama">Nama Lengkap</label>
                <div class="pr-in"><input type="text" id="f-nama" name="nama" maxlength="100" value="<?php echo e($u['nama']); ?>" required><i class="fa-solid fa-pencil"></i></div></div>
            <div class="pr-item"><label class="pr-lbl" for="f-bio">Bio <span class="muted" id="pr-bio-n"></span></label>
                <div class="pr-in pr-ta"><textarea id="f-bio" name="bio" rows="2" maxlength="160" placeholder="Ceritakan sedikit tentang Anda"><?php echo e($u['bio']); ?></textarea></div></div>
            <div class="pr-item"><label class="pr-lbl" for="f-email">Email</label>
                <div class="pr-in pr-off"><input type="email" id="f-email" value="<?php echo e($u['email']); ?>" disabled><i class="fa-solid fa-lock" title="Email tidak dapat diubah"></i></div></div>
            <div class="pr-item"><label class="pr-lbl" for="f-telp">No Telepon</label>
                <div class="pr-in"><input type="tel" id="f-telp" name="nomor_telepon" inputmode="tel" placeholder="081234567890" value="<?php echo e($u['nomor_telepon']); ?>" required><i class="fa-solid fa-pencil"></i></div></div>
            <div class="pr-item"><label class="pr-lbl" for="f-alamat">Alamat</label>
                <div class="pr-in"><input type="text" id="f-alamat" name="alamat" maxlength="150" placeholder="Jl. Contoh No. 1, Jakarta Barat" value="<?php echo e($u['lokasi']); ?>"><i class="fa-solid fa-pencil"></i></div>
                <small class="muted">Alamat ini otomatis terisi saat Anda membuat booking.</small></div>
            <button class="pr-save" type="submit">Simpan</button>
        </form>

        <section class="pr-card">
            <h2>MUA Favorit</h2>
            <?php foreach ($fav as $m) kartu_favorit($m, 'profil_edit'); ?>
            <?php if (!$fav): ?><div class="kosong pr-kosong"><i class="fa-regular fa-heart"></i><p>Belum ada MUA favorit.</p><a class="kb-fill" href="cari.php">Cari MUA</a></div><?php endif; ?>
        </section>
    </div>

    <form class="pr-card pr-pass" id="password" method="POST" novalidate>
        <h2>Ganti Password</h2>
        <?php if ($pesan_pw) echo '<p class="alert-err" role="alert">' . e($pesan_pw) . '</p>'; ?>
        <?php echo csrf_field(); ?><input type="hidden" name="aksi" value="password">
        <div class="pr-pass-grid">
            <div class="pr-item"><label class="pr-lbl" for="pr-lama">Password Lama</label><div class="pr-in"><input type="password" id="pr-lama" name="lama" autocomplete="current-password" required></div></div>
            <div class="pr-item"><label class="pr-lbl" for="pr-baru">Password Baru</label><div class="pr-in"><input type="password" id="pr-baru" name="baru" minlength="8" autocomplete="new-password" required></div><small class="muted">Minimal 8 karakter.</small></div>
            <div class="pr-item"><label class="pr-lbl" for="pr-kon">Konfirmasi Password Baru</label><div class="pr-in"><input type="password" id="pr-kon" name="konfirmasi" autocomplete="new-password" required></div></div>
        </div>
        <button class="pr-save pr-save-sm" type="submit">Ganti Password</button>
    </form>
</main>
<script>
(function () {
    var foto = document.getElementById('f-foto'), err = document.getElementById('pr-fotoerr'), prev = document.getElementById('pr-prev');
    foto.addEventListener('change', function () {
        var f = foto.files[0]; err.hidden = true;
        if (!f) return;
        if (['image/jpeg', 'image/png', 'image/webp'].indexOf(f.type) < 0) { err.textContent = 'Format harus JPG, PNG, atau WebP.'; err.hidden = false; foto.value = ''; return; }
        if (f.size > 5 * 1024 * 1024) { err.textContent = 'Ukuran foto lebih dari 5 MB.'; err.hidden = false; foto.value = ''; return; }
        var img = document.createElement('img'); img.className = 'avatar xl'; img.alt = ''; img.src = URL.createObjectURL(f);
        prev.innerHTML = ''; prev.appendChild(img);                      // pratinjau sebelum disimpan
    });
    var bio = document.getElementById('f-bio'), n = document.getElementById('pr-bio-n');
    function hitung() { n.textContent = '(' + bio.value.length + '/160)'; } bio.addEventListener('input', hitung); hitung();
    var fokus = <?php echo json_encode($fokus); ?>;
    if (fokus && document.getElementById(fokus)) { var el = document.getElementById(fokus); el.focus(); el.setSelectionRange && el.setSelectionRange(el.value.length, el.value.length); }
    <?php if ($pesan_pw) echo "document.getElementById('password').scrollIntoView();"; ?>
    if (location.hash === '#password' && !fokus) { document.getElementById('pr-lama').focus({ preventScroll: true }); }
})();
</script>
</body></html>
