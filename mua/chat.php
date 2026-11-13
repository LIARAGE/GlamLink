<?php
/**
 * CHAT MUA <-> KLIEN (sisi MUA)
 * Dibuka dari ikon chat di navbar atau tombol "Chat Klien" di halaman Booking (chat.php?dengan=ID_KLIEN).
 * Logika & API ada di includes/chat.php, tampilan di includes/chat_view.php.
 */
session_start();
require_once '../includes/auth_mua.php';   // koneksi + cek login MUA + $id_mua
require_once '../includes/mua_ui.php';     // toast
require_once '../includes/chat.php';

chat_api($koneksi, $id_mua, 'mua');        // permintaan API (fetch) berhenti di sini

$chat_saya    = $id_mua;
$chat_role    = 'mua';
$chat_kembali = 'index.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - GlamLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/chat.css">
    <link rel="stylesheet" href="../assets/css/mua_ui.css">
</head>
<body class="ct-body">
    <?php include '../navbar.php'; ?>
    <?php include '../includes/chat_view.php'; ?>

    <?php mua_toast_render(); ?>
    <script src="../assets/js/mua_ui.js"></script>
    <script src="../assets/js/chat.js"></script>
</body>
</html>