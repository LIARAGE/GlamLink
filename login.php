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

            <form action="actions/login_act.php" method="POST">
                <div class="input-group">
                    <label>Alamat Email</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" name="email" placeholder="nama@email.com" required>
                    </div>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="password" required>
                        <i class="fa-solid fa-eye eye-icon"></i>
                    </div>
                    <div class="forgot-password">
                        <a href="#">Lupa Password?</a>
                    </div>
                </div>

                <button type="submit" name="login" class="btn-submit">Masuk</button>
            </form>
        </div>
        
        <div class="login-footer">
            Belum Memiliki Akun? <a href="register.php">Mulai Sekarang</a>
        </div>
    </main>

</body>
</html>