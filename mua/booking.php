<?php
session_start();
require '../koneksi.php';

if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'mua') {
    header("Location: ../login.php");
    exit();
}

$id_mua = $_SESSION['id_user'];
$month = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('m');
$year = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');
$tab = $_GET['tab'] ?? 'scheduled';

// --- PROSES BACKEND: KONFIRMASI SELESAI (ONGOING) ---
if (isset($_POST['konfirmasi_selesai_mua'])) {
    $id_booking_done = mysqli_real_escape_string($koneksi, $_POST['id_booking']);
    mysqli_query($koneksi, "UPDATE bookings SET is_mua_done = 1 WHERE id_booking = '$id_booking_done' AND id_mua = '$id_mua'");
    
    $cek = mysqli_query($koneksi, "SELECT is_mua_done, is_client_done FROM bookings WHERE id_booking = '$id_booking_done'");
    if ($row = mysqli_fetch_assoc($cek)) {
        if ($row['is_mua_done'] == 1 && $row['is_client_done'] == 1) {
            mysqli_query($koneksi, "UPDATE bookings SET status = 'selesai' WHERE id_booking = '$id_booking_done'");
        }
    }
    header("Location: booking.php?tab=ongoing&m=$month&y=$year");
    exit();
}

// --- PROSES BACKEND: TERIMA / TOLAK / BATALKAN (SCHEDULED) ---
if (isset($_POST['terima_pesanan'])) {
    $id_b = mysqli_real_escape_string($koneksi, $_POST['id_booking']);
    mysqli_query($koneksi, "UPDATE bookings SET status = 'dikonfirmasi' WHERE id_booking = '$id_b' AND id_mua = '$id_mua'");
    header("Location: booking.php?tab=scheduled&m=$month&y=$year");
    exit();
}
if (isset($_POST['tolak_pesanan']) || isset($_POST['batalkan_pesanan'])) {$id_b = mysqli_real_escape_string($koneksi,$_POST['id_booking']);
    mysqli_query($koneksi, "UPDATE bookings SET status = 'ditolak' WHERE id_booking = '$id_b' AND id_mua = '$id_mua'");
    header("Location: booking.php?tab=scheduled&m=$month&y=$year");
    exit();
}

// --- LOGIKA FILTER DINAMIS BERDASARKAN TAB ---
$status_filter = "b.status IN ('menunggu', 'dikonfirmasi')"; 
$time_filter = "AND (b.tanggal_booking > CURDATE() OR (b.tanggal_booking = CURDATE() AND b.waktu_booking > CURTIME()))";
$date_filter = "AND MONTH(b.tanggal_booking) = '$month' AND YEAR(b.tanggal_booking) = '$year'";
$page_title = 'Jadwal Pesanan';

if ($tab == 'ongoing') {$status_filter = "b.status = 'dikonfirmasi'";
    $time_filter = "AND (b.tanggal_booking < CURDATE() OR (b.tanggal_booking = CURDATE() AND b.waktu_booking <= CURTIME()))";
    $date_filter = ""; 
    $page_title = 'Jadwal Hari Ini';
} elseif ($tab == 'done') {$status_filter = "b.status = 'selesai'";
    $time_filter = "";
    $page_title = 'Pesanan Selesai';
} elseif ($tab == 'cancelled') {$status_filter = "b.status = 'ditolak'";
    $time_filter = "";
    $page_title = 'Pesanan Dibatalkan';
}

// Logika Prev/Next Bulan
$prev_month =$month - 1; $prev_year =$year;
if ($prev_month == 0) { $prev_month = 12; $prev_year--; }
$next_month =$month + 1; $next_year =$year;
if ($next_month == 13) { $next_month = 1; $next_year++; }

