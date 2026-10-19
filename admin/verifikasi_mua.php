<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit(); }
include '../koneksi.php';

// Proses aksi tombol setujui/tolak
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $status = $_GET['action'] == 'approve' ? 'approved' : 'rejected';
    
    mysqli_query($koneksi, "UPDATE users SET status_verifikasi='$status' WHERE id_user=$id AND role='mua'");
    header("Location: verifikasi_mua.php");
    exit();
}

$query = mysqli_query($koneksi, "SELECT * FROM users WHERE role='mua' ORDER BY FIELD(status_verifikasi, 'pending', 'approved', 'rejected')");

// Hitung statistik verifikasi
$pending_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM users WHERE role='mua' AND status_verifikasi='pending'"))['t'];
$approved_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM users WHERE role='mua' AND status_verifikasi='approved'"))['t'];
$rejected_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM users WHERE role='mua' AND status_verifikasi='rejected'"))['t'];
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

    <main class="main-wrapper" style="max-width: 1200px; margin: 30px auto; padding: 0 20px;">
        <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Verifikasi Mitra Makeup Artist (MUA)</h1>
        <p style="color: #7A6F68; font-size: 14px; margin-bottom: 25px;">Kelola pendaftaran mitra penata rias, tinjau portofolio, dan tentukan status akses akun.</p>

        <!-- Kartu Statistik Verifikasi -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
            <div class="card-stat">
                <span class="stat-title">Menunggu Verifikasi</span>
                <div class="stat-value" style="color: #D9822B;"><?= $pending_count; ?></div>
            </div>
            <div class="card-stat">
                <span class="stat-title">Terverifikasi</span>
                <div class="stat-value" style="color: #1B5E20;"><?= $approved_count; ?></div>
            </div>
            <div class="card-stat">
                <span class="stat-title">Ditolak</span>
                <div class="stat-value" style="color: #C62828;"><?= $rejected_count; ?></div>
            </div>
        </div>

        <!-- Tabel Verifikasi MUA -->
        <div class="table-card">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>ID PENGGUNA</th>
                        <th>MITRA MUA / STUDIO</th>
                        <th>EMAIL / TELEPON</th>
                        <th>DOKUMEN</th>
                        <th>TANGGAL DAFTAR</th>
                        <th>STATUS AKUN</th>
                        <th>AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($query) > 0) : ?>
                        <?php while($row = mysqli_fetch_assoc($query)) : ?>
                        <tr>
                            <td>usr-m<?= $row['id_user']; ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row['nama']); ?></strong><br>
                                <small style="color: #7A6F68;"><?= htmlspecialchars($row['nama_brand'] ?? 'Studio Mandiri'); ?></small>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['email']); ?><br>
                                <small style="color: #7A6F68;"><?= htmlspecialchars($row['nomor_telepon'] ?? '-'); ?></small>
                            </td>
                            <td>
                                <span style="font-size: 12px; color: #1B5E20; font-weight: 600;">📄 4/4 Lengkap</span>
                            </td>
                            <td><?= date('d M Y', strtotime($row['dibuat_pada'])); ?></td>
                            <td>
                                <?php if ($row['status_verifikasi'] == 'approved') : ?>
                                    <span class="badge badge-aktif">Terverifikasi</span>
                                <?php elseif ($row['status_verifikasi'] == 'pending') : ?>
                                    <span class="badge badge-pending">Menunggu</span>
                                <?php else : ?>
                                    <span class="badge badge-tolak">Ditolak</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="detail_verifikasi.php?id=<?= $row['id_user']; ?>" class="btn-action">Tinjau</a>
                                <?php if ($row['status_verifikasi'] == 'pending') : ?>
                                    <a href="verifikasi_mua.php?action=approve&id=<?= $row['id_user']; ?>" style="background: #E8F5E9; color: #1B5E20; border: none; padding: 6px 10px; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; margin-left: 4px;">Setujui</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr><td colspan="7" style="text-align: center; padding: 30px; color: #7A6F68;">Belum ada pendaftaran MUA yang masuk.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>