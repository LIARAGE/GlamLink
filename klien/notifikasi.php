<?php
/**
 * HALAMAN NOTIFIKASI KLIEN (dibuka dari ikon lonceng di navbar klien)
 * Data: includes/klien_notifikasi.php (tabel klien_notifikasi)
 */
session_start();
require __DIR__ . '/../includes/klien.php';             // penjaga login klien + fungsi bantu (e, csrf_field, bottom_nav, ...)
require_once __DIR__ . '/../includes/klien_notifikasi.php';

klien_notif_sinkron($koneksi, $id_klien);

// Klik satu notifikasi: tandai dibaca lalu buka halaman terkait (hanya milik sendiri)
if (isset($_GET['buka'])) {
    $n = q('SELECT id_notif, link FROM klien_notifikasi WHERE id_notif = ? AND id_pelanggan = ? AND dihapus_pada IS NULL', [(int)$_GET['buka'], $id_klien])[0] ?? null;
    if ($n) {
        q('UPDATE klien_notifikasi SET dibaca_pada = NOW() WHERE id_notif = ? AND id_pelanggan = ? AND dibaca_pada IS NULL', [$n['id_notif'], $id_klien]);
        header('Location: ' . klien_notif_link_aman($n['link'])); exit();
    }
    header('Location: notifikasi.php'); exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok() && ($_POST['aksi'] ?? '') === 'semua') {
    q('UPDATE klien_notifikasi SET dibaca_pada = NOW() WHERE id_pelanggan = ? AND dibaca_pada IS NULL', [$id_klien]);
    header('Location: notifikasi.php?msg=semua'); exit();
}
$daftar = klien_notif_daftar($koneksi, $id_klien);
$belum  = count(array_filter($daftar, function ($n) { return $n['dibaca_pada'] === null; }));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - GlamLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/klien.css">
    <link rel="stylesheet" href="../assets/css/klien_notifikasi.css">
</head>
<body>
<?php include __DIR__ . '/../navbar.php'; ?>
<section class="nf-hero"><h1>Notifications</h1></section>
<main class="nf-wrap">
    <?php if (isset($_GET['msg'])) echo '<p class="alert-ok">Semua notifikasi ditandai sudah dibaca.</p>'; ?>
    <?php if ($belum > 0): ?>
        <form method="POST" class="nf-tools"><?php echo csrf_field(); ?><input type="hidden" name="aksi" value="semua">
            <span class="muted"><?php echo $belum; ?> belum dibaca</span>
            <button type="submit" class="nf-readall"><i class="fa-solid fa-check-double"></i> Tandai semua dibaca</button></form>
    <?php endif; ?>

    <?php foreach ($daftar as $n): $baru = $n['dibaca_pada'] === null; ?>
        <a class="nf-card<?php echo $baru ? ' unread' : ''; ?>" href="notifikasi.php?buka=<?php echo (int)$n['id_notif']; ?>">
            <span class="nf-icon"><i class="<?php echo e(KLIEN_NOTIF_IKON[$n['tipe']] ?? 'fa-regular fa-bell'); ?>"></i></span>
            <div class="nf-body">
                <div class="nf-meta"><?php echo $baru ? '<i class="nf-dot"></i>' : ''; ?><span><?php echo e(klien_notif_relatif((int)$n['detik'])); ?></span></div>
                <h3><?php echo e($n['judul']); ?></h3>
                <p><?php echo e($n['pesan']); ?></p>
                <?php if ($n['label_aksi']) echo '<span class="nf-aksi">' . e($n['label_aksi']) . ' <i class="fa-solid fa-chevron-right"></i></span>'; ?>
            </div>
        </a>
    <?php endforeach; ?>
    <?php if (!$daftar): ?><div class="kosong"><i class="fa-regular fa-bell-slash"></i><p>Belum ada notifikasi. Notifikasi muncul saat booking Anda dibuat, dikonfirmasi, atau selesai.</p></div><?php endif; ?>
</main>
<?php bottom_nav();