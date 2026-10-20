<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit(); }
include '../koneksi.php';

$keyword = isset($_GET['cari']) ? trim(mysqli_real_escape_string($koneksi, $_GET['cari'])) : '';
$role_filter = isset($_GET['role']) ? mysqli_real_escape_string($koneksi, $_GET['role']) : 'semua';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, $_GET['status']) : 'semua';
$periode = isset($_GET['periode']) ? mysqli_real_escape_string($koneksi, $_GET['periode']) : 'bulan_ini';

$sql = "SELECT * FROM users WHERE role != 'admin'";

if ($keyword !== '') {
    $sql .= " AND (nama LIKE '%$keyword%' OR email LIKE '%$keyword%' OR nomor_telepon LIKE '%$keyword%' OR nama_brand LIKE '%$keyword%')";
}
if ($role_filter !== '' && $role_filter !== 'semua') {
    $sql .= " AND role = '$role_filter'";
}
if ($status_filter !== '' && $status_filter !== 'semua') {
    if ($status_filter == 'aktif') {
        $sql .= " AND (role = 'pelanggan' OR (role = 'mua' AND status_verifikasi = 'approved'))";
    } elseif ($status_filter == 'pending') {
        $sql .= " AND (role = 'mua' AND status_verifikasi = 'pending')";
    } elseif ($status_filter == 'rejected') {
        $sql .= " AND (role = 'mua' AND status_verifikasi = 'rejected')";
    }
}

// Filter Periode Tanggal
if ($periode == 'bulan_ini') {
    $sql .= " AND MONTH(dibuat_pada) = 10 AND YEAR(dibuat_pada) = 2026";
} elseif ($periode == 'bulan_lalu') {
    $sql .= " AND MONTH(dibuat_pada) = 9 AND YEAR(dibuat_pada) = 2026";
} elseif ($periode == 'tahun_ini') {
    $sql .= " AND YEAR(dibuat_pada) = 2026";
}

$sql .= " ORDER BY id_user DESC";
$query = mysqli_query($koneksi, $sql);

$nama_role = ['pelanggan' => 'Klien / Pelanggan', 'mua' => 'Mitra MUA'];
$nama_status = ['aktif' => 'Aktif / Terverifikasi', 'pending' => 'Menunggu (Pending)', 'rejected' => 'Ditolak (Rejected)'];

