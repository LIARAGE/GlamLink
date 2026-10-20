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
$label_periode = "Bulan ini";

// Array data grafik default (6 bulan terakhir)
$trend_labels = ['Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt'];
$trend_data = [45, 78, 85, 110, 135, 162];
$service_data = [38, 27, 20, 15];

if ($periode == 'bulan_ini') {
    $filter_booking = " WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
    $label_periode = "Bulan ini";
} elseif ($periode == 'bulan_lalu') {
    $filter_booking = " WHERE created_at >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01 00:00:00') AND created_at < DATE_FORMAT(NOW(), '%Y-%m-01 00:00:00')";
    $label_periode = "Bulan lalu";
} elseif ($periode == '3_bulan') {
    $filter_booking = " WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
    $label_periode = "3 Bulan Terakhir";
} elseif ($periode == 'tahun_ini') {
    $filter_booking = " WHERE YEAR(created_at) = YEAR(NOW())";
    $label_periode = "Tahun ini";
}

// Menghitung statistik data real dari database
$q_klien = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role='pelanggan'"))['total'];
$q_mua   = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role='mua' AND status_verifikasi='approved'"))['total'];

$q_booking_res = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM bookings" . $filter_booking);
$q_booking = $q_booking_res ? mysqli_fetch_assoc($q_booking_res)['total'] : 0;

$q_verif = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role='mua' AND status_verifikasi='pending'"))['total'];

