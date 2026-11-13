<?php
/**
 * Helper toast notifikasi untuk halaman MUA (pengganti alert()).
 *
 * Pemakaian:
 *   require_once '../includes/mua_ui.php';
 *   mua_toast('Pesanan berhasil dikonfirmasi', 'success');   // 'success' | 'error' | 'info'
 *   header('Location: booking.php'); exit();                // toast tampil di halaman berikutnya
 *
 * Di halaman (sebelum <script src="../assets/js/mua_ui.js">):
 *   <?php mua_toast_render(); ?>
 */

function mua_toast(string $pesan, string $tipe = 'success'): void {
    if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
    $_SESSION['mua_toast'][] = ['msg' => $pesan, 'type' => $tipe];
}

function mua_toast_render(): void {
    $daftar = $_SESSION['mua_toast'] ?? [];
    unset($_SESSION['mua_toast']);
    // JSON_HEX_* membuat pesan aman ditaruh di dalam <script>
    echo '<script>window.GL_TOASTS = ' . json_encode($daftar, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>' . "\n";
}