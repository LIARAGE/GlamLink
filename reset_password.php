<?php
/**
 * LUPA PASSWORD - langkah 2: user membuka link (reset_password.php?token=...) lalu membuat password baru.
 * Token hanya berlaku 30 menit dan hanya bisa dipakai sekali.
 */
session_start();
require 'koneksi.php';
require_once 'includes/reset_password.php';

$token = (string)($_POST['token'] ?? $_GET['token'] ?? '');
$reset = rp_cek_token($koneksi, $token);
$error = '';

if ($reset && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass    = (string)($_POST['password'] ?? '');
    $konfirm = (string)($_POST['konfirmasi'] ?? '');

    $st = $koneksi->prepare("SELECT password FROM users WHERE id_user = ?");
    $st->bind_param('i', $reset['id_user']);
    $st->execute();
    $lama = (string)($st->get_result()->fetch_assoc()['password'] ?? '');

    if (strlen($pass) < PASSWORD_MIN)        $error = 'Password minimal ' . PASSWORD_MIN . ' karakter.';
    elseif (strlen($pass) > 72)              $error = 'Password maksimal 72 karakter.';
    elseif ($pass !== $konfirm)              $error = 'Konfirmasi password tidak sama.';
    elseif (password_verify($pass, $lama))   $error = 'Password baru tidak boleh sama dengan password lama.';
    else {
        $koneksi->begin_transaction();
        // Tandai token terpakai dulu. Jika 0 baris berubah, berarti token sudah dipakai di tab lain.
        $st = $koneksi->prepare("UPDATE password_resets SET dipakai_pada = NOW() WHERE id_reset = ? AND dipakai_pada IS NULL");
        $st->bind_param('i', $reset['id_reset']);
        $st->execute();
        if ($st->affected_rows !== 1) {
            $koneksi->rollback();
            $reset = null;
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $st = $koneksi->prepare("UPDATE users SET password = ? WHERE id_user = ?");
            $st->bind_param('si', $hash, $reset['id_user']);
            $st->execute();
            // Link reset lain milik user ini ikut tidak berlaku
            $st = $koneksi->prepare("UPDATE password_resets SET kedaluwarsa = NOW() WHERE id_user = ? AND dipakai_pada IS NULL");
            $st->bind_param('i', $reset['id_user']);
            $st->execute();
            $koneksi->commit();

            header('Location: login.php?reset=berhasil');
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title>Buat Password Baru - GlamLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="assets/css/reset_password.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="login-container">
        <div class="login-card">
        <?php if (!$reset): ?>
            <div class="rp-invalid">
                <i class="fa-solid fa-link-slash"></i>
                <h2>Link Tidak Berlaku</h2>
                <p>Link atur ulang password ini salah, sudah dipakai, atau sudah lewat <?php echo RESET_BERLAKU_MENIT; ?> menit.</p>
                <a href="lupa_password.php" class="btn-submit rp-btn-link">Minta Link Baru</a>
            </div>
        <?php else: ?>
            <div class="card-header">
                <span class="overline">PASSWORD BARU</span>
                <h2>Buat Password Baru</h2>
                <p class="rp-sub">Untuk akun <b><?php echo rp_e($reset['email']); ?></b></p>
            </div>

            <?php if ($error): ?>
                <div class="rp-alert rp-alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo rp_e($error); ?></div>
            <?php endif; ?>

            <form action="reset_password.php" method="POST" id="rpForm">
                <input type="hidden" name="token" value="<?php echo rp_e($token); ?>">
                <div class="input-group">
                    <label>Password Baru</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="password" id="rpPass" minlength="<?php echo PASSWORD_MIN; ?>" maxlength="72" required autofocus autocomplete="new-password">
                        <button type="button" class="fa-solid fa-eye eye-icon toggle-password" aria-label="Tampilkan password" aria-pressed="false"></button>
                    </div>
                    <div class="rp-strength" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
                    <small class="rp-hint" id="rpHint">Minimal <?php echo PASSWORD_MIN; ?> karakter. Campurkan huruf besar, angka, dan simbol agar lebih kuat.</small>
                </div>
                <div class="input-group">
                    <label>Konfirmasi Password Baru</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="konfirmasi" id="rpKonfirm" maxlength="72" required autocomplete="new-password">
                        <button type="button" class="fa-solid fa-eye eye-icon toggle-password" aria-label="Tampilkan password" aria-pressed="false"></button>
                    </div>
                    <small class="rp-hint" id="rpCocok"></small>
                </div>
                <button type="submit" class="btn-submit" id="rpSubmit">Simpan Password Baru</button>
            </form>
        <?php endif; ?>
        </div>

        <div class="login-footer">
            <a href="login.php"><i class="fa-solid fa-arrow-left"></i> Kembali ke Masuk</a>
        </div>
    </main>

    <script>
    (function () {
        // Tampilkan / sembunyikan password (sama seperti halaman login)
        document.querySelectorAll('.toggle-password').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = this.closest('.input-wrapper').querySelector('input');
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                this.classList.toggle('fa-eye', !show);
                this.classList.toggle('fa-eye-slash', show);
                this.setAttribute('aria-pressed', String(show));
            });
        });

        var pass = document.getElementById('rpPass'), konf = document.getElementById('rpKonfirm');
        if (!pass) return;
        var bar = document.querySelector('.rp-strength'), hint = document.getElementById('rpHint'), cocok = document.getElementById('rpCocok');
        var LABEL = ['Terlalu pendek', 'Lemah', 'Cukup', 'Kuat', 'Sangat kuat'];

        function kekuatan(p) {
            if (p.length < <?php echo PASSWORD_MIN; ?>) return 0;
            var s = 1;
            if (/[A-Z]/.test(p) && /[a-z]/.test(p)) s++;
            if (/\d/.test(p)) s++;
            if (/[^A-Za-z0-9]/.test(p) || p.length >= 14) s++;
            return s;
        }
        function cekCocok() {
            if (!konf.value) { cocok.textContent = ''; cocok.className = 'rp-hint'; return; }
            var ok = konf.value === pass.value;
            cocok.textContent = ok ? 'Password cocok.' : 'Password belum sama.';
            cocok.className = 'rp-hint ' + (ok ? 'ok' : 'bad');
        }
        pass.addEventListener('input', function () {
            var s = pass.value ? kekuatan(pass.value) : -1;
            bar.dataset.level = s;
            hint.textContent = s < 0 ? 'Minimal <?php echo PASSWORD_MIN; ?> karakter. Campurkan huruf besar, angka, dan simbol agar lebih kuat.' : 'Kekuatan: ' + LABEL[s];
            cekCocok();
        });
        konf.addEventListener('input', cekCocok);

        document.getElementById('rpForm').addEventListener('submit', function (e) {
            if (pass.value !== konf.value) { e.preventDefault(); cekCocok(); konf.focus(); return; }
            var b = document.getElementById('rpSubmit');
            b.disabled = true;
            b.textContent = 'Menyimpan...';
        });
    })();
    </script>
</body>
</html>