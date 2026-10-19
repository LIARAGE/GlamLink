<?php
session_start();
require '../koneksi.php';

if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'mua') {
    header("Location: ../login.php");
    exit();
}

$id_mua = $_SESSION['id_user'];
$id_paket_aktif = isset($_GET['id_paket']) ? (int)$_GET['id_paket'] : 2; // Default Pro Glam (id=2)

// --- AMBIL DATA MASTER PAKET DARI DATABASE ---
$q_paket = mysqli_query($koneksi, "SELECT * FROM subscription_packages ORDER BY id_paket ASC");
$pakets = [];
$js_plans_data = []; // Untuk dilempar ke JavaScript

while($row = mysqli_fetch_assoc($q_paket)){
    $pakets[$row['id_paket']] = $row;
    
    // Logika Diskon (10% untuk paket selain Starter)
    $diskon = ($row['id_paket'] > 1) ? ($row['harga'] * 0.1) : 0;
    
    $js_plans_data[$row['id_paket']] = [
        'nama' => $row['nama_paket'],
        'durasi' => $row['durasi_hari'] . ' Hari',
        'normal' => (int)$row['harga'],
        'diskon' => (int)$diskon
    ];
}

// --- PROSES PEMBAYARAN KE TABEL TRANSAKSI ---
if (isset($_POST['bayar_sekarang'])) {
    $id_paket_pilihan = (int)$_POST['id_paket'];
    $metode = mysqli_real_escape_string($koneksi, $_POST['metode_bayar']);
    
    $paket_info = $pakets[$id_paket_pilihan];
    $durasi = $paket_info['durasi_hari'];
    $harga_normal = $paket_info['harga'];
    
    $diskon = ($id_paket_pilihan > 1) ? ($harga_normal * 0.1) : 0;
    $nominal_bayar = $harga_normal - $diskon;

    $no_invoice = 'INV-SUB-' . date('Y') . '-' . rand(1000, 9999);

    // Cek apakah ada paket aktif, jika ada, tambahkan durasi dari tanggal berakhir saat ini
    $q_cek_aktif = mysqli_query($koneksi, "SELECT tanggal_berakhir FROM mua_subscriptions WHERE id_mua = '$id_mua' AND status = 'aktif' AND tanggal_berakhir >= CURDATE() ORDER BY tanggal_berakhir DESC LIMIT 1");
    
    if(mysqli_num_rows($q_cek_aktif) > 0) {
        $row_aktif = mysqli_fetch_assoc($q_cek_aktif);
        $base_date = $row_aktif['tanggal_berakhir'];
    } else {
        $base_date = date('Y-m-d');
    }

    $tgl_berakhir = date('Y-m-d', strtotime($base_date . " + $durasi days"));

    // Insert ke tabel riwayat transaksi (mua_subscriptions)
    $query_insert = "INSERT INTO mua_subscriptions (no_invoice, id_mua, id_paket, metode_bayar, nominal_bayar, tanggal_berakhir, status) 
                     VALUES ('$no_invoice', '$id_mua', '$id_paket_pilihan', '$metode', '$nominal_bayar', '$tgl_berakhir', 'aktif')";
    
    if (mysqli_query($koneksi, $query_insert)) {
        echo "<script>alert('Pembayaran Berhasil! Paket langganan Anda telah diperbarui.'); window.location.href='subscription.php';</script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perpanjang Subscription - GlamLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/mua_subscription.css">
</head>
<body>

    <?php include '../navbar.php'; ?>

    <div class="pay-top-bg">
        <div class="pay-container">
            <a href="subscription.php" class="btn-back-pay"><i class="fa-solid fa-arrow-left"></i></a>
            <h2>Perpanjang Subscription</h2>
        </div>
    </div>

    <main class="pay-container" style="margin-top: -30px; padding-bottom: 50px;">
        <form method="POST" action="" id="formPembayaran" class="pay-grid">
            
            <!-- KOLOM KIRI: PILIHAN -->
            <div class="pay-left">
                
                <!-- STEP 1: PAKET -->
                <div class="pay-step-box">
                    <div class="step-header">
                        <div class="step-num">1</div>
                        <h3>PILIH TINGKAT PAKET KEANGGOTAAN</h3>
                    </div>
                    
                    <!-- Radio: Starter (ID: 1) -->
                    <label class="radio-card <?php echo $id_paket_aktif == 1 ? 'selected' : ''; ?>">
                        <input type="radio" name="id_paket" value="1" <?php echo $id_paket_aktif == 1 ? 'checked' : ''; ?> onchange="updateSummary()">
                        <div class="rc-check"><i class="fa-solid fa-check"></i></div>
                        <div class="rc-content">
                            <div class="rc-top">
                                <div>
                                    <h4><?php echo htmlspecialchars($pakets[1]['nama_paket']); ?> <span class="badge-tier basic">Pemula</span></h4>
                                    <p class="rc-dur"><?php echo $pakets[1]['durasi_hari']; ?> Hari</p>
                                </div>
                                <div class="rc-price-right">
                                    <h4>Rp <?php echo number_format($pakets[1]['harga'], 0, ',', '.'); ?></h4>
                                    <p>Rp <?php echo number_format(ceil($pakets[1]['harga'] / $pakets[1]['durasi_hari']), 0, ',', '.'); ?>/hari</p>
                                </div>
                            </div>
                            <ul class="rc-features">
                                <li><i class="fa-solid fa-check-circle"></i> Tampil di Featured MUA</li>
                                <li><i class="fa-solid fa-check-circle"></i> Maksimal 15 feed portofolio</li>
                                <li><i class="fa-solid fa-check-circle"></i> Listing pencarian standar regional</li>
                            </ul>
                        </div>
                    </label>

                    <!-- Radio: Pro (ID: 2) -->
                    <label class="radio-card <?php echo $id_paket_aktif == 2 ? 'selected' : ''; ?>">
                        <input type="radio" name="id_paket" value="2" <?php echo $id_paket_aktif == 2 ? 'checked' : ''; ?> onchange="updateSummary()">
                        <div class="rc-check"><i class="fa-solid fa-check"></i></div>
                        <div class="rc-content">
                            <div class="rc-top">
                                <div>
                                    <h4><?php echo htmlspecialchars($pakets[2]['nama_paket']); ?> <span class="badge-tier pop">Paling Populer</span></h4>
                                    <p class="rc-dur"><?php echo $pakets[2]['durasi_hari']; ?> Hari</p>
                                </div>
                                <div class="rc-price-right">
                                    <h4>Rp <?php echo number_format($pakets[2]['harga'], 0, ',', '.'); ?></h4>
                                    <p>Rp <?php echo number_format(ceil($pakets[2]['harga'] / $pakets[2]['durasi_hari']), 0, ',', '.'); ?>/hari</p>
                                </div>
                            </div>
                            <ul class="rc-features">
                                <li><i class="fa-solid fa-check-circle"></i> Tampil di Featured MUA</li>
                                <li><i class="fa-solid fa-check-circle"></i> Kuota Feed Portofolio Tanpa Batas</li>
                                <li><i class="fa-solid fa-check-circle"></i> Prioritas Algoritma #1 di Pencarian Kota</li>
                            </ul>
                        </div>
                    </label>

                    <!-- Radio: Elite (ID: 3) -->
                    <label class="radio-card <?php echo $id_paket_aktif == 3 ? 'selected' : ''; ?>">
                        <input type="radio" name="id_paket" value="3" <?php echo $id_paket_aktif == 3 ? 'checked' : ''; ?> onchange="updateSummary()">
                        <div class="rc-check"><i class="fa-solid fa-check"></i></div>
                        <div class="rc-content">
                            <div class="rc-top">
                                <div>
                                    <h4><?php echo htmlspecialchars($pakets[3]['nama_paket']); ?> <span class="badge-tier vip">Eksklusif Master</span></h4>
                                    <p class="rc-dur"><?php echo $pakets[3]['durasi_hari']; ?> Hari</p>
                                </div>
                                <div class="rc-price-right">
                                    <h4>Rp <?php echo number_format($pakets[3]['harga'], 0, ',', '.'); ?></h4>
                                    <p>Rp <?php echo number_format(ceil($pakets[3]['harga'] / $pakets[3]['durasi_hari']), 0, ',', '.'); ?>/hari</p>
                                </div>
                            </div>
                            <ul class="rc-features">
                                <li><i class="fa-solid fa-check-circle"></i> Semua keuntungan Pro Glam Artist</li>
                                <li><i class="fa-solid fa-check-circle"></i> Featured Banner Eksklusif di Home Klien</li>
                                <li><i class="fa-solid fa-check-circle"></i> Dashboard Analitik Pengunjung & Trend Pencarian</li>
                            </ul>
                        </div>
                    </label>
                </div>

                <!-- STEP 2: METODE BAYAR -->
                <div class="pay-step-box">
                    <div class="step-header">
                        <div class="step-num">2</div>
                        <h3>METODE PEMBAYARAN</h3>
                    </div>

                    <label class="radio-card small-card selected">
                        <input type="radio" name="metode_bayar" value="QRIS Instan" checked onchange="updatePaymentSelect(this)">
                        <div class="rc-check"><i class="fa-solid fa-check"></i></div>
                        <div class="rc-icon"><i class="fa-solid fa-qrcode"></i></div>
                        <div class="rc-text-simple">
                            <h4>QRIS Instan Otomatis <span class="badge-tier pop">Disarankan</span></h4>
                            <p>Bisa scan dari GoPay, BCA Mobile, OVO, ShopeePay, DANA (Bebas Biaya Admin)</p>
                        </div>
                    </label>

                    <label class="radio-card small-card">
                        <input type="radio" name="metode_bayar" value="BCA Virtual Account" onchange="updatePaymentSelect(this)">
                        <div class="rc-check"><i class="fa-solid fa-check"></i></div>
                        <div class="rc-icon"><i class="fa-solid fa-building-columns"></i></div>
                        <div class="rc-text-simple">
                            <h4>BCA Virtual Account</h4>
                            <p>Nomor VA langsung terbit, verifikasi real-time 24 jam</p>
                        </div>
                    </label>

                    <label class="radio-card small-card">
                        <input type="radio" name="metode_bayar" value="Mandiri / BRI / BNI VA" onchange="updatePaymentSelect(this)">
                        <div class="rc-check"><i class="fa-solid fa-check"></i></div>
                        <div class="rc-icon"><i class="fa-solid fa-building-columns"></i></div>
                        <div class="rc-text-simple">
                            <h4>Mandiri / BRI / BNI Virtual Account</h4>
                            <p>Pembayaran transfer bank antar VA tanpa biaya tambahan</p>
                        </div>
                    </label>

                    <label class="radio-card small-card">
                        <input type="radio" name="metode_bayar" value="Kartu Kredit / Debit" onchange="updatePaymentSelect(this)">
                        <div class="rc-check"><i class="fa-solid fa-check"></i></div>
                        <div class="rc-icon"><i class="fa-regular fa-credit-card"></i></div>
                        <div class="rc-text-simple">
                            <h4>Kartu Kredit / Debit Online</h4>
                            <p>Mendukung Visa, Mastercard, JCB berlogo 3D Secure</p>
                        </div>
                    </label>

                </div>
            </div>

            <!-- KOLOM KANAN: SUMMARY -->
            <div class="pay-right">
                <div class="summary-box">
                    <span class="sum-label">RINGKASAN PEMBAYARAN</span>
                    <h3>Faktur Langganan Studio</h3>

                    <div class="sum-details">
                        <div class="sum-row">
                            <span class="sr-label">Paket Dipilih:</span>
                            <span class="sr-val bold" id="sumPaket">Memuat...</span>
                        </div>
                        <div class="sum-row">
                            <span class="sr-label">Durasi Periode:</span>
                            <span class="sr-val bold" id="sumDurasi">...</span>
                        </div>
                        <div class="sum-row">
                            <span class="sr-label">Harga Normal:</span>
                            <span class="sr-val" id="sumNormal">Rp 0</span>
                        </div>
                        <div class="sum-row" id="rowDiskon" style="display: none;">
                            <span class="sr-label">Potongan Durasi:</span>
                            <span class="sr-val text-green" id="sumDiskon">- Rp 0</span>
                        </div>
                        <div class="sum-row">
                            <span class="sr-label">Biaya Administrasi & Pajak:</span>
                            <span class="sr-val text-green">Rp 0 (Gratis)</span>
                        </div>
                    </div>

                    <div class="sum-total">
                        <span>Total Tagihan:</span>
                        <span class="st-price" id="sumTotalTagihan">Rp 0</span>
                    </div>

                    <div class="sum-info-box">
                        <i class="fa-regular fa-clock"></i>
                        <div>
                            <strong>Instruksi Pembayaran</strong>
                            <p>Setelah menekan tombol di bawah, QRIS atau kode pembayaran akan langsung aktif dan status keanggotaan terverifikasi instan.</p>
                        </div>
                    </div>

                    <button type="submit" name="bayar_sekarang" class="btn-bayar">
                        <i class="fa-solid fa-bolt"></i> Bayar Sekarang &middot; <span id="btnTotal">Rp 0</span>
                    </button>
                    <p class="secure-text"><i class="fa-solid fa-shield-halved"></i> Transaksi Aman Terenkripsi 256-bit SSL</p>
                </div>
            </div>
        </form>
    </main>

    <!-- SCRIPT UNTUK KALKULASI DINAMIS & STYLING KLIK -->
    <script>
        // Lempar data paket dari PHP ke JavaScript
        const plansData = <?php echo json_encode($js_plans_data); ?>;

        function formatRp(angka) {
            return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        function updateSummary() {
            // Update styling radio
            document.querySelectorAll('input[name="id_paket"]').forEach(radio => {
                radio.closest('.radio-card').classList.toggle('selected', radio.checked);
            });

            // Kalkulasi berdasarkan ID paket yang dipilih
            const selectedId = document.querySelector('input[name="id_paket"]:checked').value;
            const data = plansData[selectedId];
            const total = data.normal - data.diskon;

            // Update DOM Summary
            document.getElementById('sumPaket').innerText = data.nama;
            document.getElementById('sumDurasi').innerText = data.durasi;
            document.getElementById('sumNormal').innerText = formatRp(data.normal);
            
            const rowDiskon = document.getElementById('rowDiskon');
            if (data.diskon > 0) {
                document.getElementById('sumDiskon').innerText = '- ' + formatRp(data.diskon);
                rowDiskon.style.display = 'flex';
            } else {
                rowDiskon.style.display = 'none';
            }

            document.getElementById('sumTotalTagihan').innerText = formatRp(total);
            document.getElementById('btnTotal').innerText = formatRp(total);
        }

        function updatePaymentSelect(element) {
            document.querySelectorAll('input[name="metode_bayar"]').forEach(radio => {
                radio.closest('.radio-card').classList.remove('selected');
            });
            element.closest('.radio-card').classList.add('selected');
        }

        // Jalankan saat pertama dimuat
        document.addEventListener('DOMContentLoaded', updateSummary);
    </script>
</body>
</html>