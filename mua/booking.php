<?php
session_start();
require '../koneksi.php';

if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'mua') {
    header("Location: ../login.php");
    exit();
}

$id_mua = $_SESSION['id_user'];

// --- PROSES KONFIRMASI SELESAI & UPLOAD FOTO (ONGOING) ---
if (isset($_POST['konfirmasi_selesai_mua'])) {
    $id_booking_done = mysqli_real_escape_string($koneksi, $_POST['id_booking']);
    
    $nama_foto = NULL;
    if (isset($_FILES['foto_dokum']) && $_FILES['foto_dokum']['error'] === 0) {
        $ext = pathinfo($_FILES['foto_dokum']['name'], PATHINFO_EXTENSION);
        $nama_foto = 'dokum_' . $id_booking_done . '_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['foto_dokum']['tmp_name'], '../assets/img/' . $nama_foto);
    }
    
    if ($nama_foto) {
        mysqli_query($koneksi, "UPDATE bookings SET is_mua_done = 1, foto_dokumentasi = '$nama_foto' WHERE id_booking = '$id_booking_done' AND id_mua = '$id_mua'");
        
        $cek = mysqli_query($koneksi, "SELECT is_mua_done, is_client_done FROM bookings WHERE id_booking = '$id_booking_done'");
        if ($row = mysqli_fetch_assoc($cek)) {
            if ($row['is_mua_done'] == 1 && $row['is_client_done'] == 1) {
                mysqli_query($koneksi, "UPDATE bookings SET status = 'selesai' WHERE id_booking = '$id_booking_done'");
            }
        }
    }
    header("Location: booking.php?tab=ongoing");
    exit();
}

// --- PROSES TERIMA / TOLAK / BATALKAN (SCHEDULED) ---
if (isset($_POST['terima_pesanan'])) {
    $id_b = mysqli_real_escape_string($koneksi, $_POST['id_booking']);
    mysqli_query($koneksi, "UPDATE bookings SET status = 'dikonfirmasi' WHERE id_booking = '$id_b' AND id_mua = '$id_mua'");
    header("Location: booking.php?tab=scheduled");
    exit();
}
if (isset($_POST['tolak_pesanan']) || isset($_POST['batalkan_pesanan'])) {
    $id_b = mysqli_real_escape_string($koneksi, $_POST['id_booking']);
    mysqli_query($koneksi, "UPDATE bookings SET status = 'ditolak' WHERE id_booking = '$id_b' AND id_mua = '$id_mua'");
    header("Location: booking.php?tab=scheduled");
    exit();
}

// --- AMBIL PARAMETER WAKTU & TAB ---
$month = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('m');
$year = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');
$tab = $_GET['tab'] ?? 'scheduled';

// --- LOGIKA FILTER DINAMIS ---
$status_filter = "b.status IN ('menunggu', 'dikonfirmasi')"; 
$time_filter = "AND (b.tanggal_booking > CURDATE() OR (b.tanggal_booking = CURDATE() AND b.waktu_booking > CURTIME()))";
$date_filter = "AND MONTH(b.tanggal_booking) = '$month' AND YEAR(b.tanggal_booking) = '$year'";
$page_title = 'Pesanan Terkonfirmasi';

if ($tab == 'ongoing') {
    $status_filter = "b.status = 'dikonfirmasi'";
    $time_filter = "AND (b.tanggal_booking < CURDATE() OR (b.tanggal_booking = CURDATE() AND b.waktu_booking <= CURTIME()))";
    $date_filter = ""; 
    $page_title = 'Jadwal Hari Ini';
} elseif ($tab == 'done') {
    $status_filter = "b.status = 'selesai'";
    $time_filter = "";
    $date_filter = ""; 
    $page_title = 'Riwayat & Pendapatan';
} elseif ($tab == 'cancelled') {
    $status_filter = "b.status = 'ditolak'";
    $time_filter = "";
    $page_title = 'Pesanan Dibatalkan';
}

$prev_month = $month - 1; $prev_year = $year;
if ($prev_month == 0) { $prev_month = 12; $prev_year--; }
$next_month = $month + 1; $next_year = $year;
if ($next_month == 13) { $next_month = 1; $next_year++; }

