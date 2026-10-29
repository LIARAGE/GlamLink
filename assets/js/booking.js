/**
 * Wizard Booking MUA (4 langkah). Data jadwal & konfigurasi dari <script id="bk-data"> (dibuat server).
 * JS hanya mengatur tampilan/validasi awal; server tetap memvalidasi ulang semuanya saat "Bayar Sekarang".
 */
(function () {
    'use strict';
    var cfg = JSON.parse(document.getElementById('bk-data').textContent);
    var form = document.getElementById('bk-form');
    var $ = function (s) { return document.querySelector(s); };
    var $$ = function (s) { return Array.prototype.slice.call(document.querySelectorAll(s)); };

    var HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    var BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    var DOW = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    var pad = function (n) { return (n < 10 ? '0' : '') + n; };
    var ymd = function (y, m, d) { return y + '-' + pad(m + 1) + '-' + pad(d); };       // m = 0..11
    var rp = function (n) { return 'Rp' + String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };
    var parse = function (t) { var p = t.split('-'); return { y: +p[0], m: +p[1] - 1, d: +p[2] }; };
    var tglIndo = function (t) { var p = parse(t); return p.d + ' ' + BULAN[p.m] + ' ' + p.y; };
    var tglPanjang = function (t) { var p = parse(t); return HARI[new Date(p.y, p.m, p.d).getDay()] + ', ' + tglIndo(t); };

    var st = { step: cfg.langkah || 1, tanggal: cfg.awal.tanggal || '', jam: cfg.awal.jam || '', bulan: null };
    var awal = parse(cfg.hariIni), akhir = parse(cfg.maxTanggal);
    var idxBulan = function (y, m) { return y * 12 + m; };
    var minB = idxBulan(awal.y, awal.m), maxB = idxBulan(akhir.y, akhir.m);

    /* ---------- Ketersediaan ---------- */
    function slotIndex(jam) { for (var i = 0; i < cfg.slot.length; i++) if (cfg.slot[i][0] === jam) return i; return -1; }
    function slotBebas(tg, i) {
        var isi = cfg.terisi[tg] || [];
        if (isi.indexOf(i) > -1) return false;
        if (tg === cfg.hariIni && cfg.slot[i][0] <= cfg.jamSekarang) return false;
        return true;
    }
    function statusHari(tg) {
        if (tg < cfg.hariIni || tg > cfg.maxTanggal) return 'lewat';
        if (cfg.libur.indexOf(tg) > -1) return 'cuti';
        for (var i = 0; i < cfg.slot.length; i++) if (slotBebas(tg, i)) return 'tersedia';
        return 'penuh';
    }
    function jadwalValid() {
        var i = slotIndex(st.jam);
        return !!st.tanggal && i > -1 && statusHari(st.tanggal) === 'tersedia' && slotBebas(st.tanggal, i);
    }

    /* ---------- Ringkasan ---------- */
    function layananTerpilih() { return $('input[name="layanan"]:checked'); }
    function ringkasan() {
        var l = layananTerpilih(), harga = l ? +l.dataset.harga : 0, fee = Math.round(harga * cfg.persen / 100);
        $('#s-layanan').textContent = l ? l.dataset.nama : '-';
        $('#s-tanggal').textContent = st.tanggal ? tglIndo(st.tanggal) : 'Belum Dipilih';
        var i = slotIndex(st.jam);
        $('#s-waktu').textContent = i > -1 ? cfg.slot[i][0] + '\u2013' + cfg.slot[i][1] : 'Belum Dipilih';
        $('#s-lokasi').textContent = $('#f-kota').value || 'Belum Dipilih';
        $('#s-biaya').textContent = l ? rp(harga) : '-';
        $('#s-fee').textContent = l ? rp(fee) : '-';
        $('#s-total').textContent = l ? rp(harga + fee) : '-';
        $('#f-tanggal').value = st.tanggal;
        $('#f-waktu').value = st.jam;
    }

    /* ---------- Kalender + slot jam (langkah 2) ---------- */
    function renderKalender() {
        var b = st.bulan, first = new Date(b.y, b.m, 1), jml = new Date(b.y, b.m + 1, 0).getDate();
        var lead = (first.getDay() + 6) % 7;                       // Senin = 0
        var cur = idxBulan(b.y, b.m), h = '';
        h += '<div class="bk-cal-head"><button type="button" data-nav="-1" aria-label="Bulan sebelumnya"' + (cur <= minB ? ' disabled' : '') + '><i class="fa-solid fa-chevron-left"></i></button>' +
             '<b>' + BULAN[b.m] + ' ' + b.y + '</b><button type="button" data-nav="1" aria-label="Bulan berikutnya"' + (cur >= maxB ? ' disabled' : '') + '><i class="fa-solid fa-chevron-right"></i></button></div>';
        h += '<div class="bk-cal-grid">';
        DOW.forEach(function (d) { h += '<span class="bk-dow' + (d === 'Sun' ? ' sun' : '') + '">' + d + '</span>'; });
        for (var k = 0; k < lead; k++) h += '<span></span>';
        for (var d = 1; d <= jml; d++) {
            var tg = ymd(b.y, b.m, d), s = statusHari(tg), on = tg === st.tanggal ? ' on' : '';
            if (s === 'lewat') h += '<span class="bk-day s-lewat">' + d + '</span>';
            else if (s === 'tersedia') h += '<button type="button" class="bk-day s-tersedia' + on + '" data-tgl="' + tg + '" aria-label="' + tglPanjang(tg) + ', tersedia"><span>' + d + '</span><i></i></button>';
            else h += '<span class="bk-day s-' + s + '" title="' + (s === 'cuti' ? 'MUA cuti' : 'Semua jam penuh') + '">' + d + '<i></i></span>';
        }
        h += '</div><div class="bk-legend"><span><i class="dot t"></i>Tersedia</span><span><i class="dot c"></i>Cuti</span><span><i class="dot p"></i>Penuh</span></div>';
        $('#bk-cal').innerHTML = h;
    }
    function renderSlots() {
        var el = $('#bk-slots');
        if (!st.tanggal) { el.innerHTML = '<span class="bk-over">JAM YANG TERSEDIA</span><p class="bk-hint"><i class="fa-regular fa-calendar"></i> Pilih tanggal pada kalender terlebih dahulu.</p>'; return; }
        var h = '<span class="bk-over">JAM YANG TERSEDIA</span><h3>' + tglPanjang(st.tanggal) + '</h3><div class="bk-slot-list">';
        cfg.slot.forEach(function (sl, i) {
            var label = sl[0] + ' - ' + sl[1];
            if (!slotBebas(st.tanggal, i)) {
                var lewat = st.tanggal === cfg.hariIni && sl[0] <= cfg.jamSekarang && (cfg.terisi[st.tanggal] || []).indexOf(i) < 0;
                h += '<div class="bk-slot full"><span>' + label + '</span><em>' + (lewat ? 'Lewat' : 'Penuh') + '</em></div>';
            } else h += '<button type="button" class="bk-slot' + (st.jam === sl[0] ? ' on' : '') + '" data-jam="' + sl[0] + '">' + label + '</button>';
        });
        el.innerHTML = h + '</div>';
    }
    function pilihTanggal(tg) {
        st.tanggal = tg;
        var i = slotIndex(st.jam);
        if (i < 0 || !slotBebas(tg, i)) st.jam = '';               // jam lama tidak tersedia di tanggal baru
        renderKalender(); renderSlots(); ringkasan(); tombol();
    }
    $('#bk-cal').addEventListener('click', function (ev) {
        var nav = ev.target.closest('[data-nav]'), hari = ev.target.closest('[data-tgl]');
        if (nav && !nav.disabled) {
            var n = idxBulan(st.bulan.y, st.bulan.m) + (+nav.dataset.nav);
            if (n >= minB && n <= maxB) { st.bulan = { y: Math.floor(n / 12), m: n % 12 }; renderKalender(); }
        } else if (hari) pilihTanggal(hari.dataset.tgl);
    });
    $('#bk-slots').addEventListener('click', function (ev) {
        var b = ev.target.closest('[data-jam]');
        if (b) { st.jam = b.dataset.jam; renderSlots(); ringkasan(); tombol(); }
    });

    /* ---------- Validasi per langkah & tombol ---------- */
    function valid(n) {
        if (n === 1) return !!layananTerpilih();
        if (n === 2) return jadwalValid();
        if (n === 3) return !!($('#f-jenis').value && $('#f-kota').value && $('#f-alamat').value.trim());
        if (n === 4) return !!$('input[name="metode"]:checked');
        return false;
    }
    function tombol() {
        var ok = valid(st.step);
        [$('#bk-next'), $('#bk-next1'), $('#bk-bayar')].forEach(function (b) { b.disabled = !ok; });
    }
    function tampil(n, scroll) {
        st.step = n;
        $$('.bk-panel').forEach(function (p) { p.hidden = +p.dataset.step !== n; });
        $$('.bk-step').forEach(function (s) { var k = +s.dataset.step; s.classList.toggle('on', k <= n); s.classList.toggle('now', k === n); });
        $('#bk-actions').hidden = n === 1;
        $('#bk-next1').hidden = n !== 1;
        $('#bk-next').hidden = n === 4;
        $('#bk-bayar').hidden = n !== 4;
        if (n === 2) { if (!st.bulan) st.bulan = st.tanggal ? { y: parse(st.tanggal).y, m: parse(st.tanggal).m } : { y: awal.y, m: awal.m }; renderKalender(); renderSlots(); }
        ringkasan(); tombol();
        if (scroll) window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    function maju() { if (valid(st.step) && st.step < 4) tampil(st.step + 1, true); }
    $('#bk-next').addEventListener('click', maju);
    $('#bk-next1').addEventListener('click', maju);
    $('#bk-back').addEventListener('click', function () { if (st.step > 1) tampil(st.step - 1, true); });

    /* ---------- Layanan: pilih, cari ---------- */
    $$('input[name="layanan"]').forEach(function (r) {
        r.addEventListener('change', function () {
            var j = $('#f-jenis'); if (!j.value) { for (var i = 0; i < j.options.length; i++) if (j.options[i].value === r.dataset.kategori) j.value = r.dataset.kategori; }
            ringkasan(); tombol();
        });
    });
    $('#bk-cari').addEventListener('input', function () {
        var q = this.value.trim().toLowerCase(), ada = 0;
        $$('.bk-svc-list .bk-svc').forEach(function (l) { var cocok = !q || l.dataset.cari.indexOf(q) > -1; l.hidden = !cocok; if (cocok) ada++; });
        $('#bk-cari-kosong').hidden = ada > 0;
    });

    /* ---------- Detail acara & pembayaran ---------- */
    ['#f-jenis', '#f-kota', '#f-alamat'].forEach(function (s) { $(s).addEventListener('input', function () { ringkasan(); tombol(); }); $(s).addEventListener('change', function () { ringkasan(); tombol(); }); });
    $$('input[name="metode"]').forEach(function (r) { r.addEventListener('change', tombol); });

    // Enter di kolom teks tidak boleh mengirim form sebelum langkah 4
    form.addEventListener('keydown', function (ev) { if (ev.key === 'Enter' && ev.target.tagName !== 'TEXTAREA') ev.preventDefault(); });
    form.addEventListener('submit', function (ev) {
        if (st.step !== 4) { ev.preventDefault(); return; }
        for (var n = 1; n <= 4; n++) if (!valid(n)) { ev.preventDefault(); tampil(n, true); return; }   // lompat ke langkah yang belum lengkap
        var b = $('#bk-bayar'); b.disabled = true; b.textContent = 'Memproses...';                      // cegah klik ganda
    });

    /* ---------- Mulai ---------- */
    if (st.tanggal && (statusHari(st.tanggal) !== 'tersedia')) { st.tanggal = ''; st.jam = ''; }
    if (st.tanggal && st.jam && !jadwalValid()) st.jam = '';
    var mulai = st.step;
    if (mulai >= 3 && !jadwalValid()) mulai = 2;                     // jadwal dari URL sudah tidak tersedia -> pilih ulang
    if (mulai >= 2 && !valid(1)) mulai = 1;
    tampil(mulai, false);
})();
