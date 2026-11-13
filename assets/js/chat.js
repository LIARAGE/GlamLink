/**
 * GLAMLINK - CHAT KLIEN <-> MUA
 * Dipakai oleh mua/chat.php dan klien/chat.php (API ada di halaman itu sendiri: chat.php?api=...)
 * - Pesan baru diambil otomatis tiap 3 detik (polling), daftar chat tiap 8 detik.
 * - Polling berhenti saat tab browser tidak aktif, supaya hemat server.
 * - Pesan langsung tampil saat dikirim, lalu dikonfirmasi server.
 */
(function () {
    'use strict';

    var dataEl = document.getElementById('ctData');
    if (!dataEl) return;
    var D = JSON.parse(dataEl.textContent);

    var $ = function (id) { return document.getElementById(id); };
    var el = function (tag, cls, teks) {
        var n = document.createElement(tag);
        if (cls) n.className = cls;
        if (teks !== undefined) n.textContent = teks;          // textContent = aman dari HTML injection
        return n;
    };
    var BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    var toast = function (m, t) { if (window.GL) GL.toast(m, t || 'error'); };

    var app = $('ctApp'), list = $('ctList'), msgs = $('ctMsgs'), form = $('ctForm'), input = $('ctInput');
    var aktif = null;              // lawan bicara yang sedang dibuka
    var lastId = 0;                // id pesan terakhir yang sudah tampil
    var tanggalTerakhir = null;    // untuk pemisah tanggal
    var percakapan = D.percakapan || [];
    var sibuk = false;

    function api(params, body) {
        var url = 'chat.php?' + new URLSearchParams(params).toString();
        var opsi = { headers: { 'X-Requested-With': 'fetch' }, credentials: 'same-origin', cache: 'no-store' };
        if (body) { opsi.method = 'POST'; opsi.body = body; }
        return fetch(url, opsi).then(function (r) {
            return r.json().then(function (j) { if (!r.ok) throw new Error(j.error || 'Gagal'); return j; });
        });
    }

    function avatar(u, cls) {
        var a = el('div', 'ct-avatar ' + (cls || ''));
        if (u.foto) { var img = el('img'); img.src = u.foto; img.alt = ''; a.appendChild(img); }
        else a.textContent = (u.nama || '?').trim().charAt(0).toUpperCase();
        return a;
    }

    function setBadge(n) {
        var b = $('navChatBadge');
        if (b) { b.textContent = n > 99 ? '99+' : n; b.hidden = !n; }
    }

    /* ---------------- DAFTAR PERCAKAPAN ---------------- */
    function renderList() {
        var q = ($('ctCari').value || '').trim().toLowerCase();
        list.innerHTML = '';
        var tampil = percakapan.filter(function (p) { return !q || p.nama.toLowerCase().indexOf(q) > -1; });

        // Lawan yang dibuka dari link (?dengan=) tapi belum ada pesannya
        if (aktif && !percakapan.some(function (p) { return p.id === aktif.id; }) && (!q || aktif.nama.toLowerCase().indexOf(q) > -1)) {
            tampil.unshift({ id: aktif.id, nama: aktif.nama, foto: aktif.foto, label: aktif.label, cuplikan: 'Percakapan baru', waktu: '', belum: 0, baru: true });
        }

        if (!tampil.length) {
            list.appendChild(el('p', 'ct-list-empty', q ? 'Tidak ada yang cocok.' : 'Belum ada percakapan.'));
        }
        tampil.forEach(function (p) {
            var item = el('button', 'ct-item' + (aktif && aktif.id === p.id ? ' active' : '') + (p.belum ? ' unread' : ''));
            item.type = 'button';
            item.setAttribute('role', 'listitem');
            item.appendChild(avatar(p));
            var isi = el('div', 'ct-item-body');
            var atas = el('div', 'ct-item-top');
            atas.appendChild(el('b', '', p.nama));
            atas.appendChild(el('time', '', p.waktu || ''));
            isi.appendChild(atas);
            var bawah = el('div', 'ct-item-bottom');
            bawah.appendChild(el('span', 'ct-preview', (p.dari_saya ? 'Kamu: ' : '') + p.cuplikan));
            if (p.belum) bawah.appendChild(el('span', 'ct-unread', String(p.belum)));
            isi.appendChild(bawah);
            item.appendChild(isi);
            item.addEventListener('click', function () { buka({ id: p.id, nama: p.nama, foto: p.foto, label: p.label }); });
            list.appendChild(item);
        });

        // Saran mulai chat
        var saran = (D.saran || []).filter(function (s) { return !percakapan.some(function (p) { return p.id === s.id; }) && !(aktif && aktif.id === s.id); });
        $('ctSaranWrap').hidden = !saran.length || !!q;
        var wadah = $('ctSaran');
        wadah.innerHTML = '';
        saran.forEach(function (s) {
            var b = el('button', 'ct-saran-item');
            b.type = 'button';
            b.appendChild(avatar(s, 'sm'));
            b.appendChild(el('span', '', s.nama));
            b.appendChild(el('i', 'fa-regular fa-paper-plane'));
            b.addEventListener('click', function () { buka(s); });
            wadah.appendChild(b);
        });
    }

    /* ---------------- PESAN ---------------- */
    function labelTanggal(t) {
        if (t === D.tanggal.hariIni) return 'Hari ini';
        if (t === D.tanggal.kemarin) return 'Kemarin';
        var p = t.split('-');
        return +p[2] + ' ' + BULAN[+p[1] - 1] + ' ' + p[0];
    }

    function centang(bubble, status) {      // status: 'kirim' | 'terkirim' | 'dibaca' | 'gagal'
        var c = bubble.querySelector('.ct-check');
        if (!c) return;
        c.className = 'ct-check ' + status;
        c.innerHTML = status === 'kirim' ? '<i class="fa-regular fa-clock"></i>'
                    : status === 'gagal' ? '<i class="fa-solid fa-circle-exclamation"></i>'
                    : '<i class="fa-solid fa-check-double"></i>';
        c.title = { kirim: 'Mengirim...', terkirim: 'Terkirim', dibaca: 'Dibaca', gagal: 'Gagal terkirim' }[status];
    }

    function buatBubble(m) {
        var b = el('div', 'ct-msg ' + (m.saya ? 'me' : 'them'));
        if (m.id) b.dataset.id = m.id;
        b.appendChild(el('p', '', m.pesan));
        var meta = el('span', 'ct-meta');
        meta.appendChild(el('time', '', m.jam || ''));
        if (m.saya) meta.appendChild(el('span', 'ct-check'));
        b.appendChild(meta);
        if (m.saya) centang(b, m.status || (m.dibaca ? 'dibaca' : 'terkirim'));
        return b;
    }

    function dekatBawah() { return msgs.scrollHeight - msgs.scrollTop - msgs.clientHeight < 120; }
    function keBawah(halus) { msgs.scrollTo({ top: msgs.scrollHeight, behavior: halus ? 'smooth' : 'auto' }); }

    function tambahPesan(daftar, paksaScroll) {
        var bawah = dekatBawah();
        daftar.forEach(function (m) {
            if (msgs.querySelector('.ct-msg[data-id="' + m.id + '"]')) return;     // sudah ada
            // Pesan saya yang masih "mengirim" dengan isi sama: ganti, jangan dobel
            if (m.saya) {
                var tmp = Array.prototype.find.call(msgs.querySelectorAll('.ct-msg.me.pending'), function (x) { return x.dataset.teks === m.pesan; });
                if (tmp) { tmp.classList.remove('pending'); tmp.dataset.id = m.id; tmp.querySelector('time').textContent = m.jam; centang(tmp, m.dibaca ? 'dibaca' : 'terkirim'); lastId = Math.max(lastId, m.id); return; }
            }
            if (m.tanggal !== tanggalTerakhir) {
                msgs.appendChild(el('div', 'ct-date', labelTanggal(m.tanggal)));
                tanggalTerakhir = m.tanggal;
            }
            msgs.appendChild(buatBubble(m));
            lastId = Math.max(lastId, m.id);
        });
        var kosong = msgs.querySelector('.ct-empty-room');
        if (kosong && msgs.querySelector('.ct-msg')) kosong.remove();
        if (paksaScroll || bawah) keBawah(!paksaScroll);
    }

    function updateDibaca(sampai) {
        msgs.querySelectorAll('.ct-msg.me[data-id]').forEach(function (b) {
            if (+b.dataset.id <= sampai) centang(b, 'dibaca');
        });
    }

    /* ---------------- BUKA PERCAKAPAN ---------------- */
    function buka(lawan) {
        aktif = lawan;
        lastId = 0;
        tanggalTerakhir = null;
        msgs.innerHTML = '';
        $('ctPlaceholder').hidden = true;
        $('ctHead').hidden = false;
        msgs.hidden = false;
        form.hidden = false;
        var av = $('ctHeadAvatar');
        av.replaceWith(avatar(lawan));
        app.querySelector('.ct-room-head .ct-avatar').id = 'ctHeadAvatar';
        $('ctHeadNama').textContent = lawan.nama;
        $('ctHeadLabel').textContent = lawan.label;
        app.classList.add('show-room');

        var url = new URL(window.location.href);
        url.searchParams.set('dengan', lawan.id);
        history.replaceState(null, '', url);

        msgs.appendChild(el('div', 'ct-loading', 'Memuat pesan...'));
        renderList();
        api({ api: 'pesan', dengan: lawan.id, setelah: 0 }).then(function (res) {
            if (!aktif || aktif.id !== lawan.id) return;
            msgs.innerHTML = '';
            if (!res.pesan.length) {
                var e = el('div', 'ct-empty-room');
                e.appendChild(el('i', 'fa-regular fa-hand'));
                e.appendChild(el('p', '', 'Belum ada pesan. Mulai percakapan dengan ' + lawan.nama + '.'));
                msgs.appendChild(e);
            }
            tambahPesan(res.pesan, true);
            updateDibaca(res.dibaca_sampai);
            setBadge(res.belum);
            percakapan.forEach(function (p) { if (p.id === lawan.id) p.belum = 0; });
            renderList();
        }).catch(function (err) { msgs.innerHTML = ''; toast(err.message); });

        if (window.innerWidth > 860) input.focus();
    }

    /* ---------------- KIRIM ---------------- */
    function kirim() {
        var teks = input.value.trim();
        if (!teks || !aktif || sibuk) return;
        if (teks.length > D.maks) { toast('Pesan terlalu panjang.'); return; }

        var lawanId = aktif.id;
        var hariIni = D.tanggal.hariIni;
        if (tanggalTerakhir !== hariIni) { msgs.appendChild(el('div', 'ct-date', 'Hari ini')); tanggalTerakhir = hariIni; }
        var b = buatBubble({ saya: true, pesan: teks, jam: '', status: 'kirim' });
        b.classList.add('pending');
        b.dataset.teks = teks;
        msgs.appendChild(b);
        var kosong = msgs.querySelector('.ct-empty-room');
        if (kosong) kosong.remove();
        keBawah(true);

        input.value = '';
        tinggiInput();
        sibuk = true;

        var fd = new FormData();
        fd.append('dengan', lawanId);
        fd.append('pesan', teks);
        api({ api: 'kirim' }, fd).then(function (res) {
            if (b.dataset.id) return;                          // sudah diganti oleh polling
            b.classList.remove('pending');
            b.dataset.id = res.pesan.id;
            b.querySelector('time').textContent = res.pesan.jam;
            centang(b, 'terkirim');
            lastId = Math.max(lastId, res.pesan.id);
            muatList();
        }).catch(function (err) {
            b.classList.remove('pending');
            b.classList.add('failed');
            centang(b, 'gagal');
            toast(err.message || 'Pesan gagal terkirim.');
            if (!input.value) { input.value = teks; tinggiInput(); }   // kembalikan teks agar bisa dikirim ulang
        }).finally(function () { sibuk = false; });
    }

    form.addEventListener('submit', function (e) { e.preventDefault(); kirim(); });
    // Enter = kirim, Shift+Enter = baris baru
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) { e.preventDefault(); kirim(); }
    });
    function tinggiInput() { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 140) + 'px'; }
    input.addEventListener('input', tinggiInput);

    $('ctCari').addEventListener('input', renderList);
    $('ctBtnList').addEventListener('click', function () { app.classList.remove('show-room'); });

    /* ---------------- POLLING ---------------- */
    function muatList() {
        return api({ api: 'list' }).then(function (res) {
            percakapan = res.percakapan;
            setBadge(res.belum);
            renderList();
        }).catch(function () {});
    }

    function muatPesanBaru() {
        if (!aktif || document.hidden) return;
        var id = aktif.id;
        api({ api: 'pesan', dengan: id, setelah: lastId }).then(function (res) {
            if (!aktif || aktif.id !== id) return;
            if (res.pesan.length) tambahPesan(res.pesan, false);
            updateDibaca(res.dibaca_sampai);
            setBadge(res.belum);
        }).catch(function () {});
    }

    setInterval(muatPesanBaru, 3000);
    setInterval(function () { if (!document.hidden) muatList(); }, 8000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) { muatPesanBaru(); muatList(); } });

    /* ---------------- MULAI ---------------- */
    if (D.dilarang) toast('Percakapan tidak tersedia untuk pengguna tersebut.');
    renderList();
    if (D.lawanAwal) buka(D.lawanAwal);
    else if (!D.dilarang && percakapan.length && window.innerWidth > 860) {
        var p = percakapan[0];
        buka({ id: p.id, nama: p.nama, foto: p.foto, label: p.label });
    }
})();