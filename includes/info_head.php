<?php
/**
 * Bagian ATAS halaman info (head + navbar). Dipakai halaman di folder utama:
 *   $judul_halaman = 'Tentang Kami';
 *   $deskripsi_halaman = '...';            // opsional, untuk meta description
 *   include 'includes/info_head.php';
 * Pasangannya: includes/info_foot.php
 */
require_once __DIR__ . '/site_info.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo site_e($judul_halaman ?? 'GlamLink'); ?> - GlamLink</title>
    <?php if (!empty($deskripsi_halaman)): ?><meta name="description" content="<?php echo site_e($deskripsi_halaman); ?>"><?php endif; ?>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/landing.css">
    <link rel="stylesheet" href="assets/css/info.css">
</head>
<body>
    <?php include __DIR__ . '/../navbar.php'; ?>
