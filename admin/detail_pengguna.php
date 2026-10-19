<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit(); }
include '../koneksi.php';

$id_user = isset($_GET['id']) ? intval($_GET['id']) : 0;

$query_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = $id_user LIMIT 1");
$user = mysqli_fetch_assoc($query_user);

if (!$user) {
    header("Location: kelola_pengguna.php");
    exit();
}

// Ambil riwayat booking user
$query_booking = mysqli_query($koneksi, "
    SELECT b.*, s.nama_layanan 
    FROM bookings b 
    JOIN services s ON b.id_layanan = s.id_service 
    WHERE b.id_pelanggan = $id_user OR b.id_mua = $id_user
    ORDER BY b.id_booking DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Pengguna - Admin GlamLink</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <header class="admin-navbar">
        <div class="brand-container"><h2 style="color: #4A3B32; font-weight: 700;">GlamLink</h2></div>
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="kelola_pengguna.php" class="nav-link active">Kelola Pengguna</a>
            <a href="verifikasi_mua.php" class="nav-link">Verifikasi MUA</a>
        </nav>
        <div class="user-profile">
            <span style="font-size: 13px;">Halo, <strong><?= $_SESSION['admin_nama']; ?></strong> | <a href="../logout.php" style="color: #C62828; text-decoration: none;">Logout</a></span>
        </div>
    </header>

    <main class="main-wrapper" style="max-width: 900px; margin: 30px auto; padding: 0 20px;">
        <a href="kelola_pengguna.php" style="color: #7A6F68; text-decoration: none; font-size: 13px; font-weight: 600;">← Kembali ke Kelola Pengguna</a>

        <div style="background: white; border-radius: 16px; border: 1px solid #EFECE6; padding: 30px; margin-top: 15px;">
            <div style="display: flex; align-items: center; gap: 20px; border-bottom: 1px solid #EFECE6; padding-bottom: 20px; margin-bottom: 20px;">
                <div style="width: 65px; height: 65px; border-radius: 50%; background: #F5F0E8; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 700; color: #4A3B32;">
                    <?= strtoupper(substr($user['nama'], 0, 1)); ?>
                </div>
                <div>
                    <h1 style="font-size: 22px; margin: 0; color: #332822;"><?= htmlspecialchars($user['nama']); ?></h1>
                    <p style="color: #7A6F68; font-size: 13px; margin: 4px 0 0;">ID: usr-<?= $user['id_user']; ?> | Tipe: <span style="text-transform: uppercase; font-weight: 600;"><?= $user['role']; ?></span></p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; font-size: 14px;">
                <div>
                    <p style="color: #7A6F68; margin-bottom: 4px;">Email</p>
                    <strong><?= htmlspecialchars($user['email']); ?></strong>
                </div>
                <div>
                    <p style="color: #7A6F68; margin-bottom: 4px;">Nomor Telepon</p>
                    <strong><?= htmlspecialchars($user['nomor_telepon'] ?? '-'); ?></strong>
                </div>
                <div>
                    <p style="color: #7A6F68; margin-bottom: 4px;">Lokasi</p>
                    <strong><?= htmlspecialchars($user['lokasi'] ?? '-'); ?></strong>
                </div>
                <div>
                    <p style="color: #7A6F68; margin-bottom: 4px;">Tanggal Bergabung</p>
                    <strong><?= date('d M Y', strtotime($user['dibuat_pada'])); ?></strong>
                </div>
            </div>

            <h3 style="font-size: 16px; margin-bottom: 12px; color: #332822;">Riwayat Transaksi Booking</h3>
            <div class="table-card" style="margin-top: 0;">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Layanan</th>
                            <th>Tanggal Acara</th>
                            <th>Total Harga</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($query_booking) > 0) : ?>
                            <?php while($b = mysqli_fetch_assoc($query_booking)) : ?>
                            <tr>
                                <td style="font-weight: 600;"><?= $b['no_invoice']; ?></td>
                                <td><?= htmlspecialchars($b['nama_layanan']); ?></td>
                                <td><?= $b['tanggal_booking']; ?></td>
                                <td>Rp <?= number_format($b['total_harga'], 0, ',', '.'); ?></td>
                                <td><span style="font-weight: 600; text-transform: capitalize;"><?= $b['status']; ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <tr><td colspan="5" style="text-align: center; padding: 25px; color: #7A6F68;">Belum ada riwayat transaksi booking untuk pengguna ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>