$label_role_terpilih = isset($nama_role[$role_filter]) ? $nama_role[$role_filter] : '';
$label_status_terpilih = isset($nama_status[$status_filter]) ? $nama_status[$status_filter] : '';

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
    <style>
        .kpi-icon-box {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }
        .filter-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .filter-input-wrapper svg.input-icon {
            position: absolute;
            left: 14px;
            color: #7A6F68;
            pointer-events: none;
        }
    </style>
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
        
        <!-- Header & Select Period Sejajar (Sesuai Referensi Gambar) -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
            <div>
                <h1 style="font-size: 24px; font-weight: 700; margin: 0 0 4px 0;">Kelola Pengguna</h1>
                <p style="color: #7A6F68; font-size: 14px; margin: 0;">Kelola data akun klien dan mitra penata rias (MUA), status akses, serta riwayat interaksi.</p>
            </div>
            
            <!-- Widget Select Period di Pojok Kanan Atas -->
            <div>
                <span style="font-size: 11px; font-weight: 600; color: #7A6F68; display: block; margin-bottom: 4px;">Select Period</span>
                <form method="GET" action="kelola_pengguna.php">
                    <div style="position: relative;">
                        <select name="periode" onchange="this.form.submit()" style="height: 40px; padding: 0 35px 0 36px; border-radius: 10px; border: 1px solid #D7CCC8; background: #FFFFFF; font-size: 13px; color: #4A3B32; font-family: 'Plus Jakarta Sans', sans-serif; outline: none; cursor: pointer; appearance: none; -webkit-appearance: none;">
                            <option value="bulan_ini" <?= $periode == 'bulan_ini' ? 'selected' : ''; ?>>Bulan ini (Oktober 2026)</option>
                            <option value="bulan_lalu" <?= $periode == 'bulan_lalu' ? 'selected' : ''; ?>>Bulan lalu (September 2026)</option>
                            <option value="tahun_ini" <?= $periode == 'tahun_ini' ? 'selected' : ''; ?>>Tahun ini (2026)</option>
                        </select>
                        <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #7A6F68;" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #7A6F68; font-size: 10px;">▼</span>
                    </div>
                </form>
            </div>
        </div>

        <!-- Kartu Statistik (KPI Cards) -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
            <div class="card-stat">
                <div class="kpi-icon-box" style="background: #E8F5E9; color: #1B5E20;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <span class="stat-title">Total Pengguna Aktif</span>
                <div class="stat-value"><?= number_format($total_aktif); ?></div>
            </div>
            <div class="card-stat">
                <div class="kpi-icon-box" style="background: #F5F0E8; color: #4A3B32;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <span class="stat-title">Total Klien Terdaftar</span>
                <div class="stat-value"><?= number_format($total_klien); ?></div>
            </div>
            <div class="card-stat">
                <div class="kpi-icon-box" style="background: #FEF6E6; color: #D9822B;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <span class="stat-title">Total MUA Terdaftar</span>
                <div class="stat-value"><?= number_format($total_mua); ?></div>
            </div>
        </div>

        <!-- Bar Pencarian & Filter dengan Ikon -->
        <form method="GET" action="kelola_pengguna.php" style="display: flex; align-items: center; gap: 12px; background: white; padding: 15px 20px; border-radius: 16px; border: 1px solid #EFECE6; margin-bottom: 25px; flex-wrap: wrap;">
            
            <!-- Input Pencarian dengan Ikon Kaca Pembesar -->
            <div class="filter-input-wrapper" style="flex: 2; min-width: 240px;">
                <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="cari" value="<?= htmlspecialchars($keyword); ?>" placeholder="Cari berdasarkan nama, email, atau nomor telepon..." style="width: 100%; height: 44px; padding: 0 16px 0 38px; border-radius: 10px; border: 1px solid #D7CCC8; outline: none; font-size: 13px; background: #FFFFFF; font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; color: #4A3B32;">
            </div>

            <!-- Filter Tipe Pengguna dengan Ikon Filter -->
            <div class="filter-input-wrapper" style="position: relative; flex: 1; min-width: 190px; max-width: 220px;">
                <svg class="input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                <select name="role" style="width: 100%; height: 44px; padding: 0 36px 0 36px; border-radius: 10px; border: 1px solid #D7CCC8; background: #FFFFFF; font-size: 13px; color: #4A3B32; font-family: 'Plus Jakarta Sans', sans-serif; outline: none; cursor: pointer; box-sizing: border-box; appearance: none; -webkit-appearance: none;">
                    <option value="semua" <?= $role_filter == 'semua' ? 'selected' : ''; ?>>Semua Tipe (Klien & MUA)</option>
                    <option value="pelanggan" <?= $role_filter == 'pelanggan' ? 'selected' : ''; ?>>Klien / Pelanggan</option>
                    <option value="mua" <?= $role_filter == 'mua' ? 'selected' : ''; ?>>Mitra MUA</option>
                </select>
                <span style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #7A6F68; font-size: 11px;">▼</span>
            </div>

            <!-- Filter Status Akun -->
            <div class="filter-input-wrapper" style="position: relative; flex: 1; min-width: 170px; max-width: 200px;">
                <svg class="input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <select name="status" style="width: 100%; height: 44px; padding: 0 36px 0 36px; border-radius: 10px; border: 1px solid #D7CCC8; background: #FFFFFF; font-size: 13px; color: #4A3B32; font-family: 'Plus Jakarta Sans', sans-serif; outline: none; cursor: pointer; box-sizing: border-box; appearance: none; -webkit-appearance: none;">
                    <option value="semua" <?= $status_filter == 'semua' ? 'selected' : ''; ?>>Semua Status</option>
                    <option value="aktif" <?= $status_filter == 'aktif' ? 'selected' : ''; ?>>Aktif / Terverifikasi</option>
                    <option value="pending" <?= $status_filter == 'pending' ? 'selected' : ''; ?>>Menunggu (Pending)</option>
                    <option value="rejected" <?= $status_filter == 'rejected' ? 'selected' : ''; ?>>Ditolak (Rejected)</option>
                </select>
                <span style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #7A6F68; font-size: 11px;">▼</span>
            </div>

            <button type="submit" style="height: 44px; background: #4A3B32; color: white; border: none; padding: 0 26px; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 13px; font-family: 'Plus Jakarta Sans', sans-serif; white-space: nowrap;">Cari</button>
            
            <?php if ($keyword !== '' || $role_filter !== 'semua' || $status_filter !== 'semua') : ?>
                <a href="kelola_pengguna.php" style="height: 44px; display: inline-flex; align-items: center; justify-content: center; background: #EFECE6; color: #4A3B32; padding: 0 16px; border-radius: 10px; text-decoration: none; font-size: 13px; font-weight: 600; white-space: nowrap; box-sizing: border-box;">Reset</a>
            <?php endif; ?>
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
                            <td>usr-<?= $row['role'] == 'pelanggan' ? 'c' : 'm'; ?><?= $row['id_user']; ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row['nama']); ?></strong><br>
                                <small style="color: #7A6F68;"><?= htmlspecialchars($row['nama_brand'] ?? ($row['lokasi'] ?? '-')); ?></small>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['email']); ?><br>
                                <small style="color: #7A6F68;"><?= htmlspecialchars($row['nomor_telepon'] ?? '-'); ?></small>
                            </td>
                            <td>
                                <?php if ($row['role'] == 'pelanggan') : ?>
                                    <span style="display: inline-flex; align-items: center; gap: 5px; background: #E3F2FD; color: #1565C0; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> Klien
                                    </span>
                                <?php else : ?>
                                    <span style="display: inline-flex; align-items: center; gap: 5px; background: #FCE4EC; color: #C2185B; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg> MUA
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d M Y', strtotime($row['dibuat_pada'])); ?></td>
                            <td>
                                <?php if ($row['role'] == 'pelanggan' || $row['status_verifikasi'] == 'approved') : ?>
                                    <span class="badge badge-aktif">● Aktif</span>
                                <?php elseif ($row['status_verifikasi'] == 'pending') : ?>
                                    <span class="badge badge-pending">● Menunggu</span>
                                <?php else : ?>
                                    <span class="badge badge-tolak">● Ditolak</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="detail_pengguna.php?id=<?= $row['id_user']; ?>" class="btn-action">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Detail
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <!-- Empty State dengan Ikon SVG (Tanpa Emoji) -->
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 50px 20px; color: #7A6F68;">
                                <div style="display: flex; justify-content: center; margin-bottom: 12px; color: #D7CCC8;">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                </div>
                                <div style="font-size: 14px; font-weight: 600; color: #4A3B32; margin-bottom: 4px;">Tidak Ada Data Ditemukan</div>
                                <div style="font-size: 12px; color: #7A6F68;">
                                    <?php
                                    $info_filter = [];
                                    if ($keyword !== '') $info_filter[] = 'kata kunci <strong>"' . htmlspecialchars($keyword) . '"</strong>';
                                    if ($role_filter !== 'semua') $info_filter[] = 'tipe <strong>' . $label_role_terpilih . '</strong>';
                                    if ($status_filter !== 'semua') $info_filter[] = 'status <strong>' . $label_status_terpilih . '</strong>';
                                    
                                    if (!empty($info_filter)) {
                                        echo "Tidak ada data pengguna yang cocok dengan " . implode(' dan ', $info_filter) . ".";
                                    } else {
                                        echo "Belum ada data pengguna yang terdaftar pada periode ini.";
                                    }
                                    ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>