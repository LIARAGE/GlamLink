<?php
session_start();
include '../koneksi.php';

// Logika Proses Verifikasi (Terima/Tolak)
if (isset($_Action) && $_GET['action'] == 'update') {
    $id = $_GET['id'];
    $status = $_GET['status']; // Terverifikasi / Ditolak
    mysqli_query($conn, "UPDATE users SET status_akun='$status' WHERE id='$id'");
    header("Location: verifikasi_mua.php");
    exit();
}

$query = mysqli_query($conn, "SELECT * FROM users WHERE role='mua'");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Verifikasi MUA - Admin GlamLink</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <header class="admin-navbar">
        <div class="logo"><strong>GlamLink</strong></div>
        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="kelola_pengguna.php">Kelola Pengguna</a>
            <a href="verifikasi_mua.php" class="active">Verifikasi MUA</a>
        </nav>
        <div class="admin-profile"><span>Admin</span></div>
    </header>

    <main class="main-container">
        <h1>Verifikasi Mitra Makeup Artist (MUA)</h1>
        <p>Kelola data akun klien dan mitra penata rias (MUA), status akses, serta riwayat interaksi.</p>

        <div style="background: #fff; border-radius: 16px; padding: 20px; margin-top: 30px; border: 1px solid #EFECE6;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="border-bottom: 1px solid #EFECE6; color: #7A6F68; font-size: 14px;">
                        <th style="padding: 12px;">ID PENGGUNA</th>
                        <th style="padding: 12px;">MUA</th>
                        <th style="padding: 12px;">EMAIL</th>
                        <th style="padding: 12px;">DOKUMEN</th>
                        <th style="padding: 12px;">STATUS AKUN</th>
                        <th style="padding: 12px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($query)) : ?>
                    <tr style="border-bottom: 1px solid #F7F5F0;">
                        <td style="padding: 15px; font-weight: 500;"><?= $row['id_pengguna']; ?></td>
                        <td style="padding: 15px;">
                            <strong><?= $row['nama']; ?></strong><br>
                            <small style="color: #7A6F68;"><?= $row['lokasi']; ?></small>
                        </td>
                        <td style="padding: 15px;"><?= $row['email']; ?><br><small><?= $row['telepon']; ?></small></td>
                        <td style="padding: 15px;"><span style="color: #2E7D32; font-size: 13px;">📄 <?= $row['dokumen_status']; ?></span></td>
                        <td style="padding: 15px;">
                            <span class="badge-status badge-<?= strtolower($row['status_akun']); ?>">
                                <?= $row['status_akun']; ?>
                            </span>
                        </td>
                        <td style="padding: 15px;">
                            <?php if($row['status_akun'] == 'Menunggu') : ?>
                                <a href="verifikasi_mua.php?action=update&id=<?= $row['id']; ?>&status=Terverifikasi" class="btn-action" style="background:#2E7D32; color:#fff;">Terima</a>
                                <a href="verifikasi_mua.php?action=update&id=<?= $row['id']; ?>&status=Ditolak" class="btn-action" style="background:#C62828; color:#fff;">Tolak</a>
                            <?php else: ?>
                                <button class="btn-action">Tinjau</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>