<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit(); }
include '../koneksi.php';

$id_user = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil data MUA berdasarkan ID
$query_mua = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = $id_user AND role = 'mua' LIMIT 1");
$mua = mysqli_fetch_assoc($query_mua);

if (!$mua) {
    header("Location: verifikasi_mua.php");
    exit();
}

// Ambil portofolio MUA
$query_porto = mysqli_query($koneksi, "SELECT * FROM portfolios WHERE id_mua = $id_user");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tinjau Mitra MUA - Admin GlamLink</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <header class="admin-navbar">
        <div class="brand-container"><h2 style="color: #4A3B32; font-weight: 700;">GlamLink</h2></div>
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="kelola_pengguna.php" class="nav-link">Kelola Pengguna</a>
            <a href="verifikasi_mua.php" class="nav-link active">Verifikasi MUA</a>
        </nav>
        <div class="user-profile">
            <span style="font-size: 13px;">Halo, <strong><?= $_SESSION['admin_nama']; ?></strong> | <a href="../logout.php" style="color: #C62828; text-decoration: none;">Logout</a></span>
        </div>
    </header>

    <main class="main-wrapper" style="max-width: 900px; margin: 30px auto; padding: 0 20px;">
        <a href="verifikasi_mua.php" style="color: #7A6F68; text-decoration: none; font-size: 13px; font-weight: 600;">← Kembali ke Daftar Verifikasi</a>

        <div style="background: white; border-radius: 16px; border: 1px solid #EFECE6; padding: 30px; margin-top: 15px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #EFECE6; padding-bottom: 20px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div style="width: 65px; height: 65px; border-radius: 50%; background: #F5F0E8; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 700; color: #4A3B32;">
                        <?= strtoupper(substr($mua['nama'], 0, 1)); ?>
                    </div>
                    <div>
                        <h1 style="font-size: 22px; margin: 0; color: #332822;"><?= htmlspecialchars($mua['nama']); ?></h1>
                        <p style="color: #7A6F68; font-size: 13px; margin: 4px 0 0;">Studio: <strong><?= htmlspecialchars($mua['nama_brand'] ?? 'Studio Mandiri'); ?></strong></p>
                    </div>
                </div>
                <div>
                    <span class="badge <?= $mua['status_verifikasi'] == 'approved' ? 'badge-aktif' : ($mua['status_verifikasi'] == 'pending' ? 'badge-pending' : 'badge-tolak'); ?>">
                        <?= ucfirst($mua['status_verifikasi']); ?>
                    </span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; font-size: 14px;">
                <div>
                    <p style="color: #7A6F68; margin-bottom: 4px;">Email</p>
                    <strong><?= htmlspecialchars($mua['email']); ?></strong>
                </div>
                <div>
                    <p style="color: #7A6F68; margin-bottom: 4px;">Nomor Telepon</p>
                    <strong><?= htmlspecialchars($mua['nomor_telepon'] ?? '-'); ?></strong>
                </div>
                <div>
                    <p style="color: #7A6F68; margin-bottom: 4px;">Lokasi / Area Operasional</p>
                    <strong><?= htmlspecialchars($mua['lokasi'] ?? '-'); ?></strong>
                </div>
                <div>
                    <p style="color: #7A6F68; margin-bottom: 4px;">Tanggal Pendaftaran</p>
                    <strong><?= date('d M Y', strtotime($mua['dibuat_pada'])); ?></strong>
                </div>
                <div style="grid-column: span 2;">
                    <p style="color: #7A6F68; margin-bottom: 4px;">Bio / Deskripsi Studio</p>
                    <p style="background: #FAFAFA; padding: 12px; border-radius: 8px; border: 1px solid #EFECE6; margin: 0; color: #4A3B32;"><?= htmlspecialchars($mua['bio'] ?? 'Belum ada bio deskripsi.'); ?></p>
                </div>
            </div>

            <h3 style="font-size: 16px; margin-bottom: 12px; color: #332822;">Galeri Portofolio MUA</h3>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px;">
                <?php if (mysqli_num_rows($query_porto) > 0) : ?>
                    <?php while($p = mysqli_fetch_assoc($query_porto)) : ?>
                    <div style="border: 1px solid #EFECE6; border-radius: 10px; overflow: hidden; background: #FAFAFA;">
                        <img src="../assets/img/<?= $p['foto']; ?>" alt="Portofolio" style="width: 100%; height: 150px; object-fit: cover;" onerror="this.src='https://via.placeholder.com/200?text=No+Image'">
                    </div>
                    <?php endwhile; ?>
                <?php else : ?>
                    <p style="color: #7A6F68; font-size: 13px; grid-column: span 3;">Belum ada foto portofolio yang diunggah oleh MUA ini.</p>
                <?php endif; ?>
            </div>

            <div style="display: flex; gap: 12px; border-top: 1px solid #EFECE6; padding-top: 20px;">
                <a href="verifikasi_mua.php?action=approve&id=<?= $mua['id_user']; ?>" style="background: #1B5E20; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13px;">Setujui Akun MUA</a>
                <a href="verifikasi_mua.php?action=reject&id=<?= $mua['id_user']; ?>" style="background: #C62828; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13px;">Tolak Akun MUA</a>
            </div>
        </div>
    </main>
</body>
</html>