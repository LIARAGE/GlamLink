<?php
session_start();
require '../koneksi.php';

if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'mua') {
    header("Location: ../login.php");
    exit();
}

$id_mua = $_SESSION['id_user'];

// Ambil Status Langganan Aktif Terakhir dengan JOIN
$q_aktif = mysqli_query($koneksi, "
    SELECT ms.*, sp.nama_paket 
    FROM mua_subscriptions ms 
    JOIN subscription_packages sp ON ms.id_paket = sp.id_paket 
    WHERE ms.id_mua = '$id_mua' AND ms.status = 'aktif' AND ms.tanggal_berakhir >= CURDATE() 
    ORDER BY ms.tanggal_berakhir DESC LIMIT 1
");
$sub_aktif = mysqli_fetch_assoc($q_aktif);

$status_akun = "Reguler (Gratis)";
$paket_berjalan = null;
$sisa_hari = 0;
$tgl_berakhir = "-";

if ($sub_aktif) {
    $status_akun = $sub_aktif['nama_paket'];
    $paket_berjalan = $sub_aktif['nama_paket'];
    $tgl_berakhir = date('d/m/Y', strtotime($sub_aktif['tanggal_berakhir']));
    
    $date1 = new DateTime(date('Y-m-d'));
    $date2 = new DateTime($sub_aktif['tanggal_berakhir']);
    $sisa_hari = $date1->diff($date2)->days;
}

// Ambil Riwayat Pembelian dengan JOIN
$q_riwayat = mysqli_query($koneksi, "
    SELECT ms.*, sp.nama_paket 
    FROM mua_subscriptions ms 
    JOIN subscription_packages sp ON ms.id_paket = sp.id_paket 
    WHERE ms.id_mua = '$id_mua' 
    ORDER BY ms.tanggal_transaksi DESC
");
$riwayat = [];
if($q_riwayat) {
    while ($row = mysqli_fetch_assoc($q_riwayat)) {
        $riwayat[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription - GlamLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/mua_subscription.css">
</head>
<body>

    <?php include '../navbar.php'; ?>

    <main class="sub-main">
        <div class="page-header">
            <h2>Paket Langganan Mitra MUA</h2>
            <div class="status-badge-top"><i class="fa-regular fa-circle-check"></i> Status Akun: <?php echo $status_akun; ?></div>
        </div>

        <?php if ($paket_berjalan): ?>
        <div class="active-sub-banner">
            <div class="asb-left">
                <div class="asb-label"><i class="fa-solid fa-crown"></i> PAKET LANGGANAN BERJALAN <span class="badge-aktif">Aktif</span></div>
                <h3><?php echo $paket_berjalan; ?></h3>
            </div>
            <div class="asb-right">
                <div class="asb-expiry">
                    <span class="exp-label">MASA BERLAKU HINGGA</span>
                    <span class="exp-date"><?php echo $tgl_berakhir; ?></span>
                    <span class="exp-days">(<?php echo $sisa_hari; ?> hari tersisa)</span>
                </div>
                <a href="subscription_pay.php" class="btn-perpanjang">Perpanjang Sekarang</a>
            </div>
        </div>
        <?php endif; ?>

        <div class="pricing-grid">
            <div class="pricing-card">
                <div class="pc-header">
                    <span class="pc-tier">MITRA PEMULA</span>
                    <h4>Starter Studio</h4>
                    <p class="pc-duration">7 Hari</p>
                </div>
                <div class="pc-price">
                    <h2>Rp 30.000</h2>
                    <p>atau Rp 4.285/hari</p>
                </div>
                <ul class="pc-features">
                    <li><i class="fa-solid fa-check text-green"></i> Tampil di Featured MUA</li>
                    <li><i class="fa-solid fa-check text-green"></i> Maksimal 15 feed portofolio</li>
                    <li><i class="fa-solid fa-check text-green"></i> Listing pencarian standar regional</li>
                </ul>
                <a href="subscription_pay.php?id_paket=1" class="btn-pilih outline">Pilih Starter (7 Hari)</a>
            </div>

            <div class="pricing-card popular">
                <div class="popular-badge"><i class="fa-solid fa-sparkles"></i> PALING BANYAK DIPILIH</div>
                <div class="pc-header">
                    <span class="pc-tier text-maroon"><i class="fa-solid fa-crown"></i> PROFESIONAL</span>
                    <h4>Pro Glam Artist</h4>
                    <p class="pc-duration">30 Hari</p>
                </div>
                <div class="pc-price text-maroon">
                    <h2>Rp 110.000</h2>
                    <p>atau Rp 3.667/hari</p>
                </div>
                <ul class="pc-features">
                    <li><i class="fa-solid fa-check text-green"></i> Tampil di Featured MUA</li>
                    <li><i class="fa-solid fa-check text-green"></i> Kuota Feed Portofolio Tanpa Batas</li>
                    <li><i class="fa-solid fa-check text-green"></i> Prioritas Algoritma #1 di Pencarian Kota</li>
                </ul>
                <a href="subscription_pay.php?id_paket=2" class="btn-pilih filled">Pilih Pro Glam (30 Hari)</a>
            </div>

            <div class="pricing-card">
                <div class="pc-header">
                    <span class="pc-tier text-yellow"><i class="fa-solid fa-star"></i> STUDIO EKSKLUSIF</span>
                    <h4>Elite Master Studio</h4>
                    <p class="pc-duration">90 Hari</p>
                </div>
                <div class="pc-price">
                    <h2>Rp 300.000</h2>
                    <p>atau Rp 3.334/hari</p>
                </div>
                <ul class="pc-features">
                    <li><i class="fa-solid fa-check text-green"></i> Semua keuntungan Pro Glam Artist</li>
                    <li><i class="fa-solid fa-check text-green"></i> Featured Banner Eksklusif di Home Klien</li>
                    <li><i class="fa-solid fa-check text-green"></i> Dashboard Analitik Pengunjung & Trend Pencarian</li>
                </ul>
                <a href="subscription_pay.php?id_paket=3" class="btn-pilih dark">Pilih Elite Master (90 Hari)</a>
            </div>
        </div>

        <div class="history-section">
            <div class="hs-header">
                <h3>Riwayat Pembelian</h3>
                <span>Total <?php echo count($riwayat); ?> Transaksi</span>
            </div>
            
            <?php if(count($riwayat) > 0): ?>
            <div class="table-responsive">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>No. Invoice</th>
                            <th>Paket Langganan</th>
                            <th>Metode Bayar</th>
                            <th>Nominal</th>
                            <th>Tanggal Transaksi</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($riwayat as $r): ?>
                        <tr>
                            <td style="font-weight:600; font-size:0.85rem;"><?php echo htmlspecialchars($r['no_invoice']); ?></td>
                            <td style="font-weight:600; color:#2b2326; font-size:0.85rem;"><?php echo htmlspecialchars($r['nama_paket']); ?></td>
                            <td style="color:#8c8286; font-size:0.85rem;"><?php echo htmlspecialchars($r['metode_bayar']); ?></td>
                            <td style="color:#7E4655; font-weight:600; font-size:0.85rem;">Rp <?php echo number_format($r['nominal_bayar'], 0, ',', '.'); ?></td>
                            <td style="color:#8c8286; font-size:0.85rem;"><?php echo date('H:i \W\I\B \· d/m/Y', strtotime($r['tanggal_transaksi'])); ?></td>
                            <td>
                                <?php if($r['status'] == 'aktif'): ?>
                                    <span class="badge-status aktif"><i class="fa-solid fa-check"></i> Lunas & Aktif</span>
                                <?php else: ?>
                                    <span class="badge-status exp"><i class="fa-solid fa-check"></i> Lunas & Kedaluwarsa</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div style="padding: 30px; text-align: center; color: #8c8286;">Belum ada riwayat transaksi.</div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const navLinks = document.querySelectorAll('.nav-menu-mua a');
            navLinks.forEach(link => link.classList.remove('active'));
            if(navLinks[3]) navLinks[3].classList.add('active'); 
        });
    </script>
</body>
</html>