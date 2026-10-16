<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Peran - GlamLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/navbar.css"> 
    <link rel="stylesheet" href="assets/css/register.css">
</head>
<body>

    <!-- Memanggil Navbar -->
    <?php include 'navbar.php'; ?>

    <main class="role-container">
        <div class="role-header">
            <span class="overline">BERGABUNG DENGAN GLAMLINK</span>
            <h1>Bagaimana Anda ingin menggunakan<br>GlamLink?</h1>
        </div>

        <div class="role-cards">
            <div class="card">
                <div class="card-title">
                    <i class="fa-solid fa-spa icon" style="font-size: 35px; color: #2b2326;"></i>
                    <h2>Makeup Artist (MUA)</h2>
                </div>
                <p>Tampilkan karya terbaik Anda, dapatkan klien baru, dan kelola kalender pesanan di satu platform.</p>
                <a href="register_form.php?role=mua" class="btn-role btn-mua">Daftar Sebagai MUA &rarr;</a>
            </div>

            <div class="card">
                <div class="card-title">
                    <i class="fa-regular fa-face-smile icon" style="font-size: 35px; color: #2b2326;"></i>
                    <h2>Klien</h2>
                </div>
                <p>Temukan dan pesan Makeup Artist untuk kebutuhan Anda (pernikahan, wisuda, pesta perayaan, photoshoot, dll)</p>
                <a href="register_form.php?role=pelanggan" class="btn-role btn-klien">Daftar Sebagai Klien &rarr;</a>
            </div>
        </div>

        <div class="role-footer">
            <p>Sudah memiliki akun? <a href="login.php">Masuk Sekarang</a></p>
        </div>
    </main>

</body>
</html>