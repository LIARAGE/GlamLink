/**
 * GLAMLINK - HALAMAN NOTIFIKASI MUA
 * Hapus, tandai dibaca, dan tandai semua dibaca tanpa reload halaman.
 * Butuh assets/js/mua_ui.js (GL.toast) dimuat lebih dulu.
 */
(function () {
    'use strict';

    var list = document.getElementById('ntList');
    if (!list) return;
    var hanyaBelum = new URLSearchParams(window.location.search).get('f') === 'belum';

    function kirim(data) {
        var fd = new FormData();
        Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
        return fetch('notifikasi.php', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'fetch' }, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); });
    }

    // Perbarui semua angka: tombol "Tandai Semua", filter, dan badge lonceng di navbar
    function setJumlah(n) {
        var btn = document.getElementById('ntCountBtn');
        if (btn) btn.textContent = n;
        var form = document.getElementById('ntReadAllForm');
        if (form) form.hidden = n === 0;
        var f = document.getElementById('ntCountFilter');
        if (f) { f.textContent = n; f.hidden = n === 0; }
        var badge = document.getElementById('navNotifBadge');
        if (badge) { badge.textContent = n > 99 ? '99+' : n; badge.hidden = n === 0; }
    }

    function cekKosong() {
        if (!list.querySelector('.nt-card')) {
            var e = document.getElementById('ntEmpty');
            e.hidden = false;
            if (window.GL) GL.fadeIn(e);
        }
    }

    // Kartu menyusut lalu hilang
    function hilangkan(card) {
        card.style.height = card.offsetHeight + 'px';
        void card.offsetHeight;
        card.classList.add('is-removing');
        setTimeout(function () { card.remove(); cekKosong(); }, 320);
    }

    function tandaiDibaca(card) {
        card.classList.remove('is-unread');
        var dot = card.querySelector('.nt-dot');
        if (dot) dot.remove();
        if (hanyaBelum) hilangkan(card);
    }

    // --- Hapus satu notifikasi ---
    list.addEventListener('submit', function (e) {
        var form = e.target.closest('.nt-del-form');
        if (!form) return;
        e.preventDefault();
        e.stopPropagation();
        var card = form.closest('.nt-card');
        if (card.classList.contains('is-removing')) return;
        hilangkan(card);
        kirim({ hapus: 1, id_notif: card.dataset.id })
            .then(function (res) { setJumlah(res.belum_dibaca); GL.toast('Notifikasi dihapus.', 'success', 2000); })
            .catch(function () { GL.toast('Gagal menghapus. Muat ulang halaman lalu coba lagi.', 'error'); });
    });

    // --- Klik kartu (di luar tombol) = tandai dibaca ---
    list.addEventListener('click', function (e) {
        var card = e.target.closest('.nt-card');
        if (!card || !card.classList.contains('is-unread')) return;
        if (e.target.closest('.nt-del-form')) return;
        var keLink = e.target.closest('.nt-btn-action');
        tandaiDibaca(card);
        if (keLink) {
            // Server menandai dibaca lewat ?buka=. Kurangi angka di layar sekarang juga
            var btn = document.getElementById('ntCountBtn');
            setJumlah(Math.max(0, (btn ? +btn.textContent : 1) - 1));
            return;
        }
        kirim({ baca: 1, id_notif: card.dataset.id }).then(function (res) { setJumlah(res.belum_dibaca); }).catch(function () {});
    });

    // --- Tandai semua dibaca ---
    var formSemua = document.getElementById('ntReadAllForm');
    if (formSemua) {
        formSemua.addEventListener('submit', function (e) {
            e.preventDefault();
            e.stopPropagation();
            kirim({ baca_semua: 1 })
                .then(function (res) {
                    list.querySelectorAll('.nt-card.is-unread').forEach(tandaiDibaca);
                    setJumlah(res.belum_dibaca);
                    GL.toast('Semua notifikasi ditandai dibaca.', 'success', 2200);
                })
                .catch(function () { GL.toast('Gagal memperbarui. Coba lagi.', 'error'); });
        });
    }
})();