<?php
/**
 * Tampilan halaman chat (dipakai mua/chat.php & klien/chat.php).
 * Butuh variabel: $koneksi, $chat_saya (id user), $chat_role ('mua'|'pelanggan'), $chat_kembali (URL tombol kembali)
 * Isi daftar & pesan digambar oleh assets/js/chat.js dari data JSON di bawah.
 */
$chat_dengan = (int)($_GET['dengan'] ?? 0);
$chat_lawan_awal = $chat_dengan ? chat_lawan($koneksi, $chat_saya, $chat_role, $chat_dengan) : null;

$chat_data = [
    'role'       => $chat_role,
    'lawanAwal'  => $chat_lawan_awal,
    'dilarang'   => $chat_dengan && !$chat_lawan_awal,
    'percakapan' => chat_percakapan($koneksi, $chat_saya, $chat_role),
    'saran'      => chat_saran($koneksi, $chat_saya, $chat_role),
    'tanggal'    => chat_tanggal_server($koneksi),
    'maks'       => CHAT_MAKS_PANJANG,
];
?>
<div class="ct-topbar">
    <div class="ct-topbar-inner">
        <a href="<?php echo chat_e($chat_kembali); ?>" class="ct-btn-back" aria-label="Kembali"><i class="fa-solid fa-arrow-left"></i></a>
        <h1>Chat</h1>
    </div>
</div>

<main class="ct-main" id="ctApp">
    <!-- DAFTAR PERCAKAPAN -->
    <aside class="ct-side">
        <h2>Chat</h2>
        <div class="ct-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="search" id="ctCari" placeholder="Cari nama..." autocomplete="off" aria-label="Cari percakapan">
        </div>
        <div class="ct-list" id="ctList" role="list"></div>
        <div class="ct-saran" id="ctSaranWrap" hidden>
            <span class="ct-saran-title"><?php echo $chat_role === 'mua' ? 'Klien dari pesananmu' : 'MUA yang pernah kamu pesan'; ?></span>
            <div id="ctSaran"></div>
        </div>
    </aside>

    <!-- RUANG CHAT -->
    <section class="ct-room" id="ctRoom">
        <div class="ct-placeholder" id="ctPlaceholder">
            <i class="fa-regular fa-comments"></i>
            <h3>Pilih percakapan</h3>
            <p><?php echo $chat_role === 'mua'
                ? 'Balas pertanyaan klien atau hubungi klien dari halaman Booking.'
                : 'Tanyakan paket, jadwal, atau detail riasan langsung ke MUA.'; ?></p>
        </div>

        <header class="ct-room-head" id="ctHead" hidden>
            <button type="button" class="ct-btn-list" id="ctBtnList" aria-label="Kembali ke daftar chat"><i class="fa-solid fa-chevron-left"></i></button>
            <div class="ct-avatar" id="ctHeadAvatar"></div>
            <div>
                <b id="ctHeadNama"></b>
                <small id="ctHeadLabel"></small>
            </div>
        </header>

        <div class="ct-msgs" id="ctMsgs" aria-live="polite" hidden></div>

        <form class="ct-form" id="ctForm" hidden autocomplete="off" data-no-loading>
            <textarea id="ctInput" rows="1" maxlength="<?php echo CHAT_MAKS_PANJANG; ?>" placeholder="Tulis pesan..." aria-label="Tulis pesan"></textarea>
            <button type="submit" class="ct-send" id="ctSend" aria-label="Kirim"><i class="fa-solid fa-paper-plane"></i></button>
        </form>
    </section>
</main>

<script id="ctData" type="application/json"><?php echo json_encode($chat_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>