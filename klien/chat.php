<?php
/**
 * CHAT KLIEN <-> MUA (sisi KLIEN) - FILE BARU
 * Dibuka dari ikon chat di navbar klien, atau link chat.php?dengan=ID_MUA
 * (misalnya tombol "Chat MUA" di halaman profil MUA).
 * Logika & API ada di includes/chat.php, tampilan di includes/chat_view.php (dipakai bersama sisi MUA).
 */
session_start();
require '../koneksi.php';

if (!isset($_SESSION['status_login']) || ($_SESSION['role'] ?? '') !== 'pelanggan') {
    header('Location: ../login.php');
    exit();
}
require_once '../includes/chat.php';

$chat_saya    = (int)$_SESSION['id_user'];
chat_api($koneksi, $chat_saya, 'pelanggan');   // permintaan API (fetch) berhenti di sini

$chat_role    = 'pelanggan';
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

    <script src="../assets/js/mua_ui.js"></script>
    <script src="../assets/js/chat.js"></script>
</body>
</html>