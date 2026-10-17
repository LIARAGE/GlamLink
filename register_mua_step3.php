<?php
session_start();
require_once __DIR__ . '/Includes/upload.php';

// PERBAIKAN: Pastikan session step 2 adalah array, bukan sisa boolean (true) dari versi sebelumnya
if (!isset($_SESSION['reg_mua_step2']) || !is_array($_SESSION['reg_mua_step2'])) {
    $_SESSION['reg_mua_step2'] = [];
}

if (!isset($_SESSION['reg_mua_step1'])) { header("Location: register_form.php?role=mua"); exit(); }

// Form langkah 2 mengirim foto ke halaman ini
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $foto_ok = $_SESSION['reg_mua_step2']['portfolio'] ?? [];
    $gagal = '';
    if (isset($_FILES['portfolio']) && !empty($_FILES['portfolio']['name'][0])) {
        foreach ($_FILES['portfolio']['name'] as $k => $nama_asli) {
            $f = simpan_gambar([
                'tmp_name' => $_FILES['portfolio']['tmp_name'][$k],
                'error'    => $_FILES['portfolio']['error'][$k],
                'size'     => $_FILES['portfolio']['size'][$k],
            ], 'porto', 10, $alasan);
            if ($f) { $foto_ok[] = $f; } else { $gagal = $alasan; }
        }
        $_SESSION['reg_mua_step2']['portfolio'] = $foto_ok;
    }
    // Kembali ke langkah 2 (dengan pesan) jika ada foto yang gagal atau belum ada foto sama sekali
    if ($gagal !== '') { header('Location: register_mua_step2.php?err=foto&alasan=' . urlencode($gagal)); exit(); }
    if (count($foto_ok) === 0) { header('Location: register_mua_step2.php?err=kosong'); exit(); }
    // Redirect agar refresh halaman TIDAK mengunggah ulang foto yang sama
    header('Location: register_mua_step3.php'); exit();
}
if (!isset($_SESSION['reg_mua_step1'])) { header("Location: register_form.php?role=mua"); exit(); }
$s3 = isset($_SESSION['reg_mua_step3']) ? $_SESSION['reg_mua_step3'] : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layanan - Langkah 3 - GlamLink</title>
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
            <div class="step active"><div class="circle">3</div><p style="color: #7E4655;">Services</p></div><div class="line"></div>
            <div class="step"><div class="circle outline">4</div><p>Review</p></div>
        </div>

        <div class="card-mua">
            <div class="card-header">
                <span class="overline">LANGKAH 3 DARI 4</span>
                <h2>Tambah Layanan</h2>
            </div>
            <form action="register_mua_step4.php" method="POST">
                <div class="mua-grid-section">
                    <div class="input-group">
                        <label>Nama Layanan</label>
                        <input type="text" name="nama_layanan" class="input-plain" value="<?php echo htmlspecialchars($s3['nama_layanan'] ?? ''); ?>" required>
                    </div>
                    <div class="input-group">
                        <label>Kategori</label>
                        <select name="kategori" class="input-plain" required>
                            <option value="" disabled <?php echo empty($s3['kategori']) ? 'selected' : ''; ?>>Pilih Kategori</option>
                            <option value="Bridal" <?php echo ($s3['kategori']??'') == 'Bridal' ? 'selected' : ''; ?>>Bridal / Wedding</option>
                            <option value="Graduation" <?php echo ($s3['kategori']??'') == 'Graduation' ? 'selected' : ''; ?>>Graduation / Wisuda</option>
                            <option value="Party" <?php echo ($s3['kategori']??'') == 'Party' ? 'selected' : ''; ?>>Party / Pesta</option>
                            <option value="Photoshoot" <?php echo ($s3['kategori']??'') == 'Photoshoot' ? 'selected' : ''; ?>>Photoshoot / Studio</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Harga (Rp)</label>
                        <input type="number" name="harga" class="input-plain" value="<?php echo htmlspecialchars($s3['harga'] ?? ''); ?>" required>
                    </div>
                    <div class="input-group">
                        <label>Durasi</label>
                        <input type="text" name="durasi" class="input-plain" value="<?php echo htmlspecialchars($s3['durasi'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="input-group" style="margin-top: 10px;">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi_layanan" class="input-plain" rows="4" required><?php echo htmlspecialchars($s3['deskripsi_layanan'] ?? ''); ?></textarea>
                </div>
                <div class="form-actions-mua">
                    <a href="register_mua_step2.php" class="btn-outline-mua">Kembali</a>
                    <button type="submit" class="btn-submit-mua">Lanjut &rarr;</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>