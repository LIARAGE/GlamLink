<?php
session_start();
require 'koneksi.php';
require 'includes/upload.php';

// 1. LOGIKA HAPUS FOTO DARI SESSION & SERVER
if (isset($_GET['hapus_foto'])) {
    $foto_hapus = $_GET['hapus_foto'];
    $portfolios = $_SESSION['reg_mua_step2']['portfolio'] ?? [];
    
    // Cari foto di dalam array session
    if (($key = array_search($foto_hapus, $portfolios)) !== false) {
        unset($portfolios[$key]);
        $_SESSION['reg_mua_step2']['portfolio'] = array_values($portfolios); // Re-index array
        
        // Hapus file fisiknya dari folder assets/img
        $file_path = 'assets/img/' . $foto_hapus;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    // Refresh halaman agar bersih dari URL
    header("Location: register_mua_step2.php");
    exit();
}

// 2. Menerima data dari Langkah 1
if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($_POST['role'] ?? '') == 'mua') {
    $balik = 'register_form.php?role=mua&err=';

    // Simpan HANYA field yang diizinkan (password mentah & konfirmasi tidak ikut disimpan)
    foreach (['nama', 'email', 'nomor_telepon', 'nama_brand', 'bio', 'lokasi'] as $k) {
        $_SESSION['reg_mua_step1'][$k] = trim($_POST[$k] ?? '');
    }

    // Validasi password di SERVER (JavaScript saja bisa dilewati)
    $pw = $_POST['password'] ?? '';
    if ($pw !== '') {
        if ($pw !== ($_POST['confirm_password'] ?? '') || strlen($pw) < 8) { header('Location: ' . $balik . 'password'); exit(); }
        $_SESSION['reg_mua_step1']['password_hash'] = password_hash($pw, PASSWORD_DEFAULT);
    } elseif (empty($_SESSION['reg_mua_step1']['password_hash'])) {
        header('Location: ' . $balik . 'password'); exit();
    }

    // Cek email dari awal, jangan tunggu sampai langkah 4
    $cek = $koneksi->prepare('SELECT 1 FROM users WHERE email = ?');
    $cek->bind_param('s', $_SESSION['reg_mua_step1']['email']);
    $cek->execute();
    if ($cek->get_result()->num_rows > 0) { header('Location: ' . $balik . 'email'); exit(); }

    // Foto profil
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] != UPLOAD_ERR_NO_FILE) {
        $f = simpan_gambar($_FILES['foto_profil'], 'profil');
        if (!$f) { header('Location: ' . $balik . 'foto'); exit(); }
        $_SESSION['reg_mua_step1']['foto_profil'] = $f;
    }
}

if (!isset($_SESSION['reg_mua_step1'])) {
    header("Location: register_form.php?role=mua");
    exit();
}

$portfolios = $_SESSION['reg_mua_step2']['portfolio'] ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio - Langkah 2 - GlamLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/register_form.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <main class="form-container">
        <div class="stepper-wrapper">
            <div class="step"><div class="circle outline"><i class="fa-solid fa-check"></i></div><p>Your Profile</p></div><div class="line" style="background-color: #7E4655;"></div>
            <div class="step active"><div class="circle">2</div><p style="color: #7E4655;">Portfolio</p></div><div class="line"></div>
            <div class="step"><div class="circle outline">3</div><p>Services</p></div><div class="line"></div>
            <div class="step"><div class="circle outline">4</div><p>Review</p></div>
        </div>

        <div class="card-mua">
            <div class="card-header">
                <span class="overline">LANGKAH 2 DARI 4</span>
                <h2>Tampilkan Karyamu</h2>
            </div>
            <form action="register_mua_step3.php" method="POST" enctype="multipart/form-data">
                <div class="upload-area">
                    <i class="fa-regular fa-image" style="font-size: 40px; color: #9A6372; margin-bottom: 15px;"></i>
                    <p style="font-weight: 600; color: #2b2326; margin-bottom: 5px;">Letakkan foto portfolio disini</p>
                    <label for="portfolio_input" class="btn-outline-mua" style="display: inline-block; padding: 10px 25px; cursor: pointer;">Pilih Foto</label>
                    <input type="file" name="portfolio[]" id="portfolio_input" multiple accept="image/jpeg, image/png" style="display: none;">
                </div>

                <!-- Menampilkan Foto yang SUDAH TERSIMPAN di Session -->
                <?php if(count($portfolios) > 0): ?>
                    <p style="font-size: 0.9rem; font-weight: 600; margin-bottom: 10px;">Foto Tersimpan:</p>
                    <div class="portfolio-preview-grid">
                        <?php foreach($portfolios as $port): ?>
                            <div class="preview-item">
                                <img src="assets/img/<?php echo $port; ?>">
                                <a href="?hapus_foto=<?php echo urlencode($port); ?>" class="btn-remove-photo" title="Hapus foto ini"><i class="fa-solid fa-xmark"></i></a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Menampilkan Foto BARU yang sedang dipilih (Belum disubmit) -->
                <p id="new_photo_label" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 10px; display: none;">Foto Baru Dipilih:</p>
                <div class="portfolio-preview-grid" id="new_preview_container"></div>

                <div class="form-actions-mua">
                    <a href="register_form.php?role=mua" class="btn-outline-mua">Kembali</a>
                    <button type="submit" class="btn-submit-mua">Lanjut &rarr;</button>
                </div>
            </form>
        </div>
    </main>

    <!-- JavaScript dengan fitur hapus file tunggal (DataTransfer) -->
    <script>
        const fileInput = document.getElementById('portfolio_input');
        const previewContainer = document.getElementById('new_preview_container');
        const labelBaru = document.getElementById('new_photo_label');
        let dataTransfer = new DataTransfer();

        fileInput.addEventListener('change', function() {
            // Memasukkan file baru ke DataTransfer tanpa menimpa yang sudah ada di JS
            Array.from(this.files).forEach(file => {
                dataTransfer.items.add(file);
            });
            fileInput.files = dataTransfer.files;
            renderPreviews();
        });

        function renderPreviews() {
            previewContainer.innerHTML = '';
            
            if (fileInput.files.length > 0) {
                labelBaru.style.display = 'block';
            } else {
                labelBaru.style.display = 'none';
            }

            Array.from(fileInput.files).forEach((file, index) => {
                if (file.type.match('image.*')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div'); 
                        div.className = 'preview-item';
                        
                        const img = document.createElement('img'); 
                        img.src = e.target.result;
                        
                        // Membuat tombol X untuk JavaScript
                        const btnRemove = document.createElement('button');
                        btnRemove.type = 'button';
                        btnRemove.className = 'btn-remove-photo';
                        btnRemove.innerHTML = '<i class="fa-solid fa-xmark"></i>';
                        btnRemove.onclick = function() { removeFile(index); };
                        
                        div.appendChild(img); 
                        div.appendChild(btnRemove);
                        previewContainer.appendChild(div);
                    }
                    reader.readAsDataURL(file);
                }
            });
        }

        // Fungsi untuk menghapus 1 file spesifik dari input multiple
        function removeFile(index) {
            const dt = new DataTransfer();
            const files = fileInput.files;
            
            for (let i = 0; i < files.length; i++) {
                if (i !== index) {
                    dt.items.add(files[i]);
                }
            }
            
            // Perbarui file input dan DataTransfer global
            fileInput.files = dt.files;
            dataTransfer = dt;
            renderPreviews();
        }
    </script>
</body>
</html>