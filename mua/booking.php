<?php
session_start();
require '../koneksi.php';

if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'mua') {
    header("Location: ../login.php");
    exit();
}

$id_mua =$_SESSION['id_user'];

// 1. AMBIL PARAMETER FILTER TAB
$month = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('m');
$year = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');$selected_date = isset($_GET['date']) ?$_GET['date'] : '';

$tab =$_GET['tab'] ?? 'scheduled';
$status_filter = 'dikonfirmasi';$page_title = 'Pesanan Terkonfirmasi';

if ($tab == 'ongoing') {
    $status_filter = 'menunggu';$page_title = 'Pesanan Menunggu';
} elseif ($tab == 'done') {
    $status_filter = 'selesai';$page_title = 'Pesanan Selesai';
} elseif ($tab == 'cancelled') {
    $status_filter = 'ditolak';$page_title = 'Pesanan Dibatalkan';
}

// Logika Prev/Next Bulan
$prev_month =$month - 1;
$prev_year =$year;
if ($prev_month == 0) { $prev_month = 12; $prev_year--; }

$next_month =$month + 1;
$next_year =$year;
if ($next_month == 13) { $next_month = 1; $next_year++; }

// 2. FETCH DATA UNTUK TITIK KALENDER (Tarik Semua: Dikonfirmasi & Menunggu)
$query_dots = mysqli_query($koneksi, "
    SELECT tanggal_booking FROM bookings 
    WHERE id_mua = '$id_mua' AND status IN ('dikonfirmasi', 'menunggu')
      AND MONTH(tanggal_booking) = '$month' AND YEAR(tanggal_booking) = '$year'
");
$booked_dates = [];
if ($query_dots) {
    while ($row = mysqli_fetch_assoc($query_dots)) {
        $booked_dates[] = date('Y-m-d', strtotime($row['tanggal_booking']));
    }
}

// 3. FETCH DATA UNTUK DAFTAR KANAN (Hanya ditarik sesuai Tab aktif)
$query_bookings = mysqli_query($koneksi, "
    SELECT b.*, u.nama AS nama_klien, u.foto_profil AS foto_klien, u.nomor_telepon, s.nama_layanan 
    FROM bookings b 
    LEFT JOIN users u ON b.id_pelanggan = u.id_user 
    LEFT JOIN services s ON b.id_layanan = s.id_service 
    WHERE b.id_mua = '$id_mua' AND b.status = '$status_filter'
      AND MONTH(b.tanggal_booking) = '$month' AND YEAR(b.tanggal_booking) = '$year'
    ORDER BY b.tanggal_booking ASC, b.waktu_booking ASC
");

$all_month_bookings = [];
if ($query_bookings) {
    while ($row = mysqli_fetch_assoc($query_bookings)) {
        $all_month_bookings[] =$row;
    }
}

// 4. FILTER JS (Saring yang akan ditampilkan berdasarkan tanggal yang diklik)
$bookings_to_show = [];
if (!empty($selected_date)) {
    foreach ($all_month_bookings as$bk) {
        if ($bk['tanggal_booking'] ==$selected_date) {
            $bookings_to_show[] =$bk;
        }
    }
} else {
    $bookings_to_show =$all_month_bookings; 
}

// Data Header Kalender
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month,$year);
$first_day_of_month = date('N', strtotime(sprintf("\%04d-\%02d-01", $year, $month)));$date_obj = DateTime::createFromFormat('!m', $month);$month_name_id = str_replace(
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

        <div class="booking-grid">
            
            <!-- KOLOM KIRI: KALENDER -->
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
                        // Spacer
                        for ($i = 1; $i < $first_day_of_month; $i++) echo '<div class="date-num empty"></div>';
                        
                        // Render Tanggal
                        for ($d = 1; $d <= $days_in_month; $d++) {
                            $current_date = sprintf("\%04d-\%02d-\%02d", $year, $month,$d);
                            
                            $is_sunday = date('N', strtotime($current_date)) == 7 ? 'text-red' : '';
                            $dot_class =$is_sunday ? 'dot cuti-dot' : 'dot';
                            $is_booked = in_array($current_date,$booked_dates) ? "<div class='$dot_class'></div>" : '';
                            $is_today =$current_date == date('Y-m-d') ? 'today' : '';
                            
                            echo "<a href='#' data-date='$current_date' class='date-num date-filter-btn$is_sunday $is_today' style='font-weight:".($is_today ? 'bold' : 'normal').";'>$d$is_booked</a>";
                        }
                        ?>
                    </div>
                    
                    <div class="calendar-legend">
                        <div><span class="legend-dot available"></span> Available</div>
                        <div><span class="legend-dot cuti"></span> Cuti</div>
                    </div>
                </div>
            </aside>

            <!-- KOLOM KANAN: DAFTAR PESANAN -->
            <section class="booking-list">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 id="dynamic-list-title" style="margin: 0; font-size: 1.1rem; color: #2b2326; width: 100%;">Semua Pesanan Bulan Ini</h3>
                </div>

                <div id="empty-state-msg" class="empty-state" style="display: <?php echo count($all_month_bookings) > 0 ? 'none' : 'block'; ?>;">
                    Belum ada jadwal pesanan di bulan ini.
                </div>

                <?php foreach ($all_month_bookings as$bk): ?>
                    <div class="bk-card" data-date="<?php echo $bk['tanggal_booking']; ?>">
                        <div class="bk-header-info">
                            <img src="../assets/img/<?php echo htmlspecialchars($bk['foto_klien'] ?? 'default.jpg'); ?>" alt="Klien">
                            <div class="bk-client-details">
                                <div class="title-row">
                                    <h4><?php echo htmlspecialchars($bk['nama_klien']); ?> <span class="invoice-badge"><?php echo htmlspecialchars($bk['no_invoice']); ?></span></h4>
                                    
                                    <?php 
                                    $status_db = $bk['status'];$b_class = ''; $b_icon = '';$b_text = '';
                                    if ($status_db == 'menunggu') {$b_class = 'status-warning'; $b_icon = 'fa-solid fa-clock-rotate-left';$b_text = 'Menunggu Konfirmasi';
                                    } elseif ($status_db == 'dikonfirmasi') {$b_class = ''; $b_icon = 'fa-regular fa-circle-check';$b_text = 'Terkonfirmasi';
                                    } elseif ($status_db == 'selesai') {$b_class = 'status-success'; $b_icon = 'fa-solid fa-check-double';$b_text = 'Selesai';
                                    } elseif ($status_db == 'ditolak' || $status_db == 'dibatalkan') {$b_class = 'status-danger'; $b_icon = 'fa-solid fa-ban';$b_text = 'Dibatalkan';
                                    }
                                    ?>
                                    <span class="status-badge <?php echo $b_class; ?>"><i class="<?php echo $b_icon; ?>"></i> <?php echo $b_text; ?></span>
                                </div>
                                <p class="bk-service"><?php echo htmlspecialchars($bk['nama_layanan']); ?></p>
                                
                                <div class="bk-meta-grid">
                                    <div><i class="fa-regular fa-calendar"></i> <?php echo date('d/m/Y', strtotime($bk['tanggal_booking'])); ?></div>
                                    <div><i class="fa-regular fa-clock"></i> <?php echo date('H:i', strtotime($bk['waktu_booking'])); ?> WIB</div>
                                    <div class="full-row"><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($bk['nomor_telepon'] ?? '-'); ?></div>
                                    <div class="full-row text-grey">Lokasi: <?php echo htmlspecialchars($bk['lokasi_acara'] ?? 'Belum ditentukan'); ?></div>
                                    <div class="full-row text-italic">Catatan Klien: "<?php echo htmlspecialchars($bk['catatan'] ?? '-'); ?>"</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bk-footer-actions">
                            <button class="btn-outline-grey"><i class="fa-regular fa-comment-dots"></i> Chat Klien</button>
                            <div class="action-group">
                                <?php if($tab == 'ongoing'): ?>
                                    <button class="btn-filled-green"><i class="fa-solid fa-check"></i> Konfirmasi</button>
                                    <button class="btn-outline-red"><i class="fa-solid fa-xmark"></i> Tolak</button>
                                <?php elseif($tab == 'scheduled'): ?>
                                    <button class="btn-filled-red"><i class="fa-regular fa-circle-xmark"></i> Batalkan Sesi</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>
        </div>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const navLinks = document.querySelectorAll('.nav-menu-mua a');
            navLinks.forEach(link => link.classList.remove('active'));
            if(navLinks[2]) navLinks[2].classList.add('active'); 

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
                        emptyState.innerHTML = 'Belum ada jadwal pesanan di bulan ini.';
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
                    emptyState.innerHTML = 'Tidak ada jadwal pesanan pada tanggal ini.';
                    
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
</body>
</html>