// --- FETCH DATA PESANAN ---
$query_bookings = mysqli_query($koneksi, "
    SELECT b.*, u.nama AS nama_klien, u.foto_profil AS foto_klien, u.nomor_telepon, s.nama_layanan 
    FROM bookings b 
    LEFT JOIN users u ON b.id_pelanggan = u.id_user 
    LEFT JOIN services s ON b.id_layanan = s.id_service 
    WHERE b.id_mua = '$id_mua' AND ($status_filter) $time_filter$date_filter
    ORDER BY b.tanggal_booking ASC, b.waktu_booking ASC
");

if (!$query_bookings) { die("Error SQL: " . mysqli_error($koneksi)); }

$all_bookings = [];$booked_dates = [];
while ($row = mysqli_fetch_assoc($query_bookings)) {
    $all_bookings[] =$row;
    if ($tab != 'ongoing') {
        $booked_dates[] = date('Y-m-d', strtotime($row['tanggal_booking']));
    }
}

// Data Header Kalender
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month,$year);
$first_day_of_month = date('N', strtotime(sprintf("%04d-%02d-01", $year, $month)));$date_obj = DateTime::createFromFormat('!m', $month);$month_name_id = str_replace(
    ['January','February','March','April','May','June','July','August','September','October','November','December'],
    ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'],
    $date_obj->format('F') . ' ' .$year
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
    <style>
        .form-inline { display: inline-block; margin: 0; }
    </style>
</head>
<body>

    <?php include '../navbar.php'; ?>

    <main class="booking-main">
        <div class="page-header">
            <h2><?php echo $page_title; ?></h2>
        </div>

        <div class="booking-tabs">
            <a href="?tab=scheduled&m=<?php echo $month; ?>&y=<?php echo$year; ?>" class="tab-link <?php echo $tab == 'scheduled' ? 'active' : ''; ?>">Scheduled</a>
            <a href="?tab=ongoing&m=<?php echo $month; ?>&y=<?php echo$year; ?>" class="tab-link <?php echo $tab == 'ongoing' ? 'active' : ''; ?>">Ongoing</a>
            <a href="?tab=done&m=<?php echo $month; ?>&y=<?php echo$year; ?>" class="tab-link <?php echo $tab == 'done' ? 'active' : ''; ?>">Done</a>
            <a href="?tab=cancelled&m=<?php echo $month; ?>&y=<?php echo$year; ?>" class="tab-link <?php echo $tab == 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
        </div>

        <div class="search-container">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Cari nama klien, kode, atau tanggal">
        </div>

        <div class="booking-grid" style="<?php echo $tab == 'ongoing' ? 'display: block;' : ''; ?>">
            
            <?php if ($tab != 'ongoing'): ?>
            <aside class="calendar-sidebar">
                <div class="calendar-card">
                    <div class="calendar-header">
                        <a href="?tab=<?php echo $tab; ?>&m=<?php echo $prev_month; ?>&y=<?php echo$prev_year; ?>" class="nav-cal"><i class="fa-solid fa-chevron-left"></i></a>
                        <h3><?php echo $month_name_id; ?></h3>
                        <a href="?tab=<?php echo $tab; ?>&m=<?php echo $next_month; ?>&y=<?php echo$next_year; ?>" class="nav-cal"><i class="fa-solid fa-chevron-right"></i></a>
                    </div>
                    <div class="calendar-grid">
                        <div class="day-name">Mon</div><div class="day-name">Tue</div><div class="day-name">Wed</div>
                        <div class="day-name">Thu</div><div class="day-name">Fri</div><div class="day-name">Sat</div><div class="day-name text-red">Sun</div>
                        
                        <?php
                        for ($i = 1; $i < $first_day_of_month; $i++) echo '<div class="date-num empty"></div>';
                        
                        for ($d = 1; $d <= $days_in_month; $d++) {
                            $current_date = sprintf("%04d-%02d-%02d", $year, $month,$d);
                            $is_sunday = date('N', strtotime($current_date)) == 7 ? 'text-red' : '';
                            $dot_class =$is_sunday ? 'dot cuti-dot' : 'dot';
                            $is_booked = in_array($current_date,$booked_dates) ? "<div class='$dot_class'></div>" : '';
                            $is_today =$current_date == date('Y-m-d') ? 'today' : '';
                            
                            echo "<a href='#' data-date='$current_date' class='date-num date-filter-btn$is_sunday $is_today' style='font-weight:".($is_today ? 'bold' : 'normal').";'>$d$is_booked</a>";
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
                    <div class="bk-card <?php echo $tab == 'ongoing' ? 'ongoing-layout' : ''; ?>" data-date="<?php echo date('Y-m-d', strtotime($bk['tanggal_booking'])); ?>">
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
                                        <div class="full-row text-grey">Lokasi: <?php echo htmlspecialchars($bk['lokasi_acara'] ?? 'Belum ditentukan'); ?></div>
                                        <div class="full-row text-italic">Catatan Klien: "<?php echo htmlspecialchars($bk['catatan'] ?? '-'); ?>"</div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($tab == 'ongoing'): ?>
                            <div class="action-right">
                                <h3 class="price-big">Rp <?php echo number_format($bk['total_harga'], 0, ',', '.'); ?></h3>
                                <div class="btn-stack">
                                    <button class="btn-outline-grey"><i class="fa-regular fa-comment-dots"></i> Chat Klien</button>
                                    <?php if ($bk['is_mua_done'] == 0): ?>
                                        <form method="POST" action="" class="form-inline">
                                            <input type="hidden" name="id_booking" value="<?php echo $bk['id_booking']; ?>">
                                            <button type="submit" name="konfirmasi_selesai_mua" class="btn-filled-green"><i class="fa-solid fa-circle-check"></i> Konfirmasi Selesai Rias</button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn-outline-grey" style="cursor: default;"><i class="fa-solid fa-clock-rotate-left"></i> Check Status Konfirmasi Klien</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($tab != 'ongoing'): ?>
                        <div class="bk-footer-actions">
                            <button class="btn-outline-grey"><i class="fa-regular fa-comment-dots"></i> Chat Klien</button>
                            <div class="action-group">
                                <?php if ($tab == 'scheduled' && strtolower($bk['status']) == 'menunggu'): ?>
                                    <form method="POST" class="form-inline">
                                        <input type="hidden" name="id_booking" value="<?php echo $bk['id_booking']; ?>">
                                        <button type="submit" name="terima_pesanan" class="btn-filled-green"><i class="fa-solid fa-check"></i> Konfirmasi</button>
                                    </form>
                                    <form method="POST" class="form-inline">
                                        <input type="hidden" name="id_booking" value="<?php echo $bk['id_booking']; ?>">
                                        <button type="submit" name="tolak_pesanan" class="btn-outline-red"><i class="fa-solid fa-xmark"></i> Tolak</button>
                                    </form>
                                <?php elseif ($tab == 'scheduled' && strtolower($bk['status']) == 'dikonfirmasi'): ?>
                                    <form method="POST" class="form-inline">
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
                                <strong><i class="fa-solid fa-wand-magic-sparkles" style="color: #7E4655;"></i> Pencairan Dana: Konfirmasi Penyelesaian Sesi Makeup</strong><br>
                                Dana <b>Rp <?php echo number_format($bk['total_harga'], 0, ',', '.'); ?></b> akan otomatis masuk ke Saldo Dompet setelah Anda & Klien sama-sama mengonfirmasi penyelesaian sesi.
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
    </main>

    <?php if ($tab != 'ongoing'): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const dateBtns = document.querySelectorAll('.date-filter-btn');
            const bookingCards = document.querySelectorAll('.bk-card');
            const emptyState = document.getElementById('empty-state-msg');
            const titleElement = document.getElementById('dynamic-list-title');
            let currentSelectedDate = null;
            let totalCards = bookingCards.length;

            dateBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const selectedDate = this.getAttribute('data-date');
                    
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
                        if (card.getAttribute('data-date') === selectedDate) {
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
        });
    </script>
    <?php endif; ?>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const navLinks = document.querySelectorAll('.nav-menu-mua a');
            navLinks.forEach(link => link.classList.remove('active'));
            if(navLinks[2]) navLinks[2].classList.add('active'); 
        });
    </script>
</body>
</html>