// Logika Grafik Dinamis: Jika data booking di periode tersebut 0, buat grafik flat 0
if ($q_booking == 0) {
    $trend_data = [0, 0, 0, 0, 0, 0];
    $service_data = [0, 0, 0, 0];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - GlamLink</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <!-- Chart.js untuk Grafik Interaktif -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .kpi-icon-box {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
    <!-- Navbar yang Disamakan Persis dengan Halaman Lain -->
    <header class="admin-navbar">
        <div class="brand-container"><h2 style="color: #4A3B32; font-weight: 700;">GlamLink</h2></div>
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link active">Dashboard</a>
            <a href="kelola_pengguna.php" class="nav-link">Kelola Pengguna</a>
            <a href="verifikasi_mua.php" class="nav-link">Verifikasi MUA</a>
        </nav>
        <div class="user-profile">
            <span style="font-size: 13px;">Halo, <strong><?= $_SESSION['admin_nama']; ?></strong> | <a href="../logout.php" style="color: #C62828; text-decoration: none;">Logout</a></span>
        </div>
    </header>

    <main class="main-wrapper" style="max-width: 1200px; margin: 30px auto; padding: 0 20px;">
        
        <!-- Banner Selamat Datang dengan Select Period di Sebelah Kanan -->
        <div style="background: white; border-radius: 16px; padding: 25px 30px; border: 1px solid #EFECE6; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
            <div style="flex: 2; min-width: 280px;">
                <h1 style="font-size: 24px; font-weight: 700; margin: 0 0 6px 0; color: #332822;">Selamat Datang, Admin</h1>
                <p style="color: #7A6F68; font-size: 14px; margin: 0; line-height: 1.5;">Pantau seluruh aktivitas pendaftaran MUA, transaksi pemesanan, verifikasi dokumen, dan moderasi kepuasan klien dalam satu dashboard yang terintegrasi.</p>
            </div>
            
            <div style="display: flex; align-items: center; gap: 25px; border-left: 1px solid #EFECE6; padding-left: 25px; flex-wrap: wrap;">
                <!-- Tanggal Operasional -->
                <div>
                    <span style="font-size: 11px; color: #7A6F68; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 2px;">Tanggal Operasional</span>
                    <div style="font-size: 14px; font-weight: 700; color: #332822;"><?= date('l, d F Y'); ?></div>
                    <div style="font-size: 12px; color: #1B5E20; font-weight: 600; margin-top: 3px;">● Semua Layanan Aktif</div>
                </div>

                <!-- Select Period Dropdown dengan Ikon Kalender SVG -->
                <div>
                    <span style="font-size: 11px; font-weight: 600; color: #7A6F68; display: block; margin-bottom: 2px;">Select Period</span>
                    <form method="GET" action="">
                        <div style="position: relative;">
                            <select name="periode" onchange="this.form.submit()" style="
                                height: 40px;
                                appearance: none;
                                -webkit-appearance: none;
                                background-color: #FFFFFF;
                                border: 1px solid #D7CCC8;
                                border-radius: 10px;
                                padding: 0 35px 0 36px;
                                font-family: 'Plus Jakarta Sans', sans-serif;
                                font-size: 13px;
                                font-weight: 500;
                                color: #4A3B32;
                                outline: none;
                                cursor: pointer;
                                box-sizing: border-box;
                            ">
                                <option value="bulan_ini" <?= $periode == 'bulan_ini' ? 'selected' : ''; ?>>Bulan ini</option>
                                <option value="bulan_lalu" <?= $periode == 'bulan_lalu' ? 'selected' : ''; ?>>Bulan lalu</option>
                                <option value="3_bulan" <?= $periode == '3_bulan' ? 'selected' : ''; ?>>3 Bulan Terakhir</option>
                                <option value="tahun_ini" <?= $periode == 'tahun_ini' ? 'selected' : ''; ?>>Tahun ini</option>
                            </select>

                            <!-- Ikon Kalender SVG di Kiri -->
                            <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #7A6F68;" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>

                            <!-- Ikon Panah Dropdown di Kanan -->
                            <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #7A6F68; font-size: 10px;">
                                ▼
                            </span>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Kartu 4 Statistik Utama dengan Ikon SVG Seragam -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
            
            <div class="card-stat">
                <div class="kpi-icon-box" style="background: #E8F5E9; color: #1B5E20;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <span class="stat-title">Total Klien Terdaftar</span>
                <div class="stat-value"><?= number_format($q_klien); ?></div>
                <div style="font-size: 12px; color: #1B5E20; font-weight: 600; margin-top: 6px;">↗ Aktif</div>
            </div>

            <div class="card-stat">
                <div class="kpi-icon-box" style="background: #F5F0E8; color: #4A3B32;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                </div>
                <span class="stat-title">Total MUA Terdaftar</span>
                <div class="stat-value"><?= number_format($q_mua); ?></div>
                <div style="font-size: 12px; color: #1B5E20; font-weight: 600; margin-top: 6px;">↗ Terverifikasi</div>
            </div>

            <div class="card-stat">
                <div class="kpi-icon-box" style="background: #E3F2FD; color: #1565C0;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                </div>
                <span class="stat-title">Total Booking</span>
                <div class="stat-value"><?= number_format($q_booking); ?></div>
                <div style="font-size: 12px; color: #7A6F68; font-weight: 500; margin-top: 6px;"><?= $label_periode; ?></div>
            </div>

            <div class="card-stat">
                <div class="kpi-icon-box" style="background: #FEF6E6; color: #D9822B;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
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
                        <p style="font-size: 12px; color: #7A6F68; margin: 0;">Performa transaksi per bulan</p>
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
                    <div style="display: flex; justify-content: space-between; align-items: center;"><span style="display: flex; align-items: center; gap: 6px;"><span style="width: 8px; height: 8px; background: #8D2140; border-radius: 50%;"></span> Makeup Wedding</span> <strong><?= $q_booking > 0 ? '38%' : '0%'; ?></strong></div>
                    <div style="display: flex; justify-content: space-between; align-items: center;"><span style="display: flex; align-items: center; gap: 6px;"><span style="width: 8px; height: 8px; background: #C27A8F; border-radius: 50%;"></span> Makeup Wisuda</span> <strong><?= $q_booking > 0 ? '27%' : '0%'; ?></strong></div>
                    <div style="display: flex; justify-content: space-between; align-items: center;"><span style="display: flex; align-items: center; gap: 6px;"><span style="width: 8px; height: 8px; background: #E5CEC5; border-radius: 50%;"></span> Makeup Lamaran</span> <strong><?= $q_booking > 0 ? '20%' : '0%'; ?></strong></div>
                    <div style="display: flex; justify-content: space-between; align-items: center;"><span style="display: flex; align-items: center; gap: 6px;"><span style="width: 8px; height: 8px; background: #EFECE6; border-radius: 50%;"></span> Lainnya</span> <strong><?= $q_booking > 0 ? '15%' : '0%'; ?></strong></div>
                </div>
            </div>

        </div>
    </main>

    <!-- Script Inisialisasi Grafik Chart.js dengan Label Nama Bulan -->
    <script>
    const trendDataValues = <?= json_encode($trend_data); ?>;
    const serviceDataValues = <?= $q_booking > 0 ? json_encode([38, 27, 20, 15]) : json_encode([0, 0, 0, 0]); ?>;

    const ctxTrend = document.getElementById('trendChart').getContext('2d');
    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: ['Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt'],
            datasets: [{
                label: 'Jumlah Booking',
                data: trendDataValues,
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
                y: { 
                    beginAtZero: true,
                    grid: { color: '#F5F0E8' }, 
                    ticks: { font: { size: 11 }, precision: 0 } 
                },
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
                data: serviceDataValues,
                backgroundColor: <?= $q_booking > 0 ? "['#8D2140', '#C27A8F', '#E5CEC5', '#EFECE6']" : "['#EFECE6', '#EFECE6', '#EFECE6', '#EFECE6']" ?>,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: {
                    enabled: <?= $q_booking > 0 ? 'true' : 'false'; ?>
                }
            },
            cutout: '75%'
        }
    });
    </script>
</body>
</html>