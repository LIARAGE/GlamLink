<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit(); }
include '../koneksi.php';

// Menangkap input pencarian dan filter jika ada
$keyword = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$role_filter = isset($_GET['role']) ? mysqli_real_escape_string($koneksi, $_GET['role']) : '';

// Query dasar
$sql = "SELECT * FROM users WHERE role != 'admin'";

if (!empty($keyword)) {
    $sql .= " AND (nama LIKE '%$keyword%' OR email LIKE '%$keyword%' OR nomor_telepon LIKE '%$keyword%')";
}
if (!empty($role_filter) && $role_filter != 'semua') {
    $sql .= " AND role = '$role_filter'";
}

$sql .= " ORDER BY id_user DESC";
$query = mysqli_query($koneksi, $sql);

// Statistik ringkasan untuk kartu atas
$total_aktif = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM users WHERE role != 'admin'"))['t'];
$total_klien = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM users WHERE role = 'pelanggan'"))['t'];
$total_mua   = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM users WHERE role = 'mua'"))['t'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Pengguna - Admin GlamLink</title>
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

    <main class="main-wrapper" style="max-width: 1200px; margin: 30px auto; padding: 0 20px;">
        <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Kelola Pengguna</h1>
        <p style="color: #7A6F68; font-size: 14px; margin-bottom: 25px;">Kelola data akun klien dan mitra penata rias (MUA), status akses, serta riwayat interaksi.</p>

        <!-- Kartu Statistik -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
            <div class="card-stat">
                <span class="stat-title">Total Pengguna Aktif</span>
                <div class="stat-value"><?= number_format($total_aktif); ?></div>
            </div>
            <div class="card-stat">
                <span class="stat-title">Total Klien Terdaftar</span>
                <div class="stat-value"><?= number_format($total_klien); ?></div>
            </div>
            <div class="card-stat">
                <span class="stat-title">Total MUA Terdaftar</span>
                <div class="stat-value"><?= number_format($total_mua); ?></div>
            </div>
        </div>

        <!-- Bar Pencarian & Filter -->
        <form method="GET" action="" style="display: flex; gap: 15px; margin-bottom: 25px; background: white; padding: 15px; border-radius: 14px; border: 1px solid #EFECE6;">
            <input type="text" name="cari" value="<?= htmlspecialchars($keyword); ?>" placeholder="Cari berdasarkan nama, email, atau nomor telepon..." style="flex: 1; padding: 10px 15px; border-radius: 8px; border: 1px solid #D7CCC8; outline: none; font-size: 13px;">
            <select name="role" style="padding: 10px 15px; border-radius: 8px; border: 1px solid #D7CCC8; background: white; font-size: 13px; color: #4A3B32;" onchange="this.form.submit()">
                <option value="semua">Semua Tipe (Klien & MUA)</option>
                <option value="pelanggan" <?= $role_filter == 'pelanggan' ? 'selected' : ''; ?>>Klien / Pelanggan</option>
                <option value="mua" <?= $role_filter == 'mua' ? 'selected' : ''; ?>>Mitra MUA</option>
            </select>
            <button type="submit" style="background: #4A3B32; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 13px;">Cari</button>
        </form>

        <!-- Tabel Pengguna -->
        <div class="table-card">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>ID PENGGUNA</th>
                        <th>NAMA & BRAND</th>
                        <th>EMAIL / TELEPON</th>
                        <th>TIPE PENGGUNA</th>
                        <th>TANGGAL BERGABUNG</th>
                        <th>STATUS AKUN</th>
                        <th>AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($query) > 0) : ?>
                        <?php while($row = mysqli_fetch_assoc($query)) : ?>
                        <tr>
                            <td>usr-<?= $row['id_user']; ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row['nama']); ?></strong><br>
                                <small style="color: #7A6F68;"><?= htmlspecialchars($row['nama_brand'] ?? '-'); ?></small>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['email']); ?><br>
                                <small style="color: #7A6F68;"><?= htmlspecialchars($row['nomor_telepon'] ?? '-'); ?></small>
                            </td>
                            <td>
                                <span class="badge-role"><?= $row['role'] == 'pelanggan' ? 'Klien' : 'MUA'; ?></span>
                            </td>
                            <td><?= date('d M Y', strtotime($row['dibuat_pada'])); ?></td>
                            <td>
                                <span class="badge badge-aktif">Aktif</span>
                            </td>
                            <td>
                                <a href="detail_pengguna.php?id=<?= $row['id_user']; ?>" class="btn-action">Detail</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr><td colspan="7" style="text-align: center; padding: 30px; color: #7A6F68;">Tidak ada data pengguna yang ditemukan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>