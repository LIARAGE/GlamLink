<?php
// Menangkap role dari URL. Jika tidak ada, atur default menjadi 'pelanggan'
$role = isset($_GET['role']) ? $_GET['role'] : 'pelanggan';

// Mengubah format teks untuk ditampilkan ke layar
$teks_role = ($role == 'mua') ? 'Makeup Artist (MUA)' : 'Klien';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar <?php echo $teks_role; ?> - GlamLink</title>
    <link rel="stylesheet" href="assets/css/register_form.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <div class="form-wrapper">
        <div class="form-card">
            <div class="form-header">
                <img src="assets/img/logo.png" alt="GlamLink Logo" class="form-logo">
                <h2>Daftar sebagai <?php echo $teks_role; ?></h2>
                <p>Lengkapi data di bawah ini untuk membuat akun baru.</p>
            </div>

            <form action="actions/register_act.php" method="POST">
                <!-- Data role disembunyikan tapi tetap dikirim ke database -->
                <input type="hidden" name="role" value="<?php echo htmlspecialchars($role); ?>">

                <div class="input-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" placeholder="Masukkan nama Anda" required>
                </div>

                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="contoh@email.com" required>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Buat password yang aman" required>
                </div>

                <button type="submit" name="register" class="btn-submit">Buat Akun</button>
            </form>

            <div class="form-footer">
                <p>Kembali ke <a href="register.php">Pilihan Akun</a> atau <a href="login.php">Masuk</a></p>
            </div>
        </div>
    </div>

</body>
</html>