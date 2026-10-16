<?php
session_start();

// Menerima data dari Langkah 2 (Hanya sebagai penanda bahwa user sudah melewati tahap 2)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_SESSION['reg_mua_step2'] = true;
}

// Pengecekan agar user tidak bisa langsung melompat ke halaman ini
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
    <title>Layanan - Langkah 3 - GlamLink</title>
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
            
            <div class="step active">
                <div class="circle">3</div>
                <p style="color: #7E4655;">Services</p>
            </div>
            <div class="line"></div>
            
            <div class="step">
                <div class="circle outline">4</div>
                <p>Review</p>
            </div>
        </div>

        <div class="card-mua">
            <div class="card-header">
                <span class="overline">LANGKAH 3 DARI 4</span>
                <h2>Tambah Layanan</h2>
                <p style="color: #8c8286; font-size: 0.95rem; margin-top: 10px;">Buat setidaknya satu layanan yang dapat ditawarkan kepada klien</p>
            </div>

            <!-- Mengarah ke Langkah 4 nantinya -->
            <form action="register_mua_step4.php" method="POST">
                
                <div class="mua-grid-section">
                    <!-- Kolom Kiri -->
                    <div class="input-group">
                        <label>Nama Layanan</label>
                        <input type="text" name="nama_layanan" class="input-plain" placeholder="Contoh: Make up Wedding" required>
                    </div>

                    <!-- Kolom Kanan -->
                    <div class="input-group">
                        <label>Kategori</label>
                        <!-- Dropdown Select -->
                        <select name="kategori" class="input-plain" required style="cursor: pointer;">
                            <option value="" disabled selected>Pilih Kategori</option>
                            <option value="Bridal">Bridal / Wedding</option>
                            <option value="Graduation">Graduation / Wisuda</option>
                            <option value="Party">Party / Pesta</option>
                            <option value="Photoshoot">Photoshoot / Studio</option>
                        </select>
                    </div>

                    <!-- Kolom Kiri Bawah -->
                    <div class="input-group">
                        <label>Harga (Rp)</label>
                        <input type="number" name="harga" class="input-plain" placeholder="750000" required>
                    </div>

                    <!-- Kolom Kanan Bawah -->
                    <div class="input-group">
                        <label>Durasi</label>
                        <input type="text" name="durasi" class="input-plain" placeholder="120 menit" required>
                    </div>
                </div>

                <!-- Textarea Penuh di Bawah -->
                <div class="input-group" style="margin-top: 10px;">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi_layanan" class="input-plain" rows="4" required></textarea>
                </div>

                <div class="form-actions-mua">
                    <!-- Kembali ke langkah 2 -->
                    <a href="register_mua_step2.php" class="btn-outline-mua">Kembali</a>
                    <button type="submit" class="btn-submit-mua">Lanjut &rarr;</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>