<?php
/**
 * LUPA PASSWORD - langkah 1: user memasukkan email.
 * Dibuka dari link "Lupa Password?" di login.php.
 */
session_start();
require 'koneksi.php';
require_once 'includes/reset_password.php';

// Sudah login: tidak perlu reset password
if (isset($_SESSION['status_login'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $hasil = ['email' => $email];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $hasil['error'] = 'Format email tidak valid.';
    } else {
        $r = rp_buat_token($koneksi, $email);
        if ($r['status'] === 'terlalu_sering') {
            $hasil['error'] = 'Terlalu banyak permintaan. Coba lagi dalam 1 jam.';
        } elseif ($r['status'] === 'ok') {
            $link = rp_link($r['token']);
            if (RESET_MODE_DEMO) {
                $hasil['demo_link'] = $link;
                $hasil['nama'] = $r['user']['nama'];
            } else {
                rp_kirim_email($r['user'], $link);
                $hasil['terkirim'] = true;
            }
        } else {
            // Email tidak terdaftar
            if (RESET_MODE_DEMO) $hasil['error'] = 'Email tidak terdaftar sebagai akun klien atau MUA.';
            else $hasil['terkirim'] = true;   // mode asli: jangan bocorkan email mana yang terdaftar
        }
    }
    // Pola Post/Redirect/Get: refresh halaman tidak mengirim ulang form
    $_SESSION['rp_hasil'] = $hasil;
    header('Location: lupa_password.php');
    exit();
}

$hasil = $_SESSION['rp_hasil'] ?? [];
unset($_SESSION['rp_hasil']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - GlamLink</title>
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
            <div class="card-header">
                <span class="overline">LUPA PASSWORD</span>
                <h2>Atur Ulang Password</h2>
                <p class="rp-sub">Masukkan email akunmu. Kami akan membuat link untuk mengatur ulang password.</p>
            </div>

            <?php if (!empty($hasil['error'])): ?>
                <div class="rp-alert rp-alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo rp_e($hasil['error']); ?></div>
            <?php endif; ?>

            <?php if (!empty($hasil['terkirim'])): ?>
                <div class="rp-alert rp-alert-ok">
                    <i class="fa-solid fa-envelope-circle-check"></i>
                    <div>Jika <b><?php echo rp_e($hasil['email']); ?></b> terdaftar, link atur ulang password sudah dikirim ke email tersebut. Link berlaku <?php echo RESET_BERLAKU_MENIT; ?> menit.</div>
                </div>
            <?php endif; ?>

            <?php if (!empty($hasil['demo_link'])): ?>
                <div class="rp-demo">
                    <span class="rp-demo-badge"><i class="fa-solid fa-flask"></i> MODE DEMO</span>
                    <p>Halo <b><?php echo rp_e($hasil['nama']); ?></b>! Server lokal belum bisa mengirim email, jadi link reset ditampilkan di sini.
                       Link berlaku <b><?php echo RESET_BERLAKU_MENIT; ?> menit</b> dan hanya bisa dipakai sekali.</p>
                    <a href="<?php echo rp_e($hasil['demo_link']); ?>" class="btn-submit rp-btn-link"><i class="fa-solid fa-key"></i> Buka Link Atur Ulang Password</a>
                </div>
            <?php else: ?>
                <form action="lupa_password.php" method="POST" id="rpForm">
                    <div class="input-group">
                        <label>Alamat Email</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-envelope"></i>
                            <input type="email" name="email" placeholder="nama@email.com" maxlength="100" required autofocus
                                   value="<?php echo rp_e($hasil['email'] ?? ''); ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn-submit" id="rpSubmit">Kirim Link Atur Ulang</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="login-footer">
            Sudah ingat password? <a href="login.php">Kembali ke Masuk</a>
        </div>
    </main>

    <script>
        // Cegah klik dobel
        var f = document.getElementById('rpForm');
        if (f) f.addEventListener('submit', function () {
            var b = document.getElementById('rpSubmit');
            b.disabled = true;
            b.textContent = 'Memproses...';
        });
    </script>
</body>
</html>