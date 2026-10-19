<?php
session_start();
require __DIR__ . '/../Includes/klien.php';
require __DIR__ . '/../Includes/upload.php';

$pesan = ''; $tipe = 'ok';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['aksi'] ?? '') === 'profil') {
        $nama = trim($_POST['nama'] ?? ''); $hp = preg_replace('/[\s\-]/', '', trim($_POST['nomor_telepon'] ?? ''));
        if ($nama === '' || mb_strlen($nama) > 100) { $pesan = 'Nama wajib diisi (maksimal 100 karakter).'; $tipe = 'err'; }
        elseif (!preg_match('/^(\+62|62|0)8[0-9]{8,11}$/', $hp)) { $pesan = 'Nomor telepon tidak valid. Contoh: 081234567890.'; $tipe = 'err'; }
        else {
            $foto = '';
            if (!empty($_FILES['foto']['name'])) {
                $foto = simpan_gambar($_FILES['foto'], 'profil', 5, $alasan);
                if (!$foto) { $pesan = 'Foto gagal diunggah: ' . pesan_upload($alasan) . '.'; $tipe = 'err'; }
            }
            if ($tipe === 'ok') {
                q("UPDATE users SET nama = ?, nomor_telepon = ?, foto_profil = COALESCE(NULLIF(?, ''), foto_profil) WHERE id_user = ?", [$nama, $hp, $foto, $id_klien]);
                $_SESSION['nama'] = $nama; $pesan = 'Profil berhasil diperbarui.';
            }
        }
    } elseif (($_POST['aksi'] ?? '') === 'password') {
        $u = q('SELECT password FROM users WHERE id_user = ?', [$id_klien])[0];
        if (!password_verify($_POST['lama'] ?? '', $u['password'])) { $pesan = 'Password lama salah.'; $tipe = 'err'; }
        elseif (strlen($_POST['baru'] ?? '') < 8) { $pesan = 'Password baru minimal 8 karakter.'; $tipe = 'err'; }
        elseif (($_POST['baru'] ?? '') !== ($_POST['konfirmasi'] ?? '')) { $pesan = 'Konfirmasi password baru tidak cocok.'; $tipe = 'err'; }
        else { q('UPDATE users SET password = ? WHERE id_user = ?', [password_hash($_POST['baru'], PASSWORD_DEFAULT), $id_klien]); $pesan = 'Password berhasil diganti.'; }
    }
}
$u = q('SELECT nama, email, nomor_telepon, foto_profil FROM users WHERE id_user = ?', [$id_klien])[0];

head_klien('Profil');
include __DIR__ . '/../navbar.php';
?>
<main class="kl-wrap page-top">
    <h1 class="kl-title big">Profil Saya</h1>
    <?php if ($pesan) echo '<p class="alert-' . $tipe . '">' . e($pesan) . '</p>'; ?>
    <div class="booking-grid">
        <form class="form-card" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="aksi" value="profil">
            <div class="foto-edit"><?php echo foto($u['foto_profil'], 'avatar xl'); ?><input type="file" name="foto" accept="image/jpeg,image/png,image/webp"></div>
            <label>Nama Lengkap</label><input type="text" name="nama" maxlength="100" value="<?php echo e($u['nama']); ?>" required>
            <label>Email</label><input type="email" value="<?php echo e($u['email']); ?>" disabled>
            <label>Nomor Telepon</label><input type="tel" name="nomor_telepon" value="<?php echo e($u['nomor_telepon']); ?>" required>
            <button class="btn-cari full" type="submit">Simpan Perubahan</button>
        </form>
        <form class="form-card" method="POST">
            <input type="hidden" name="aksi" value="password"><h3>Ganti Password</h3>
            <label>Password Lama</label><input type="password" name="lama" required>
            <label>Password Baru</label><input type="password" name="baru" minlength="8" required>
            <label>Konfirmasi Password Baru</label><input type="password" name="konfirmasi" required>
            <button class="btn-cari full" type="submit">Ganti Password</button>
        </form>
    </div>
</main>
<?php bottom_nav();