$query_bookings = mysqli_query($koneksi, "
    SELECT b.*, u.nama AS nama_klien, u.foto_profil AS foto_klien, u.nomor_telepon, s.nama_layanan 
    FROM bookings b 
    LEFT JOIN users u ON b.id_pelanggan = u.id_user 
    LEFT JOIN services s ON b.id_layanan = s.id_service 
    WHERE b.id_mua = '$id_mua' AND ($status_filter) $time_filter $date_filter
    ORDER BY b.tanggal_booking DESC, b.waktu_booking DESC
");

$all_bookings = [];
$booked_dates = [];

// Variabel Kalkulasi Statistik
$total_sesi = 0; 
$total_pendapatan = 0; 
$dokum_terisi = 0; 
$dokum_kosong = 0;
$total_rating = 0;
$jumlah_ulasan = 0;

if ($query_bookings) {
    while ($row = mysqli_fetch_assoc($query_bookings)) {
        $all_bookings[] = $row;
        if ($tab != 'ongoing' && $tab != 'done') {
            $booked_dates[] = date('Y-m-d', strtotime($row['tanggal_booking']));
        }
        
        // Kalkulasi Statistik Tab Done
        if ($tab == 'done') {
            $total_sesi++;
            $total_pendapatan += $row['total_harga'];
            if (!empty($row['foto_dokumentasi'])) {
                $dokum_terisi++;
            } else {
                $dokum_kosong++;
            }
            // Kalkulasi Rating
            if (!empty($row['rating']) && $row['rating'] > 0) {
                $total_rating += $row['rating'];
                $jumlah_ulasan++;
            }
        }
    }
}

// Rumus Rata-rata Rating
$rata_rata_rating = $jumlah_ulasan > 0 ? round($total_rating / $jumlah_ulasan, 1) : 0;

$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$first_day_of_month = date('N', strtotime(sprintf("%04d-%02d-01", $year, $month)));
$date_obj = DateTime::createFromFormat('!m', $month);
$month_name_id = str_replace(
    ['January','February','March','April','May','June','July','August','September','October','November','December'],
    ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'],
    $date_obj->format('F') . ' ' . $year
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking - GlamLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/mua_booking.css">
</head>
<body>

    <?php include '../navbar.php'; ?>

    <main class="booking-main">
        <div class="page-header header-action">
            <h2><?php echo $page_title; ?></h2>
            <?php if($tab == 'done'): ?>
                <a href="#" class="btn-outline-maroon"><i class="fa-solid fa-dollar-sign"></i> Kelola Keuangan</a>
            <?php endif; ?>
        </div>

        <div class="booking-tabs">
            <a href="?tab=scheduled&m=<?php echo $month; ?>&y=<?php echo $year; ?>" class="tab-link <?php echo $tab == 'scheduled' ? 'active' : ''; ?>">Scheduled</a>
            <a href="?tab=ongoing&m=<?php echo $month; ?>&y=<?php echo $year; ?>" class="tab-link <?php echo $tab == 'ongoing' ? 'active' : ''; ?>">Ongoing</a>
            <a href="?tab=done&m=<?php echo $month; ?>&y=<?php echo $year; ?>" class="tab-link <?php echo $tab == 'done' ? 'active' : ''; ?>">Done</a>
            <a href="?tab=cancelled&m=<?php echo $month; ?>&y=<?php echo $year; ?>" class="tab-link <?php echo $tab == 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
        </div>

        <!-- ==============================================
             LAYOUT DONE (STATISTIK & TABEL RIWAYAT)
        =============================================== -->
        <?php if ($tab == 'done'): ?>
            <div class="done-stats-grid">
               <div class="stat-box">
                  <div class="stat-title">Acara Terselesaikan <i class="fa-regular fa-circle-check text-green"></i></div>
                  <div class="stat-value"><?php echo $total_sesi; ?> Sesi</div>
                  <div class="stat-desc text-green">100% Terlaksana Tepat Waktu</div>
               </div>
               <div class="stat-box">
                  <div class="stat-title">Total Pendapatan Bersih <span class="badge-lunas">Lunas</span></div>
                  <div class="stat-value text-maroon">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></div>
                  <div class="stat-desc">Telah ditransfer ke rekening bank</div>
               </div>
               <div class="stat-box">
                  <div class="stat-title">Kepuasan Rata-rata <i class="fa-solid fa-star text-yellow"></i></div>
                  <div class="stat-value"><?php echo $rata_rata_rating > 0 ? number_format($rata_rata_rating, 1) : '-'; ?> <span class="sub-val">/ 5.0</span></div>
                  <div class="stat-desc text-green">Berdasarkan <?php echo $jumlah_ulasan; ?> ulasan terverifikasi</div>
               </div>
               <div class="stat-box">
                  <div class="stat-title">Dokumentasi <i class="fa-regular fa-image text-maroon"></i></div>
                  <div class="stat-value"><?php echo $dokum_terisi; ?> Pesanan</div>
                  <div class="stat-desc text-maroon"><?php echo $dokum_kosong; ?> Belum Diunggah</div>
               </div>
            </div>

            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Cari nama klien, kode, atau tanggal">
            </div>

            <div class="done-table-container">
                <h3>Daftar Riwayat Jadwal Selesai</h3>
                <?php if (count($all_bookings) > 0): ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Kode Pesanan</th>
                            <th>Klien</th>
                            <th>Layanan</th>
                            <th>Waktu & Tanggal</th>
                            <th>Alamat Venue</th>
                            <th>Tarif</th>
                            <th>Dokumentasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_bookings as $bk): ?>
                        <tr onclick="window.location.href='session_detail.php?id=<?php echo $bk['id_booking']; ?>'" style="cursor: pointer; transition: 0.2s;" onmouseover="this.style.backgroundColor='#faf5f6'" onmouseout="this.style.backgroundColor='transparent'">
                            <td><?php echo htmlspecialchars($bk['no_invoice']); ?></td>
                            <td class="td-client"><img src="../assets/img/<?php echo htmlspecialchars($bk['foto_klien'] ?? 'default.jpg'); ?>" alt="klien"> <?php echo htmlspecialchars($bk['nama_klien']); ?></td>
                            <td><?php echo htmlspecialchars($bk['nama_layanan']); ?></td>
                            <td><?php echo date('H:i', strtotime($bk['waktu_booking'])); ?> WIB &middot; <?php echo date('d/m/Y', strtotime($bk['tanggal_booking'])); ?></td>
                            <td style="color: #8c8286;"><?php echo htmlspecialchars($bk['lokasi_acara'] ?? '-'); ?></td>
                            <td class="price text-maroon" style="font-weight:600;">Rp <?php echo number_format($bk['total_harga'], 0, ',', '.'); ?></td>
                            <td>
                                <?php if(!empty($bk['foto_dokumentasi'])): ?>
                                    <span class="badge-dokum selesai"><i class="fa-regular fa-circle-check"></i> Selesai</span>
                                <?php else: ?>
                                    <span class="badge-dokum kosong"><i class="fa-regular fa-circle-xmark"></i> Kosong</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="empty-state">Belum ada riwayat jadwal selesai.</div>
                <?php endif; ?>
            </div>

        <!-- ==============================================
             LAYOUT REGULAR (SCHEDULED, ONGOING, CANCELLED)
        =============================================== -->
        <?php else: ?>

            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Cari nama klien, kode, atau tanggal">
            </div>

            <div class="booking-grid" style="<?php echo $tab == 'ongoing' ? 'display: block;' : ''; ?>">
                
                <?php if ($tab != 'ongoing'): ?>
                <aside class="calendar-sidebar">
                    <div class="calendar-card">
                        <div class="calendar-header">
                            <a href="?tab=<?php echo $tab; ?>&m=<?php echo $prev_month; ?>&y=<?php echo $prev_year; ?>" class="nav-cal"><i class="fa-solid fa-chevron-left"></i></a>
                            <h3><?php echo $month_name_id; ?></h3>
                            <a href="?tab=<?php echo $tab; ?>&m=<?php echo $next_month; ?>&y=<?php echo $next_year; ?>" class="nav-cal"><i class="fa-solid fa-chevron-right"></i></a>
                        </div>
                        <div class="calendar-grid">
                            <div class="day-name">Mon</div><div class="day-name">Tue</div><div class="day-name">Wed</div>
                            <div class="day-name">Thu</div><div class="day-name">Fri</div><div class="day-name">Sat</div><div class="day-name text-red">Sun</div>
                            
                            <?php
                            for ($i = 1; $i < $first_day_of_month; $i++) echo '<div class="date-num empty"></div>';
                            
                            for ($d = 1; $d <= $days_in_month; $d++) {
                                $current_date = sprintf("%04d-%02d-%02d", $year, $month, $d);
                                $is_sunday = date('N', strtotime($current_date)) == 7 ? 'text-red' : '';
                                $dot_class =$is_sunday ? 'dot cuti-dot' : 'dot';
                                $is_booked = in_array($current_date,$booked_dates) ? "<div class='$dot_class'></div>" : '';
                                $is_today =$current_date == date('Y-m-d') ? 'today' : '';
                                
                                echo "<a href='#' data-filter-date='$current_date' class='date-num date-filter-btn$is_sunday $is_today' style='font-weight:".($is_today ? 'bold' : 'normal').";'>$d$is_booked</a>";
                            }
                            ?>
                        </div>
                        <div class="calendar-legend">
                            <div><span class="legend-dot available"></span> Jadwal</div>
                            <div><span class="legend-dot cuti"></span> Cuti</div>
                        </div>
                    </div>
                </aside>
                <?php endif; ?>

                <section class="booking-list">
                    <?php if ($tab != 'ongoing'): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3 id="dynamic-list-title" style="margin: 0; font-size: 1.1rem; color: #2b2326; width: 100%;">Semua Pesanan Bulan Ini</h3>
                    </div>
                    <?php endif; ?>

                    <div id="empty-state-msg" class="empty-state" style="display: <?php echo count($all_bookings) > 0 ? 'none' : 'block'; ?>;">
                        Belum ada jadwal pesanan.
                    </div>

                    <?php foreach ($all_bookings as$bk): ?>
                        <div class="bk-card <?php echo $tab == 'ongoing' ? 'ongoing-layout' : ''; ?>" 
                             data-filter-date="<?php echo date('Y-m-d', strtotime($bk['tanggal_booking'])); ?>"
                             data-inv="<?php echo htmlspecialchars($bk['no_invoice']); ?>"
                             data-name="<?php echo htmlspecialchars($bk['nama_klien']); ?>"
                             data-service="<?php echo htmlspecialchars($bk['nama_layanan']); ?>"
                             data-date-str="<?php echo date('d/m/Y', strtotime($bk['tanggal_booking'])); ?>"
                             data-time="<?php echo date('H:i', strtotime($bk['waktu_booking'])); ?>"
                             data-location="<?php echo htmlspecialchars($bk['lokasi_acara'] ?? 'Belum ditentukan'); ?>"
                             data-phone="<?php echo htmlspecialchars($bk['nomor_telepon'] ?? '-'); ?>"
                             data-notes="<?php $info_acara = trim(implode(' - ', array_filter([$bk['jenis_acara'] ?? '', $bk['nama_acara'] ?? '']))); echo htmlspecialchars(($info_acara !== '' ? '[Acara: ' . $info_acara . '] ' : '') . ($bk['catatan'] ?? '-')); ?>"
                             data-price="Rp <?php echo number_format($bk['total_harga'], 0, ',', '.'); ?>">
                             
                            <!-- ELEMEN TERSEMBUNYI UNTUK TOMBOL MODAL POP-UP -->
                            <div class="card-action-data" style="display: none;">
                                <?php if ($tab == 'ongoing'): ?>
                                    <?php if ($bk['is_mua_done'] == 0): ?>
                                        <form method="POST" action="" enctype="multipart/form-data" class="modal-form-stretch">
                                            <input type="hidden" name="id_booking" value="<?php echo $bk['id_booking']; ?>">
                                            <div style="display:flex; flex-direction:column; gap:10px; width:100%;">
                                                <input type="file" name="foto_dokum" required accept="image/*" style="font-size: 0.8rem; padding: 5px; border: 1px dashed #ccc; border-radius: 8px;">
                                                <button type="submit" name="konfirmasi_selesai_mua" class="btn-filled-green btn-stretch"><i class="fa-solid fa-circle-check"></i> Selesai Rias</button>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn-outline-grey btn-stretch" style="cursor: default;"><i class="fa-solid fa-clock-rotate-left"></i> Menunggu Klien</button>
                                    <?php endif; ?>
                                <?php elseif ($tab == 'scheduled' && strtolower($bk['status']) == 'menunggu'): ?>
                                    <form method="POST" action="" class="modal-form-stretch">
                                        <input type="hidden" name="id_booking" value="<?php echo $bk['id_booking']; ?>">
                                        <button type="submit" name="terima_pesanan" class="btn-filled-green btn-stretch"><i class="fa-solid fa-check"></i> Konfirmasi Pesanan</button>
                                    </form>
                                    <form method="POST" action="" class="modal-form-stretch">
                                        <input type="hidden" name="id_booking" value="<?php echo $bk['id_booking']; ?>">
                                        <button type="submit" name="tolak_pesanan" class="btn-outline-red btn-stretch"><i class="fa-solid fa-xmark"></i> Tolak</button>
                                    </form>
                                <?php elseif ($tab == 'scheduled' && strtolower($bk['status']) == 'dikonfirmasi'): ?>
                                    <form method="POST" action="" class="modal-form-stretch">
                                        <input type="hidden" name="id_booking" value="<?php echo $bk['id_booking']; ?>">
                                        <button type="submit" name="batalkan_pesanan" class="btn-filled-red btn-stretch"><i class="fa-regular fa-circle-xmark"></i> Batalkan Sesi</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            
                            <div class="bk-header-info">
                                <div class="client-left">
                                    <img src="../assets/img/<?php echo htmlspecialchars($bk['foto_klien'] ?? 'default.jpg'); ?>" alt="Klien">
                                    <div class="bk-client-details">
                                        <div class="title-row">
                                            <h4><?php echo htmlspecialchars($bk['nama_klien']); ?> <span class="invoice-badge"><?php echo htmlspecialchars($bk['no_invoice']); ?></span></h4>
                                            
                                            <?php 
                                            $status_db = strtolower($bk['status']);$b_class = ''; $b_icon = '';$b_text = '';
                                            if (in_array($status_db, ['menunggu', 'pending'])) {$b_class = 'status-warning'; $b_icon = 'fa-solid fa-clock-rotate-left';$b_text = 'Menunggu Konfirmasi';
                                            } elseif (in_array($status_db, ['selesai', 'done', 'completed'])) {$b_class = 'status-success'; $b_icon = 'fa-solid fa-check-double';$b_text = 'Selesai';
                                            } elseif (in_array($status_db, ['ditolak', 'dibatalkan', 'cancelled'])) {$b_class = 'status-danger'; $b_icon = 'fa-solid fa-ban';$b_text = 'Dibatalkan';
                                            } else {
                                                $b_class = ''; $b_icon = 'fa-regular fa-circle-check';$b_text = 'Terkonfirmasi';
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $b_class; ?>"><i class="<?php echo $b_icon; ?>"></i> <?php echo $b_text; ?></span>
                                        </div>
                                        <p class="bk-service"><?php echo htmlspecialchars($bk['nama_layanan']); ?></p>
                                        
                                        <div class="bk-meta-grid">
                                            <div><i class="fa-regular fa-calendar"></i> <?php echo date('d/m/Y', strtotime($bk['tanggal_booking'])); ?></div>
                                            <div><i class="fa-regular fa-clock"></i> <?php echo date('H:i', strtotime($bk['waktu_booking'])); ?> WIB</div>
                                            <div><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($bk['nomor_telepon'] ?? '-'); ?></div>
                                            
                                            <?php if($tab != 'ongoing'): ?>
                                                <div><i class="fa-solid fa-money-bill-wave"></i> Rp <?php echo number_format($bk['total_harga'], 0, ',', '.'); ?></div>
                                            <?php endif; ?>
                                            <div class="full-row text-grey">Lokasi: <?php echo htmlspecialchars($bk['lokasi_acara'] ?? 'Belum ditentukan'); ?></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($tab == 'ongoing'): ?>
                                <div class="action-right">
                                    <h3 class="price-big">Rp <?php echo number_format($bk['total_harga'], 0, ',', '.'); ?></h3>
                                    <div class="btn-stack" style="flex-direction:column;">
                                        <?php if ($bk['is_mua_done'] == 0): ?>
                                            <form method="POST" action="" enctype="multipart/form-data" onclick="event.stopPropagation()" style="display:flex; flex-direction:column; gap:8px;">
                                                <input type="hidden" name="id_booking" value="<?php echo $bk['id_booking']; ?>">
                                                <input type="file" name="foto_dokum" required accept="image/*" style="font-size: 0.75rem; width: 100%; border: 1px dashed #dcd7d5; padding: 5px; border-radius: 6px;">
                                                <button type="submit" name="konfirmasi_selesai_mua" class="btn-filled-green"><i class="fa-solid fa-circle-check"></i> Konfirmasi Selesai</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn-outline-grey" style="cursor: default;" onclick="event.stopPropagation()"><i class="fa-solid fa-clock-rotate-left"></i> Menunggu Klien</button>
                                        <?php endif; ?>
                                        <button class="btn-outline-grey" onclick="event.stopPropagation()"><i class="fa-regular fa-comment-dots"></i> Chat Klien</button>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($tab != 'ongoing'): ?>
                            <div class="bk-footer-actions">
                                <button class="btn-outline-grey" onclick="event.stopPropagation()"><i class="fa-regular fa-comment-dots"></i> Chat Klien</button>
                                <div class="action-group">
                                    <?php if ($tab == 'scheduled' && strtolower($bk['status']) == 'menunggu'): ?>
                                        <form method="POST" class="form-inline" onclick="event.stopPropagation()">
                                            <input type="hidden" name="id_booking" value="<?php echo $bk['id_booking']; ?>">
                                            <button type="submit" name="terima_pesanan" class="btn-filled-green"><i class="fa-solid fa-check"></i> Konfirmasi</button>
                                        </form>
                                        <form method="POST" class="form-inline" onclick="event.stopPropagation()">
                                            <input type="hidden" name="id_booking" value="<?php echo $bk['id_booking']; ?>">
                                            <button type="submit" name="tolak_pesanan" class="btn-outline-red"><i class="fa-solid fa-xmark"></i> Tolak</button>
                                        </form>
                                    <?php elseif ($tab == 'scheduled' && strtolower($bk['status']) == 'dikonfirmasi'): ?>
                                        <form method="POST" class="form-inline" onclick="event.stopPropagation()">
                                            <input type="hidden" name="id_booking" value="<?php echo $bk['id_booking']; ?>">
                                            <button type="submit" name="batalkan_pesanan" class="btn-filled-red"><i class="fa-regular fa-circle-xmark"></i> Batalkan Sesi</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($tab == 'ongoing'): ?>
                            <div class="bk-footer-ongoing">
                                <div class="pencairan-text">
                                    <strong><i class="fa-solid fa-wand-magic-sparkles" style="color: #7E4655;"></i> Pencairan Dana</strong><br>
                                    Dana otomatis masuk ke Saldo Dompet setelah Anda & Klien mengonfirmasi penyelesaian sesi.
                                </div>
                                <div class="status-chips">
                                    <?php if ($bk['is_mua_done'] == 1): ?>
                                        <span class="chip-status done"><i class="fa-solid fa-check"></i> MUA: Sudah Konfirmasi</span>
                                    <?php else: ?>
                                        <span class="chip-status pending"><i class="fa-solid fa-hourglass-half"></i> MUA: Belum</span>
                                    <?php endif; ?>

                                    <?php if ($bk['is_client_done'] == 1): ?>
                                        <span class="chip-status done"><i class="fa-solid fa-check"></i> Klien: Sudah Konfirmasi</span>
                                    <?php else: ?>
                                        <span class="chip-status default"><i class="fa-solid fa-hourglass-half"></i> Klien: Belum</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </section>
            </div>
        <?php endif; ?>
    </main>

    <!-- MODAL / POP-UP DETAIL PESANAN -->
    <div id="bookingModal" class="modal-overlay">
        <div class="modal-box">
            <button class="close-modal" onclick="closeBookingModal()"><i class="fa-solid fa-xmark"></i></button>
            <div class="modal-header-text">
                <span class="inv-number" id="modalInvoice">GL-0000</span>
                <h2 id="modalClientName">Nama Klien</h2>
            </div>
            <div class="modal-section">
                <div class="detail-row">
                    <span class="label">Layanan</span>
                    <span class="value bold" id="modalService">Service Name</span>
                </div>
                <div class="detail-row">
                    <span class="label">Tanggal & Waktu</span>
                    <span class="value" id="modalDateTime">Date, Time</span>
                </div>
                <div class="detail-row">
                    <span class="label">Lokasi</span>
                    <span class="value" id="modalLocation">Location</span>
                </div>
                <div class="detail-row">
                    <span class="label">No. Telepon</span>
                    <span class="value" id="modalPhone">Phone</span>
                </div>
                <div class="detail-row">
                    <span class="label">Catatan Klien</span>
                    <span class="value italic" id="modalNotes">Notes</span>
                </div>
                <div class="detail-row" style="margin-top: 15px; border-top: 1px dashed #ccc; padding-top: 15px;">
                    <span class="label">Total Harga</span>
                    <span class="value price" id="modalPrice">Rp 0</span>
                </div>
            </div>
            <div class="modal-footer-actions" id="modalActionButtons"></div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const navLinks = document.querySelectorAll('.nav-menu-mua a');
            navLinks.forEach(link => link.classList.remove('active'));
            if(navLinks[2]) navLinks[2].classList.add('active'); 

            // Logika Modal
            const bookingCards = document.querySelectorAll('.bk-card');
            bookingCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    if (e.target.closest('button') || e.target.closest('form') || e.target.closest('a') || e.target.closest('input')) return;
                    
                    document.getElementById('modalInvoice').innerText = this.getAttribute('data-inv');
                    document.getElementById('modalClientName').innerText = this.getAttribute('data-name');
                    document.getElementById('modalService').innerText = this.getAttribute('data-service');
                    document.getElementById('modalDateTime').innerText = this.getAttribute('data-date-str') + ' - ' + this.getAttribute('data-time') + ' WIB';
                    document.getElementById('modalLocation').innerText = this.getAttribute('data-location');
                    document.getElementById('modalPhone').innerText = this.getAttribute('data-phone');
                    document.getElementById('modalNotes').innerText = '"' + this.getAttribute('data-notes') + '"';
                    document.getElementById('modalPrice').innerText = this.getAttribute('data-price');
                    
                    const actionData = this.querySelector('.card-action-data');
                    document.getElementById('modalActionButtons').innerHTML = actionData ? actionData.innerHTML : '';
                    
                    document.getElementById('bookingModal').style.display = 'flex';
                });
            });

            // Logika Filter Kalender
            <?php if ($tab != 'ongoing' &&$tab != 'done'): ?>
            const dateBtns = document.querySelectorAll('.date-filter-btn');
            const emptyState = document.getElementById('empty-state-msg');
            const titleElement = document.getElementById('dynamic-list-title');
            let currentSelectedDate = null;
            let totalCards = bookingCards.length;

            dateBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const selectedDate = this.getAttribute('data-filter-date');
                    
                    if (currentSelectedDate === selectedDate) {
                        currentSelectedDate = null;
                        this.classList.remove('selected-date');
                        titleElement.innerHTML = 'Semua Pesanan Bulan Ini';
                        bookingCards.forEach(card => card.style.display = ''); 
                        emptyState.style.display = totalCards === 0 ? 'block' : 'none';
                        return;
                    }

                    dateBtns.forEach(b => b.classList.remove('selected-date'));
                    this.classList.add('selected-date');
                    currentSelectedDate = selectedDate;
                    
                    const dStr = selectedDate.split('-'); 
                    const formatted = `${dStr[2]}/${dStr[1]}/${dStr[0]}`;
                    titleElement.innerHTML = `Pesanan: ${formatted} <a href="#" id="clear-filter" style="font-size: 0.85rem; color: #7E4655; text-decoration: none; font-weight: 600; float:right;">Lihat Semua &times;</a>`;
                    
                    let visibleCount = 0;
                    bookingCards.forEach(card => {
                        if (card.getAttribute('data-filter-date') === selectedDate) {
                            card.style.display = '';
                            visibleCount++;
                        } else {
                            card.style.display = 'none';
                        }
                    });
                    emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
                    
                    const clearBtn = document.getElementById('clear-filter');
                    if(clearBtn) {
                        clearBtn.addEventListener('click', (ev) => {
                            ev.preventDefault();
                            btn.click(); 
                        });
                    }
                });
            });
            <?php endif; ?>
        });

        function closeBookingModal() { document.getElementById('bookingModal').style.display = 'none'; }
        document.getElementById('bookingModal').addEventListener('click', function(e) {
            if(e.target === this) closeBookingModal();
        });
    </script>
</body>
</html>