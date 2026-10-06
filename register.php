<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Peran - GlamLink</title>
    
    <!-- Link untuk mengambil Ikon secara online (Font Awesome) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/landing.css"> 
    <link rel="stylesheet" href="assets/css/register.css">
    
    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <!-- Navbar Sesuai Desain -->
    <header class="navbar">
        <div class="logo">
            <img src="assets/img/logo.png" alt="GlamLink Logo">
        </div>
        <div class="nav-links">
            <a href="login.php" class="btn-masuk">Masuk</a>
            <a href="register.php" class="btn-daftar">Mulai Sekarang</a>
        </div>
    </header>

    <!-- Konten Utama Pilih Role -->
    <main class="role-container">
        <div class="role-header">
            <span class="overline">BERGABUNG DENGAN GLAMLINK</span>
            <h1>Bagaimana Anda ingin menggunakan<br>GlamLink?</h1>
        </div>

        <div class="role-cards">
            <!-- Kartu MUA -->
            <div class="card">
                <div class="card-title">
                    <!-- Ikon Font Awesome untuk MUA -->
                    <i class="fa-solid fa-spa icon" style="font-size: 35px; color: #6d4b56;"></i>
                    <h2>Makeup Artist (MUA)</h2>
                </div>
                <p>Tampilkan karya terbaik Anda, dapatkan klien baru, dan kelola kalender pesanan di satu platform.</p>
                <a href="register_form.php?role=mua" class="btn-role btn-mua">Daftar Sebagai MUA &rarr;</a>
            </div>

            <!-- Kartu Klien -->
            <div class="card">
                <div class="card-title">
                    <!-- Ikon Font Awesome untuk Klien -->
                    <i class="fa-regular fa-face-smile icon" style="font-size: 35px; color: #9d6e7b;"></i>
                    <h2>Klien</h2>
                </div>
                <p>Temukan dan pesan Makeup Artist untuk kebutuhan Anda (pernikahan, wisuda, pesta perayaan, photoshoot, dll)</p>
                <!-- Parameter URL 'pelanggan' -->
                <a href="register_form.php?role=pelanggan" class="btn-role btn-klien">Daftar Sebagai Klien &rarr;</a>
            </div>
        </div>

        <div class="role-footer">
            <p>Sudah memiliki akun? <a href="login.php">Masuk Sekarang</a></p>
        </div>
    </main>

</body>
</html>