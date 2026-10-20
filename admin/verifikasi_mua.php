<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit(); }
include '../koneksi.php';

$keyword = isset($_GET['cari']) ? trim(mysqli_real_escape_string($koneksi, $_GET['cari'])) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, $_GET['status']) : 'semua';
$periode = isset($_GET['periode']) ? mysqli_real_escape_string($koneksi, $_GET['periode']) : 'bulan_ini';

$sql = "SELECT * FROM users WHERE role = 'mua'";

if ($keyword !== '') {
    $sql .= " AND (nama LIKE '%$keyword%' OR email LIKE '%$keyword%' OR nomor_telepon LIKE '%$keyword%' OR nama_brand LIKE '%$keyword%')";
}
if ($status_filter !== '' && $status_filter !== 'semua') {
    $sql .= " AND status_verifikasi = '$status_filter'";
}

if ($periode == 'bulan_ini') {
    $sql .= " AND MONTH(dibuat_pada) = 10 AND YEAR(dibuat_pada) = 2026";
} elseif ($periode == 'bulan_lalu') {
    $sql .= " AND MONTH(dibuat_pada) = 9 AND YEAR(dibuat_pada) = 2026";
} elseif ($periode == 'tahun_ini') {
    $sql .= " AND YEAR(dibuat_pada) = 2026";
}

$sql .= " ORDER BY FIELD(status_verifikasi, 'pending', 'approved', 'rejected'), id_user DESC";
$query = mysqli_query($koneksi, $sql);

$nama_status = ['pending' => 'Menunggu (Pending)', 'approved' => 'Terverifikasi (Approved)', 'rejected' => 'Ditolak (Rejected)'];
$label_status_terpilih = isset($nama_status[$status_filter]) ? $nama_status[$status_filter] : '';

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
            <a href="kelola_pengguna.php" class="nav-link">Kelola Pengguna</a>
            <a href="verifikasi_mua.php" class="nav-link active">Verifikasi MUA</a>
        </nav>
        <div class="user-profile">
            <span style="font-size: 13px;">Halo, <strong><?= $_SESSION['admin_nama']; ?></strong> | <a href="../logout.php" style="color: #C62828; text-decoration: none;">Logout</a></span>
        </div>
    </header>

    <main class="main-wrapper" style="max-width: 1200px; margin: 30px auto; padding: 0 20px;">
        
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
            <div>
                <h1 style="font-size: 24px; font-weight: 700; margin: 0 0 4px 0;">Verifikasi Mitra Makeup Artist (MUA)</h1>
                <p style="color: #7A6F68; font-size: 14px; margin: 0;">Kelola pendaftaran mitra penata rias, tinjau portofolio, dan tentukan status akses akun.</p>
            </div>
            
            <div>
                <span style="font-size: 11px; font-weight: 600; color: #7A6F68; display: block; margin-bottom: 4px;">Select Period</span>
                <form method="GET" action="verifikasi_mua.php">
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

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
            <div class="card-stat">
                <div class="kpi-icon-box" style="background: #FEF6E6; color: #D9822B;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <span class="stat-title">Menunggu Verifikasi</span>
                <div class="stat-value" style="color: #D9822B;"><?= $pending_count; ?></div>
            </div>
            <div class="card-stat">
                <div class="kpi-icon-box" style="background: #E8F5E9; color: #1B5E20;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <span class="stat-title">Terverifikasi</span>
                <div class="stat-value" style="color: #1B5E20;"><?= $approved_count; ?></div>
            </div>
            <div class="card-stat">
                <div class="kpi-icon-box" style="background: #FFEBEE; color: #C62828;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                </div>
                <span class="stat-title">Ditolak</span>
                <div class="stat-value" style="color: #C62828;"><?= $rejected_count; ?></div>
            </div>
        </div>

        <form method="GET" action="verifikasi_mua.php" style="display: flex; align-items: center; gap: 12px; background: white; padding: 15px 20px; border-radius: 16px; border: 1px solid #EFECE6; margin-bottom: 25px; flex-wrap: wrap;">
            
            <div class="filter-input-wrapper" style="flex: 2; min-width: 240px;">
                <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="cari" value="<?= htmlspecialchars($keyword); ?>" placeholder="Cari berdasarkan nama, email, atau nomor telepon..." style="width: 100%; height: 44px; padding: 0 16px 0 38px; border-radius: 10px; border: 1px solid #D7CCC8; outline: none; font-size: 13px; background: #FFFFFF; font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; color: #4A3B32;">
            </div>

            <div class="filter-input-wrapper" style="position: relative; flex: 1; min-width: 190px; max-width: 220px;">
                <svg class="input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                <select name="status" style="width: 100%; height: 44px; padding: 0 36px 0 36px; border-radius: 10px; border: 1px solid #D7CCC8; background: #FFFFFF; font-size: 13px; color: #4A3B32; font-family: 'Plus Jakarta Sans', sans-serif; outline: none; cursor: pointer; box-sizing: border-box; appearance: none; -webkit-appearance: none;">
                    <option value="semua" <?= $status_filter == 'semua' ? 'selected' : ''; ?>>Semua Status</option>
                    <option value="pending" <?= $status_filter == 'pending' ? 'selected' : ''; ?>>Menunggu (Pending)</option>
                    <option value="approved" <?= $status_filter == 'approved' ? 'selected' : ''; ?>>Terverifikasi (Approved)</option>
                    <option value="rejected" <?= $status_filter == 'rejected' ? 'selected' : ''; ?>>Ditolak (Rejected)</option>
                </select>
                <span style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #7A6F68; font-size: 11px;">▼</span>
            </div>

            <button type="submit" style="height: 44px; background: #4A3B32; color: white; border: none; padding: 0 26px; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 13px; font-family: 'Plus Jakarta Sans', sans-serif; white-space: nowrap;">Cari</button>
            
            <?php if ($keyword !== '' || $status_filter !== 'semua') : ?>
                <a href="verifikasi_mua.php" style="height: 44px; display: inline-flex; align-items: center; justify-content: center; background: #EFECE6; color: #4A3B32; padding: 0 16px; border-radius: 10px; text-decoration: none; font-size: 13px; font-weight: 600; white-space: nowrap; box-sizing: border-box;">Reset</a>
            <?php endif; ?>
        </form>

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
                                <!-- Ikon Dokumen SVG Menggantikan Emoji -->
                                <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #1B5E20; font-weight: 600;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg> 4/4 Lengkap
                                </span>
                            </td>
                            <td><?= date('d M Y', strtotime($row['dibuat_pada'])); ?></td>
                            <td>
                                <?php if ($row['status_verifikasi'] == 'approved') : ?>
                                    <span class="badge badge-aktif">● Terverifikasi</span>
                                <?php elseif ($row['status_verifikasi'] == 'pending') : ?>
                                    <span class="badge badge-pending">● Menunggu</span>
                                <?php else : ?>
                                    <span class="badge badge-tolak">● Ditolak</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="detail_verifikasi.php?id=<?= $row['id_user']; ?>" class="btn-action">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Tinjau
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 50px 20px; color: #7A6F68;">
                                <div style="display: flex; justify-content: center; margin-bottom: 12px; color: #D7CCC8;">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                </div>
                                <div style="font-size: 14px; font-weight: 600; color: #4A3B32; margin-bottom: 4px;">Tidak Ada Data Ditemukan</div>
                                <div style="font-size: 12px; color: #7A6F68;">Belum ada pendaftaran MUA yang masuk pada periode ini.</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>