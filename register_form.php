<?php
session_start();
$tipe = (($_GET['role'] ?? '') === 'mua') ? 'mua' : 'pelanggan'; // hanya 2 nilai yang valid

// Isian klien sebelumnya (tanpa password) jika pendaftaran ditolak server; dibaca sekali lalu dihapus
$old = $_SESSION['reg_klien_old'] ?? [];
unset($_SESSION['reg_klien_old']);

// Mengambil data sesi jika user menekan tombol kembali dari langkah 2
$s1 = isset($_SESSION['reg_mua_step1']) ? $_SESSION['reg_mua_step1'] : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Akun - GlamLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/register_form.css">
    <style>
        .text-danger { color: #E23636; margin-left: 3px; }
        .error-message { color: #E23636; font-size: 0.8rem; margin-top: 5px; display: none; }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <main class="form-container">
        <?php if ($tipe == 'pelanggan'): ?>
        <!-- ========================================== -->
        <!-- FORM KLIEN -->
        <!-- ========================================== -->
        <div class="card-klien">
            <div class="card-header">
                <span class="overline">AKUN KLIEN</span>
                <h2>Buat Akun</h2>
            </div>
            <?php
            $pesan_klien = [
                'nama'            => 'Nama lengkap wajib diisi (maksimal 100 karakter).',
                'email'           => 'Format email tidak valid.',
                'email_dipakai'   => 'Email sudah terdaftar. Gunakan email lain atau masuk.',
                'telepon'         => 'Nomor telepon tidak valid. Contoh: 081234567890.',
                'password_pendek' => 'Password minimal 8 karakter.',
                'password'        => 'Konfirmasi password tidak cocok.',
                'gagal'           => 'Pendaftaran gagal, silakan coba lagi.',
            ];
            if (isset($_GET['err'], $pesan_klien[$_GET['err']])) echo '<p class="error-message" style="display:block;text-align:center;margin-bottom:15px">' . $pesan_klien[$_GET['err']] . '</p>';
            ?>
            <form action="actions/register_act.php" method="POST" onsubmit="return validatePassword(this)">
                <input type="hidden" name="role" value="pelanggan">
                
                <div class="input-group">
                    <label>Nama Lengkap<span class="text-danger">*</span></label>
                    <div class="input-wrapper"><i class="fa-solid fa-user"></i><input type="text" name="nama" placeholder="Contoh: Virginia Aurelyn" maxlength="100" value="<?php echo htmlspecialchars($old['nama'] ?? ''); ?>" required></div>
                </div>
                
                <div class="input-group">
                    <label>Alamat Email<span class="text-danger">*</span></label>
                    <div class="input-wrapper"><i class="fa-solid fa-envelope"></i><input type="email" name="email" placeholder="nama@email.com" maxlength="100" value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>" required></div>
                </div>
                
                <div class="input-group">
                    <label>Nomor Telepon<span class="text-danger">*</span></label>
                    <div class="input-wrapper"><i class="fa-solid fa-phone"></i><input type="tel" name="nomor_telepon" placeholder="081234567890" pattern="(\+62|62|0)8[0-9]{8,11}" title="Contoh: 081234567890" value="<?php echo htmlspecialchars($old['nomor_telepon'] ?? ''); ?>" required></div>
                </div>
                
                <div class="input-group">
                    <label>Password<span class="text-danger">*</span></label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="password" class="password-input" minlength="8" placeholder="Minimal 8 karakter" required>
                        <i class="fa-solid fa-eye eye-icon toggle-password"></i>
                    </div>
                </div>

                <!-- Tambahan Konfirmasi Password Klien -->
                <div class="input-group">
                    <label>Konfirmasi Password<span class="text-danger">*</span></label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="confirm_password" class="password-input" required>
                        <i class="fa-solid fa-eye eye-icon toggle-password"></i>
                    </div>
                    <div class="error-message" id="error-pelanggan">Password tidak cocok!</div>
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
        <!-- FORM MUA STEP 1 -->
        <!-- ========================================== -->
        <div class="stepper-wrapper">
            <div class="step active"><div class="circle">1</div><p>Your Profile</p></div><div class="line"></div>
            <div class="step"><div class="circle outline">2</div><p>Portfolio</p></div><div class="line"></div>
            <div class="step"><div class="circle outline">3</div><p>Services</p></div><div class="line"></div>
            <div class="step"><div class="circle outline">4</div><p>Review</p></div>
        </div>

        <div class="card-mua">
            <div class="card-header">
                <span class="overline">LANGKAH 1 DARI 4</span>
                <h2>Buat Profil MUAmu</h2>
            </div>
            <?php
            require_once __DIR__ . '/Includes/upload.php';
            $pesan = ['password' => 'Password tidak cocok atau kurang dari 8 karakter.', 'email' => 'Email sudah terdaftar. Gunakan email lain atau masuk.', 'foto' => 'Foto gagal diunggah: ' . pesan_upload($_GET['alasan'] ?? '') . '.'];
            if (isset($_GET['err'], $pesan[$_GET['err']])) echo '<p class="error-message" style="display:block;text-align:center;margin-bottom:15px">' . $pesan[$_GET['err']] . '</p>';
            ?>
            <form action="register_mua_step2.php" method="POST" enctype="multipart/form-data" onsubmit="return validatePassword(this)">
                <input type="hidden" name="role" value="mua">

                <div class="mua-top-section">
                    <div class="photo-upload">
                        <div class="photo-placeholder">
                            <?php if(isset($s1['foto_profil'])): ?>
                                <img src="assets/img/<?php echo htmlspecialchars($s1['foto_profil']); ?>" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
                            <?php else: ?>
                                <i class="fa-solid fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <label for="foto_profil" class="upload-text" style="cursor:pointer;">Ganti Foto</label>
                        <input type="file" id="foto_profil" name="foto_profil" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="file-input" aria-label="Pilih foto profil MUA" <?php echo isset($s1['foto_profil']) ? '' : 'required'; ?>>
                        <?php if(!isset($s1['foto_profil'])): ?>
                            <span style="font-size: 0.75rem; color: #E23636;">*Wajib diisi</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="brand-bio-section">
                        <div class="input-group">
                            <label>Nama Brand/Studio<span class="text-danger">*</span></label>
                            <input type="text" name="nama_brand" class="input-plain" placeholder="Contoh: Kayla Studios" value="<?php echo htmlspecialchars($s1['nama_brand'] ?? ''); ?>" required>
                        </div>
                        <div class="input-group">
                            <label>Bio<span class="text-danger">*</span></label>
                            <textarea name="bio" rows="3" class="input-plain" placeholder="Professional makeup artist..." required><?php echo htmlspecialchars($s1['bio'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="mua-grid-section">
                    <div class="input-group">
                        <label>Nama Lengkap<span class="text-danger">*</span></label>
                        <div class="input-wrapper"><i class="fa-solid fa-user"></i><input type="text" name="nama" placeholder="Contoh: Virginia Aurelyn" value="<?php echo htmlspecialchars($s1['nama'] ?? ''); ?>" required></div>
                    </div>
                    
                    <div class="input-group">
                        <label>Alamat Email<span class="text-danger">*</span></label>
                        <div class="input-wrapper"><i class="fa-solid fa-envelope"></i><input type="email" name="email" placeholder="nama@email.com" value="<?php echo htmlspecialchars($s1['email'] ?? ''); ?>" required></div>
                    </div>
                    
                    <div class="input-group">
                        <label>Password<span class="text-danger">*</span></label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password" name="password" class="password-input" minlength="8" placeholder="<?php echo isset($s1['password_hash']) ? 'Kosongkan jika tidak diubah' : 'Minimal 8 karakter'; ?>" <?php echo isset($s1['password_hash']) ? '' : 'required'; ?>>
                            <i class="fa-solid fa-eye eye-icon toggle-password"></i>
                        </div>
                    </div>

                    <!-- Tambahan Konfirmasi Password MUA -->
                    <div class="input-group">
                        <label>Konfirmasi Password<span class="text-danger">*</span></label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password" name="confirm_password" class="password-input" <?php echo isset($s1['password_hash']) ? '' : 'required'; ?>>
                            <i class="fa-solid fa-eye eye-icon toggle-password"></i>
                        </div>
                        <div class="error-message" id="error-mua">Password tidak cocok!</div>
                    </div>
                    
                    <div class="input-group">
                        <label>Nomor Telepon<span class="text-danger">*</span></label>
                        <div class="input-wrapper"><i class="fa-solid fa-phone"></i><input type="text" name="nomor_telepon" placeholder="081234567890" value="<?php echo htmlspecialchars($s1['nomor_telepon'] ?? ''); ?>" required></div>
                    </div>
                    
                    <div class="input-group">
                        <label>Lokasi<span class="text-danger">*</span></label>
                        <div class="input-wrapper"><i class="fa-solid fa-location-dot"></i><input type="text" name="lokasi" placeholder="Contoh: Jakarta Barat" value="<?php echo htmlspecialchars($s1['lokasi'] ?? ''); ?>" required></div>
                    </div>
                </div>

                <div class="form-actions-mua">
                    <a href="register.php" class="btn-outline-mua">Batal</a>
                    <button type="submit" class="btn-submit-mua">Lanjut &rarr;</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </main>

    <script>
        // Preview foto profil sebelum dikirim ke server.
        const fotoProfilInput = document.getElementById('foto_profil');
        if (fotoProfilInput) {
            fotoProfilInput.addEventListener('change', function () {
                const file = this.files && this.files[0];
                if (!file) return;

                const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                const maxSize = 5 * 1024 * 1024;
                if (!allowedTypes.includes(file.type) || file.size > maxSize) {
                    alert('Pilih foto JPG, PNG, atau WEBP dengan ukuran maksimal 5 MB.');
                    this.value = '';
                    return;
                }

                const placeholder = document.querySelector('.photo-placeholder');
                if (!placeholder) return;
                const previewUrl = URL.createObjectURL(file);
                placeholder.replaceChildren();
                const img = document.createElement('img');
                img.src = previewUrl;
                img.alt = 'Pratinjau foto profil';
                img.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:50%;';
                img.onload = () => URL.revokeObjectURL(previewUrl);
                placeholder.appendChild(img);
            });
        }

        // Fitur Hide/Unhide Password (berlaku untuk semua field password)
        const togglePasswordButtons = document.querySelectorAll('.toggle-password');
        togglePasswordButtons.forEach(button => {
            button.addEventListener('click', function () {
                const passwordInput = this.previousElementSibling;
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                }
            });
        });

        // Validasi kecocokan password sebelum submit form
        function validatePassword(form) {
            const password = form.password.value;
            const confirm_password = form.confirm_password.value;
            
            // Mencari elemen error text di dalam form yang disubmit
            const errorElement = form.querySelector('.error-message');

            if (password !== confirm_password) {
                errorElement.style.display = 'block';
                form.confirm_password.focus();
                return false; // Mencegah form terkirim
            }
            
            errorElement.style.display = 'none';
            return true;
        }
    </script>
</body>
</html>