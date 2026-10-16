<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Simpan hanya field layanan yang diizinkan
    $_SESSION['reg_mua_step3'] = array_intersect_key($_POST, array_flip(['nama_layanan', 'kategori', 'harga', 'durasi', 'deskripsi_layanan']));
}
if (!isset($_SESSION['reg_mua_step3'])) { header('Location: register_mua_step3.php'); exit(); }
if (!isset($_SESSION['reg_mua_step1'])) { header("Location: register_form.php?role=mua"); exit(); }

$data_step1 = $_SESSION['reg_mua_step1'];
$portfolios = $_SESSION['reg_mua_step2']['portfolio'] ?? [];
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
    <?php include 'navbar.php'; ?>
    <main class="form-container">
        <div class="stepper-wrapper">
            <div class="step"><div class="circle outline"><i class="fa-solid fa-check"></i></div><p>Your Profile</p></div><div class="line" style="background-color: #7E4655;"></div>
            <div class="step"><div class="circle outline"><i class="fa-solid fa-check"></i></div><p>Portfolio</p></div><div class="line" style="background-color: #7E4655;"></div>
            <div class="step"><div class="circle outline"><i class="fa-solid fa-check"></i></div><p>Services</p></div><div class="line" style="background-color: #7E4655;"></div>
            <div class="step active"><div class="circle">4</div><p style="color: #7E4655;">Review</p></div>
        </div>

        <div class="card-mua">
            <div class="card-header">
                <span class="overline">LANGKAH 4 DARI 4</span>
                <h2>Review Profilmu!</h2>
            </div>
            <div class="review-box">
                <div class="review-top-bg">
                    <div class="review-profile">
                        <div class="review-photo" style="overflow: hidden;">
                            <?php if(isset($data_step1['foto_profil'])): ?>
                                <img src="assets/img/<?php echo htmlspecialchars($data_step1['foto_profil']); ?>" style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?><i class="fa-solid fa-user"></i><?php endif; ?>
                        </div>
                        <div class="review-info">
                            <h3><?php echo htmlspecialchars($data_step1['nama_brand']); ?></h3>
                            <span class="review-location"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($data_step1['lokasi']); ?></span>
                            <p class="review-bio"><?php echo htmlspecialchars($data_step1['bio']); ?></p>
                        </div>
                    </div>
                </div>
                <div class="review-divider"></div>
                <div class="review-bottom">
                    <h4>Portfolio</h4>
                    <div class="review-portfolio-grid">
                        <?php if(count($portfolios) > 0): ?>
                            <?php foreach($portfolios as $port): ?>
                                <div class="review-port-item" style="background-image: url('assets/img/<?php echo htmlspecialchars($port); ?>'); background-size: cover; background-position: center;"></div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="review-port-item">Belum ada foto</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <form action="actions/register_mua_act.php" method="POST">
                <div class="form-actions-mua">
                    <a href="register_mua_step3.php" class="btn-outline-mua">Kembali</a>
                    <button type="submit" name="selesai" class="btn-submit-mua">Selesaikan Pendaftaran</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>