<?php
$role = isset($_GET['role']) ? $_GET['role'] : 'pelanggan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Akun - GlamLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/landing.css">
    <link rel="stylesheet" href="assets/css/register_form.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <!-- Navbar -->
    <header class="navbar">
        <div class="logo">
            <img src="assets/img/logo.png" alt="GlamLink Logo">
        </div>
        <div class="nav-links">
            <a href="login.php" class="btn-masuk">Masuk</a>
            <a href="register.php" class="btn-daftar">Mulai Sekarang</a>
        </div>
    </header>

    <main class="form-container">

        <?php if ($role == 'pelanggan'): ?>
        <!-- ========================================== -->
        <!-- FORM KLIEN -->
        <!-- ========================================== -->
        <div class="card-klien">
            <div class="card-header">
                <span class="overline">AKUN KLIEN</span>
                <h2>Buat Akun</h2>
            </div>

            <form action="actions/register_act.php" method="POST">
                <input type="hidden" name="role" value="pelanggan">

                <div class="input-group">
                    <label>Nama Lengkap</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" name="nama" placeholder="Contoh: Virginia Aurelyn" required>
                    </div>
                </div>

                <div class="input-group">
                    <label>Alamat Email</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" name="email" placeholder="nama@email.com" required>
                    </div>
                </div>

                <div class="input-group">
                    <label>Nomor Telepon</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-phone"></i>
                        <input type="text" name="nomor_telepon" placeholder="081234567890" required>
                    </div>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="password" required>
                        <i class="fa-solid fa-eye eye-icon"></i>
                    </div>
                </div>

                <button type="submit" name="register" class="btn-submit">Buat Akun</button>

                <p class="terms-text">
                    Dengan membuat akun, Anda menyetujui <a href="#">Ketentuan</a> dan <a href="#">Kebijakan Privasi Kami</a>
                </p>
            </form>
        </div>
        <div class="card-footer">
            Sudah memiliki akun? <a href="login.php">Masuk</a>
        </div>

        <?php else: ?>
        <!-- ========================================== -->
        <!-- FORM MUA (STEP 1) -->
        <!-- ========================================== -->
        
        <!-- Stepper -->
        <div class="stepper-wrapper">
            <div class="step active">
                <div class="circle">1</div>
                <p>Your Profile</p>
            </div>
            <div class="line"></div>
            <div class="step">
                <div class="circle outline">2</div>
                <p>Portfolio</p>
            </div>
            <div class="line"></div>
            <div class="step">
                <div class="circle outline">3</div>
                <p>Services</p>
            </div>
            <div class="line"></div>
            <div class="step">
                <div class="circle outline">4</div>
                <p>Review</p>
            </div>
        </div>

        <div class="card-mua">
            <div class="card-header">
                <span class="overline">LANGKAH 1 DARI 4</span>
                <h2>Buat Profil MUAmu</h2>
            </div>

            <!-- Form ini nanti akan diarahkan ke tahap 2, bukan langsung disimpan -->
            <form action="register_mua_step2.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="role" value="mua">

                <div class="mua-top-section">
                    <div class="photo-upload">
                        <div class="photo-placeholder">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <span class="upload-text">Ganti Foto</span>
                        <input type="file" name="foto_profil" accept="image/*" class="file-input">
                    </div>
                    
                    <div class="brand-bio-section">
                        <div class="input-group">
                            <label>Nama Brand/Studio</label>
                            <input type="text" name="nama_brand" placeholder="Contoh: Kayla Studios" class="input-plain" required>
                        </div>
                        <div class="input-group">
                            <label>Bio</label>
                            <textarea name="bio" rows="3" placeholder="Professional makeup artist specializing in elegant bridal and soft glam looks." class="input-plain" required></textarea>
                        </div>
                    </div>
                </div>

                <div class="mua-grid-section">
                    <div class="input-group">
                        <label>Nama Lengkap</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-user"></i>
                            <input type="text" name="nama" placeholder="Contoh: Virginia Aurelyn" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Alamat Email</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-envelope"></i>
                            <input type="email" name="email" placeholder="nama@email.com" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Nomor Telepon</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-phone"></i>
                            <input type="text" name="nomor_telepon" placeholder="081234567890" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Lokasi</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-location-dot"></i>
                            <input type="text" name="lokasi" placeholder="Contoh: Jakarta Barat" required>
                        </div>
                    </div>
                </div>

                <div class="form-actions-mua">
                    <a href="register.php" class="btn-outline">Kembali</a>
                    <button type="submit" class="btn-submit-mua">Lanjut &rarr;</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

    </main>
</body>
</html>