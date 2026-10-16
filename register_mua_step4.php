<?php
session_start();

// Simpan data dari Langkah 3 ke session
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_SESSION['reg_mua_step3'] = $_POST;
}

// Pastikan user melewati Langkah 1
if (!isset($_SESSION['reg_mua_step1'])) {
    header("Location: register_form.php?role=mua");
    exit();
}

// Ambil data dari session untuk ditampilkan di Review
$data_step1 = $_SESSION['reg_mua_step1'];
$nama_brand = htmlspecialchars($data_step1['nama_brand'] ?? 'Nama Brand');
$lokasi = htmlspecialchars($data_step1['lokasi'] ?? 'Lokasi');
$bio = htmlspecialchars($data_step1['bio'] ?? 'Bio deskripsi...');

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review - Langkah 4 - GlamLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/register_form.css">
</head>
<body>

    <!-- Memanggil Navbar -->
    <?php include 'navbar.php'; ?>

    <main class="form-container">
        
        <!-- Stepper -->
        <div class="stepper-wrapper">
            <div class="step">
                <div class="circle outline"><i class="fa-solid fa-check"></i></div>
                <p>Your Profile</p>
            </div>
            <div class="line" style="background-color: #7E4655;"></div>
            
            <div class="step">
                <div class="circle outline"><i class="fa-solid fa-check"></i></div>
                <p>Portfolio</p>
            </div>
            <div class="line" style="background-color: #7E4655;"></div>
            
            <div class="step">
                <div class="circle outline"><i class="fa-solid fa-check"></i></div>
                <p>Services</p>
            </div>
            <div class="line" style="background-color: #7E4655;"></div>
            
            <div class="step active">
                <div class="circle">4</div>
                <p style="color: #7E4655;">Review</p>
            </div>
        </div>

        <div class="card-mua">
            <div class="card-header">
                <span class="overline">LANGKAH 4 DARI 4</span>
                <h2>Review Profilmu!</h2>
                <p style="color: #8c8286; font-size: 0.95rem; margin-top: 10px;">Berikut tampilan halaman profil Anda yang akan dilihat oleh calon klien di GlamLink.</p>
            </div>

            <!-- Card Review Tampilan Klien -->
            <div class="review-box">
                <div class="review-top-bg">
                    <div class="review-profile">
                        <!-- Placeholder Foto Profil -->
                        <div class="review-photo">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div class="review-info">
                            <h3><?php echo $nama_brand; ?></h3>
                            <span class="review-location"><i class="fa-solid fa-location-dot"></i> <?php echo $lokasi; ?></span>
                            <p class="review-bio"><?php echo $bio; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="review-divider"></div>

                <div class="review-bottom">
                    <h4>Portfolio</h4>
                    <div class="review-portfolio-grid">
                        <!-- Placeholder Portfolio menggunakan warna abu-abu -->
                        <div class="review-port-item">Bridal</div>
                        <div class="review-port-item">Soft Glam</div>
                        <div class="review-port-item">Soft Glam</div>
                    </div>
                </div>
            </div>

            <!-- Tombol Final Submit -->
            <form action="actions/register_mua_act.php" method="POST">
                <div class="form-actions-mua">
                    <!-- Kembali ke langkah 3 -->
                    <a href="register_mua_step3.php" class="btn-outline-mua">Kembali</a>
                    <button type="submit" name="selesai" class="btn-submit-mua">Selesaikan Pendaftaran</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>