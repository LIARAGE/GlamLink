<?php
session_start();

// Jika sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['status_login'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/index.php");
    } else if ($_SESSION['role'] == 'mua') {
        header("Location: mua/index.php");
    } else {
        header("Location: beranda.php");
    }
    exit();
}

// Pesan gagal login dari actions/login_act.php (tampil sekali)
$login_error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - GlamLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <style>
        /* Kotak pesan di dalam kartu login (pengganti alert browser) */
        .login-alert {
            display: flex; gap: 12px; align-items: flex-start;
            border-radius: 12px; padding: 13px 14px; margin-bottom: 20px;
            font-size: 0.86rem; line-height: 1.5;
            animation: loginMasuk 0.3s ease both, loginGetar 0.4s ease 0.05s;
        }
        .login-alert i { margin-top: 3px; font-size: 1rem; }
        .login-alert b { display: block; margin-bottom: 1px; }
        .login-alert-error { background: #FDECEC; color: #B4233A; border: 1px solid #F5C2C7; }
        .login-alert-ok { background: #E9F6EF; color: #1f6b47; border: 1px solid #BFE3CE; animation: loginMasuk 0.3s ease both; }
        .login-alert-close { margin-left: auto; background: none; border: none; color: inherit; opacity: 0.6; cursor: pointer; font-size: 0.9rem; padding: 0 2px; }
        .login-alert-close:hover { opacity: 1; }
        .input-wrapper.is-error input { border-color: #E5A3AE !important; background: #FFF8F8 !important; }
        .input-wrapper.is-error > i { color: #B4233A; }
        @keyframes loginMasuk { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }
        @keyframes loginGetar { 0%, 100% { margin-left: 0; } 25% { margin-left: -6px; } 50% { margin-left: 6px; } 75% { margin-left: -3px; } }
        @media (prefers-reduced-motion: reduce) { .login-alert { animation: none; } }
    </style>
</head>
<body>

    <!-- Memanggil Navbar -->
    <?php include 'navbar.php'; ?>

    <main class="login-container">
        <div class="login-card">
            <div class="card-header">
                <span class="overline">SELAMAT DATANG KEMBALI</span>
                <h2>Masuk ke GlamLink</h2>
            </div>

            <?php if ($login_error): ?>
                <!-- Pesan gagal login (dari actions/login_act.php) -->
                <div class="login-alert login-alert-error" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <div><b>Gagal masuk</b><?php echo htmlspecialchars($login_error['pesan']); ?></div>
                    <button type="button" class="login-alert-close" aria-label="Tutup pesan" onclick="this.parentNode.remove()"><i class="fa-solid fa-xmark"></i></button>
                </div>
            <?php elseif (($_GET['reset'] ?? '') === 'berhasil'): ?>
                <!-- Pesan setelah berhasil membuat password baru (dari reset_password.php) -->
                <div class="login-alert login-alert-ok" role="status">
                    <i class="fa-solid fa-circle-check"></i>
                    <div><b>Password berhasil diubah</b>Silakan masuk dengan password baru.</div>
                </div>
            <?php endif; ?>

            <form action="actions/login_act.php" method="POST">
                <div class="input-group">
                    <label>Alamat Email</label>
                    <div class="input-wrapper <?php echo $login_error ? 'is-error' : ''; ?>">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" name="email" placeholder="nama@email.com" required
                               value="<?php echo htmlspecialchars($login_error['email'] ?? ''); ?>">
                    </div>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <div class="input-wrapper <?php echo $login_error ? 'is-error' : ''; ?>">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="password" required <?php echo $login_error ? 'autofocus' : ''; ?>>
                        <button type="button" class="fa-solid fa-eye eye-icon toggle-password" aria-label="Tampilkan password" aria-pressed="false"></button>
                    </div>
                    <div class="forgot-password">
                        <a href="lupa_password.php">Lupa Password?</a>
                    </div>
                </div>

                <button type="submit" name="login" class="btn-submit">Masuk</button>
            </form>
        </div>
        
        <div class="login-footer">
            Belum Memiliki Akun? <a href="register.php">Mulai Sekarang</a>
        </div>
    </main>

    <script>
        // Tanda merah di kolom hilang begitu user mulai mengetik ulang
        document.querySelectorAll('.input-wrapper.is-error input').forEach(function (inp) {
            inp.addEventListener('input', function () {
                document.querySelectorAll('.input-wrapper.is-error').forEach(function (w) { w.classList.remove('is-error'); });
            }, { once: true });
        });

        document.querySelectorAll('.toggle-password').forEach(function (button) {
            button.addEventListener('click', function () {
                const wrapper = this.closest('.input-wrapper');
                const input = wrapper ? wrapper.querySelector('input[name="password"]') : null;
                if (!input) return;
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                this.classList.toggle('fa-eye', !show);
                this.classList.toggle('fa-eye-slash', show);
                this.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
                this.setAttribute('aria-pressed', String(show));
            });
        });
    </script>
</body>
</html>