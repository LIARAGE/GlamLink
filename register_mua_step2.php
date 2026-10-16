<?php
session_start();

// Menerima data dari Langkah 1 dan menyimpannya ke session sementara
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['role']) && $_POST['role'] == 'mua') {
    $_SESSION['reg_mua_step1'] = $_POST;
    // Catatan: Untuk foto profil (file), logika upload sesungguhnya akan kita bahas di tahap akhir (Review).
}

// Jika user langsung mengakses halaman ini tanpa lewat langkah 1, kembalikan ke awal
if (!isset($_SESSION['reg_mua_step1'])) {
    header("Location: register_form.php?role=mua");
    exit();
}
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
        
        <!-- Stepper -->
        <div class="stepper-wrapper">
            <div class="step">
                <div class="circle outline"><i class="fa-solid fa-check"></i></div>
                <p>Your Profile</p>
            </div>
            <div class="line" style="background-color: #7E4655;"></div> <!-- Garis aktif -->
            <div class="step active">
                <div class="circle">2</div>
                <p style="color: #7E4655;">Portfolio</p>
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
                <span class="overline">LANGKAH 2 DARI 4</span>
                <h2>Tampilkan Karyamu</h2>
            </div>

            <!-- Mengarah ke Langkah 3 nantinya -->
            <form action="register_mua_step3.php" method="POST" enctype="multipart/form-data">
                
                <!-- Area Upload Portfolio -->
                <div class="upload-area">
                    <i class="fa-regular fa-image" style="font-size: 40px; color: #9A6372; margin-bottom: 15px;"></i>
                    <p style="font-weight: 600; color: #2b2326; margin-bottom: 5px;">Letakkan foto portfolio disini</p>
                    <p style="font-size: 0.85rem; color: #8c8286; margin-bottom: 20px;">or click to browse - JPG or PNG</p>
                    
                    <!-- Label ini bertindak sebagai tombol yang memicu input file tersembunyi -->
                    <label for="portfolio_input" class="btn-outline-mua" style="display: inline-block; padding: 10px 25px; cursor: pointer; border-radius: 12px;">Pilih Foto</label>
                    <input type="file" name="portfolio[]" id="portfolio_input" multiple accept="image/jpeg, image/png" style="display: none;">
                </div>

                <!-- Wadah Preview Gambar -->
                <div class="portfolio-preview-grid" id="preview_container">
                    <!-- Gambar akan muncul di sini secara otomatis melalui JavaScript di bawah -->
                </div>

                <div class="form-actions-mua">
                    <!-- Kembali ke langkah 1 -->
                    <a href="register_form.php?role=mua" class="btn-outline-mua">Kembali</a>
                    <button type="submit" class="btn-submit-mua">Lanjut &rarr;</button>
                </div>
            </form>
        </div>
    </main>

    <!-- JavaScript Sederhana untuk menampilkan Pratinjau (Preview) Foto -->
    <script>
        const fileInput = document.getElementById('portfolio_input');
        const previewContainer = document.getElementById('preview_container');

        fileInput.addEventListener('change', function() {
            previewContainer.innerHTML = ''; // Kosongkan preview sebelumnya
            
            if (this.files) {
                Array.from(this.files).forEach(file => {
                    if (file.type.match('image.*')) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            // Membuat elemen div untuk setiap gambar
                            const div = document.createElement('div');
                            div.className = 'preview-item';
                            
                            // Membuat elemen gambar
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            
                            // Menambahkan gambar ke dalam div, lalu div ke container
                            div.appendChild(img);
                            previewContainer.appendChild(div);
                        }
                        
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
    </script>
</body>
</html>