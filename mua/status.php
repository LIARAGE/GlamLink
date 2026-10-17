<?php
session_start();
require '../koneksi.php';
require __DIR__ . '/../Includes/auth_mua.php';

// Sudah disetujui admin -> langsung ke dashboard
if ($status_verifikasi === 'approved') { header('Location: index.php'); exit(); }
$ditolak = ($status_verifikasi === 'rejected');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Verifikasi - GlamLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/mua_status.css">
</head>
<body>
    <?php include '../navbar.php'; ?>

    <main class="status-container">
        <div class="status-card <?php echo $ditolak ? 'is-rejected' : 'is-pending'; ?>">
            <div class="status-icon"><i class="fa-solid <?php echo $ditolak ? 'fa-circle-xmark' : 'fa-hourglass-half'; ?>"></i></div>
            <span class="overline"><?php echo $ditolak ? 'VERIFIKASI DITOLAK' : 'MENUNGGU VERIFIKASI'; ?></span>

            <?php if ($ditolak): ?>
                <h2>Pendaftaran Anda belum dapat disetujui</h2>
                <p>Mohon maaf, tim GlamLink belum dapat menyetujui akun MUA Anda saat ini.</p>
                <?php if (!empty($akun['alasan_tolak'])): ?>
                    <div class="reason-box"><b>Alasan:</b> <?php echo nl2br(htmlspecialchars($akun['alasan_tolak'])); ?></div>
                <?php endif; ?>
            <?php else: ?>
                <h2>Akun Anda sedang ditinjau admin</h2>
                <p>Terima kasih sudah mendaftar! Tim kami sedang memeriksa profil dan portfolio Anda. Setelah disetujui, Anda dapat mengelola layanan dan menerima pesanan.</p>
            <?php endif; ?>

            <ol class="status-steps">
                <li class="done"><i class="fa-solid fa-check"></i> Pendaftaran terkirim</li>
                <li class="<?php echo $ditolak ? 'fail' : 'current'; ?>"><i class="fa-solid <?php echo $ditolak ? 'fa-xmark' : 'fa-spinner'; ?>"></i> Verifikasi oleh admin</li>
                <li><i class="fa-regular fa-circle"></i> Akun aktif</li>
            </ol>

            <div class="status-actions">
                <a href="status.php" class="btn-refresh"><i class="fa-solid fa-rotate"></i> Cek Status Lagi</a>
                <a href="../logout.php" class="btn-leave">Keluar</a>
            </div>
        </div>
    </main>
</body>
</html>