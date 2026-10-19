<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { 
    header("Location: login.php"); 
    exit(); 
}
include '../koneksi.php';

// Menangkap pilihan periode dari dropdown
$periode = isset($_GET['periode']) ? $_GET['periode'] : 'bulan_ini';

// Pengkondisian filter berdasarkan pilihan Select Period
$filter_booking = "";
$label_periode = "Bulan ini (Oktober 2026)";

if ($periode == 'bulan_ini') {
    $filter_booking = " WHERE MONTH(created_at) = 10 AND YEAR(created_at) = 2026";
    $label_periode = "Bulan ini (Oktober 2026)";
} elseif ($periode == 'bulan_lalu') {
    $filter_booking = " WHERE MONTH(created_at) = 9 AND YEAR(created_at) = 2026";
    $label_periode = "Bulan lalu (September 2026)";
} elseif ($periode == '3_bulan') {
    $filter_booking = " WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
    $label_periode = "3 Bulan Terakhir";
} elseif ($periode == 'tahun_ini') {
    $filter_booking = " WHERE YEAR(created_at) = 2026";
    $label_periode = "Tahun ini (2026)";
}

// Menghitung statistik data real dari database
$q_klien = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role='pelanggan'"))['total'];
$q_mua   = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role='mua' AND status_verifikasi='approved'"))['total'];
$q_booking = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM bookings" . $filter_booking))['total'];
$q_verif = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role='mua' AND status_verifikasi='pending'"))['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - GlamLink</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <!-- Chart.js untuk Grafik Interaktif -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="admin-navbar">
        <div class="brand-container"><h2 style="color: #4A3B32; font-weight: 700;">GlamLink</h2></div>
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link active">Dashboard</a>
            <a href="kelola_pengguna.php" class="nav-link">Kelola Pengguna</a>
            <a href="verifikasi_mua.php" class="nav-link">Verifikasi MUA</a>
        </nav>
        <div class="user-profile" style="display: flex; align-items: center; gap: 15px;">
            <span style="font-size: 13px; color: #7A6F68;">Halo, <strong><?= $_SESSION['admin_nama']; ?></strong> | <a href="../logout.php" style="color: #C62828; text-decoration: none; font-weight: 600;">Logout</a></span>
        </div>
    </header>

    <main class="main-wrapper" style="max-width: 1200px; margin: 30px auto; padding: 0 20px;">
        
        <!-- Banner Selamat Datang -->
        <div style="background: white; border-radius: 16px; padding: 25px 30px; border: 1px solid #EFECE6; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="font-size: 24px; font-weight: 700; margin: 0 0 6px 0; color: #332822;">Selamat Datang, Admin</h1>
                <p style="color: #7A6F68; font-size: 14px; margin: 0;">Pantau seluruh aktivitas pendaftaran MUA, transaksi pemesanan, verifikasi dokumen, dan moderasi kepuasan klien dalam satu dashboard yang terintegrasi.</p>
            </div>
            <div style="text-align: right; border-left: 1px solid #EFECE6; padding-left: 25px;">
                <span style="font-size: 11px; color: #7A6F68; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Tanggal Operasional</span>
                <div style="font-size: 14px; font-weight: 700; color: #332822; margin-top: 2px;">Senin, 19 Oktober 2026</div>
                <div style="font-size: 12px; color: #1B5E20; font-weight: 600; margin-top: 4px;">● Semua Layanan Aktif</div>
            </div>
        </div>

        <!-- Komponen Select Period (Dropdown Kalender) -->
        <div style="margin-bottom: 25px;">
            <label style="font-size: 13px; font-weight: 600; color: #7A6F68; display: block; margin-bottom: 8px;">Select Period</label>
            
            <form method="GET" action="" style="display: inline-block; width: 100%; max-width: 320px;">
                <div style="position: relative;">
                    <select name="periode" onchange="this.form.submit()" style="
                        width: 100%;
                        appearance: none;
                        -webkit-appearance: none;
                        background-color: #FFFFFF;
                        border: 1px solid #D7CCC8;
                        border-radius: 12px;
                        padding: 12px 16px 12px 45px;
                        font-family: 'Plus Jakarta Sans', sans-serif;
                        font-size: 14px;
                        font-weight: 500;
                        color: #4A3B32;
                        outline: none;
                        cursor: pointer;
                        box-shadow: 0 2px 5px rgba(0,0,0,0.02);
                    ">
                        <option value="bulan_ini" <?= $periode == 'bulan_ini' ? 'selected' : ''; ?>>Bulan ini (Oktober 2026)</option>
                        <option value="bulan_lalu" <?= $periode == 'bulan_lalu' ? 'selected' : ''; ?>>Bulan lalu (September 2026)</option>
                        <option value="3_bulan" <?= $periode == '3_bulan' ? 'selected' : ''; ?>>3 Bulan Terakhir</option>
                        <option value="tahun_ini" <?= $periode == 'tahun_ini' ? 'selected' : ''; ?>>Tahun ini (2026)</option>
                    </select>

                    <!-- Ikon Kalender di Kiri -->
                    <span style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); pointer-events: none; font-size: 15px;">
                    </span>

                    <!-- Ikon Panah Dropdown di Kanan -->
                    <span style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #7A6F68; font-size: 11px;">
                        ▼
                    </span>
                </div>
            </form>
        </div>

        <!-- Kartu 4 Statistik Utama -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
            <div class="card-stat">
                <span class="stat-title">Total Klien Terdaftar</span>
                <div class="stat-value"><?= number_format($q_klien); ?></div>
                <div style="font-size: 12px; color: #1B5E20; font-weight: 600; margin-top: 6px;">↗ +12.4% <span style="color: #7A6F68; font-weight: normal;">vs bulan lalu</span></div>
            </div>
            <div class="card-stat">
                <span class="stat-title">Total MUA Terdaftar</span>
                <div class="stat-value"><?= number_format($q_mua); ?></div>
                <div style="font-size: 12px; color: #1B5E20; font-weight: 600; margin-top: 6px;">↗ +8.1% <span style="color: #7A6F68; font-weight: normal;">vs bulan lalu</span></div>
            </div>
            <div class="card-stat">
                <span class="stat-title">Total Booking (<?= $label_periode; ?>)</span>
                <div class="stat-value"><?= number_format($q_booking); ?></div>
                <div style="font-size: 12px; color: #1B5E20; font-weight: 600; margin-top: 6px;">↗ Periode Aktif</div>
            </div>
            <div class="card-stat">
                <span class="stat-title">Menunggu Verifikasi</span>
                <div class="stat-value" style="color: #D9822B;"><?= $q_verif; ?></div>
                <div style="margin-top: 6px;"><span style="background: #FEF6E6; color: #D9822B; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600;">Need Attention</span></div>
            </div>
        </div>

        <!-- Grid Grafik -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
            
            <!-- Grafik Tren Garis -->
            <div style="background: white; padding: 24px; border-radius: 16px; border: 1px solid #EFECE6;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <h3 style="font-size: 16px; font-weight: 700; margin: 0 0 4px 0; color: #332822;">Tren Booking Berdasarkan Waktu</h3>
                        <p style="font-size: 12px; color: #7A6F68; margin: 0;">Performa enam bulan terakhir</p>
                    </div>
                </div>
                <canvas id="trendChart" style="max-height: 260px;"></canvas>
            </div>

            <!-- Grafik Donut Layanan -->
            <div style="background: white; padding: 24px; border-radius: 16px; border: 1px solid #EFECE6;">
                <h3 style="font-size: 16px; font-weight: 700; margin: 0 0 4px 0; color: #332822;">Booking Berdasarkan Layanan</h3>
                <p style="font-size: 12px; color: #7A6F68; margin: 0 0 15px 0;">Distribusi kategori</p>
                <div style="position: relative; height: 180px; display: flex; justify-content: center;">
                    <canvas id="serviceChart"></canvas>
                </div>
                <div style="margin-top: 15px; font-size: 12px; display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;"><span style="display: flex; align-items: center; gap: 6px;"><span style="width: 8px; height: 8px; background: #8D2140; border-radius: 50%;"></span> Makeup Wedding</span> <strong>38%</strong></div>
                    <div style="display: flex; justify-content: space-between; align-items: center;"><span style="display: flex; align-items: center; gap: 6px;"><span style="width: 8px; height: 8px; background: #C27A8F; border-radius: 50%;"></span> Makeup Wisuda</span> <strong>27%</strong></div>
                    <div style="display: flex; justify-content: space-between; align-items: center;"><span style="display: flex; align-items: center; gap: 6px;"><span style="width: 8px; height: 8px; background: #E5CEC5; border-radius: 50%;"></span> Makeup Lamaran</span> <strong>20%</strong></div>
                    <div style="display: flex; justify-content: space-between; align-items: center;"><span style="display: flex; align-items: center; gap: 6px;"><span style="width: 8px; height: 8px; background: #EFECE6; border-radius: 50%;"></span> Lainnya</span> <strong>15%</strong></div>
                </div>
            </div>

        </div>
    </main>

    <!-- Script Inisialisasi Grafik Chart.js -->
    <script>
    const ctxTrend = document.getElementById('trendChart').getContext('2d');
    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: ['Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt'],
            datasets: [{
                label: 'Jumlah Booking',
                data: [45, 78, 85, 110, 135, 162],
                borderColor: '#8D2140',
                backgroundColor: 'rgba(141, 33, 64, 0.05)',
                borderWidth: 2.5,
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#8D2140',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { color: '#F5F0E8' }, ticks: { font: { size: 11 } } },
                x: { grid: { display: false }, ticks: { font: { size: 11 } } }
            }
        }
    });

    const ctxService = document.getElementById('serviceChart').getContext('2d');
    new Chart(ctxService, {
        type: 'doughnut',
        data: {
            labels: ['Makeup Wedding', 'Makeup Wisuda', 'Makeup Lamaran', 'Lainnya'],
            datasets: [{
                data: [38, 27, 20, 15],
                backgroundColor: ['#8D2140', '#C27A8F', '#E5CEC5', '#EFECE6'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            cutout: '75%'
        }
    });
    </script>
</body>
</html>