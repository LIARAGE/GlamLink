/**
 * GLAMLINK - HALAMAN PROFIL MUA
 * Dipakai oleh: mua/profil.php, mua/profil_edit.php, mua/portfolio_tambah.php
 * Setiap bagian hanya jalan jika elemennya ada di halaman.
 * Butuh assets/js/mua_ui.js (GL.toast, GL.openModal, GL.closeModal) dimuat lebih dulu.
 */
(function () {
    'use strict';

    var $ = function (s, ctx) { return (ctx || document).querySelector(s); };
    var $$ = function (s, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(s)); };
    var el = function (tag, cls, teks) {
        var n = document.createElement(tag);
        if (cls) n.className = cls;
        if (teks !== undefined) n.textContent = teks;   // textContent = aman dari HTML injection
        return n;
    };

    /* =========================================================
       1. TAB MENU (tanpa reload, URL ikut berubah ?tab=...)
       ========================================================= */
    var tabs = $$('.pf-tab');
    var indikator = $('.pf-tab-indicator');

    function geserIndikator(tab, animasi) {
        if (!indikator || !tab) return;
        if (!animasi) indikator.style.transition = 'none';
        indikator.style.width = tab.offsetWidth + 'px';
        indikator.style.transform = 'translateX(' + tab.offsetLeft + 'px)';
        if (!animasi) { void indikator.offsetWidth; indikator.style.transition = ''; }
    }

    function bukaTab(nama, animasi) {
        tabs.forEach(function (t) {
            var aktif = t.dataset.tab === nama;
            t.classList.toggle('active', aktif);
            t.setAttribute('aria-selected', aktif ? 'true' : 'false');
            if (aktif) geserIndikator(t, animasi);
        });
        $$('.pf-panel').forEach(function (p) {
            var aktif = p.dataset.panel === nama;
            if (aktif && !p.classList.contains('active') && window.GL) GL.fadeIn(p);
            p.classList.toggle('active', aktif);
        });
        var url = new URL(window.location.href);
        url.searchParams.set('tab', nama);
        if (nama !== 'jadwal') url.searchParams.delete('tgl');
        history.replaceState(null, '', url);
    }

    if (tabs.length) {
        tabs.forEach(function (t) {
            t.addEventListener('click', function () { bukaTab(t.dataset.tab, true); });
        });
        var awal = $('.pf-tab.active') || tabs[0];
        geserIndikator(awal, false);
        window.addEventListener('resize', function () { geserIndikator($('.pf-tab.active'), false); });
        // Font web selesai dimuat bisa mengubah lebar tab
        if (document.fonts && document.fonts.ready) document.fonts.ready.then(function () { geserIndikator($('.pf-tab.active'), false); });
    }

    /* =========================================================
       2. PORTFOLIO: popup detail (lihat, geser, edit, hapus)
       ========================================================= */
    var modalHapus = $('#pfModalHapus');
    var modalPorto = $('#pfModalPorto');
    var kartuPorto = $$('.pf-porto-card');

    function bukaHapus(id, judul) {
        $('#pfHapusId').value = id;
        $('#pfHapusJudul').textContent = judul;
        GL.openModal(modalHapus);
    }

    if (modalHapus) {
        $$('.pf-porto-del').forEach(function (b) {
            b.addEventListener('click', function (e) {
                e.stopPropagation();                       // jangan ikut membuka popup detail
                bukaHapus(b.dataset.id, b.dataset.judul);
            });
        });
        modalHapus.addEventListener('click', function (e) {
            if (e.target === modalHapus || e.target.closest('[data-close-modal]')) GL.closeModal(modalHapus);
        });
    }

    if (modalPorto && kartuPorto.length) {
        var posisi = 0;
        var img = $('#pfPortoImg'), noImg = $('#pfPortoNoImg');

        function tampilPorto(i) {
            posisi = (i + kartuPorto.length) % kartuPorto.length;
            var d = kartuPorto[posisi].dataset;
            if (d.img) { img.src = d.img; img.alt = d.judul; img.hidden = false; noImg.hidden = true; GL.fadeIn(img); }
            else { img.hidden = true; noImg.hidden = false; }
            $('#pfPortoJudul').textContent = d.judul;
            $('#pfPortoPos').textContent = kartuPorto.length > 1 ? '· ' + (posisi + 1) + ' / ' + kartuPorto.length : '';
            var tags = $('#pfPortoTags');
            tags.innerHTML = '';
            (d.tags ? d.tags.split(',') : []).forEach(function (t) { tags.appendChild(el('span', '', '#' + t.replace(/\s+/g, ''))); });
            var desk = $('#pfPortoDesk');
            desk.textContent = d.desk || 'Belum ada deskripsi. Tambahkan lewat tombol Edit agar klien tahu cerita di balik riasan ini.';
            desk.classList.toggle('pf-muted', !d.desk);
            $('#pfPortoEdit').href = 'portfolio_tambah.php?id=' + d.id;
            $$('.pf-porto-nav', modalPorto).forEach(function (n) { n.hidden = kartuPorto.length < 2; });
        }

        kartuPorto.forEach(function (k, i) {
            k.addEventListener('click', function () { tampilPorto(i); GL.openModal(modalPorto); });
            k.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); tampilPorto(i); GL.openModal(modalPorto); }
            });
        });
        $$('[data-porto-nav]', modalPorto).forEach(function (n) {
            n.addEventListener('click', function () { tampilPorto(posisi + (+n.dataset.portoNav)); });
        });
        modalPorto.addEventListener('click', function (e) {
            if (e.target === modalPorto || e.target.closest('[data-close-modal]')) GL.closeModal(modalPorto);
        });
        document.addEventListener('keydown', function (e) {
            if (!modalPorto.classList.contains('gl-modal-open')) return;
            if (e.key === 'ArrowLeft') tampilPorto(posisi - 1);
            if (e.key === 'ArrowRight') tampilPorto(posisi + 1);
        });
        $('#pfPortoHapus').addEventListener('click', function () {
            var d = kartuPorto[posisi].dataset;
            GL.closeModal(modalPorto);
            setTimeout(function () { bukaHapus(d.id, d.judul); }, 180);
        });

        // Setelah edit, popup karya tersebut langsung terbuka (?lihat=ID)
        var url0 = new URL(window.location.href);
        var lihat = url0.searchParams.get('lihat');
        if (lihat) {
            kartuPorto.forEach(function (k, i) { if (k.dataset.id === lihat) { tampilPorto(i); GL.openModal(modalPorto); } });
            url0.searchParams.delete('lihat');
            history.replaceState(null, '', url0);
        }
    }

    /* =========================================================
       3. KALENDER JADWAL
          Mode "Satu Tanggal"  : klik tanggal -> lihat pesanan, tandai/buka cuti
          Mode "Rentang"       : klik awal + klik akhir -> cuti/buka banyak tanggal sekaligus
          Pintasan             : cuti sebulan penuh, libur rutin mingguan
       ========================================================= */
    var dataEl = $('#pfJadwalData');
    if (dataEl) {
        var D = JSON.parse(dataEl.textContent);
        var HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        var BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        var STATUS = { menunggu: 'Menunggu', dikonfirmasi: 'Dikonfirmasi', selesai: 'Selesai' };
        var pad = function (n) { return (n < 10 ? '0' : '') + n; };
        var ymd = function (y, m, d) { return y + '-' + pad(m + 1) + '-' + pad(d); };
        var parse = function (s) { var p = s.split('-'); return { y: +p[0], m: +p[1] - 1, d: +p[2] }; };
        var keDate = function (s) { var p = parse(s); return new Date(p.y, p.m, p.d); };
        var dariDate = function (d) { return ymd(d.getFullYear(), d.getMonth(), d.getDate()); };
        var tglPanjang = function (s) { var p = parse(s); return HARI[keDate(s).getDay()] + ', ' + p.d + ' ' + BULAN[p.m] + ' ' + p.y; };
        var tglPendek = function (s) { var p = parse(s); return p.d + ' ' + BULAN[p.m].slice(0, 3) + ' ' + p.y; };
        var adaCuti = function (t) { return Object.prototype.hasOwnProperty.call(D.libur, t); };
        var bookingAktif = function (t) { return (D.booking[t] || []).filter(function (b) { return b.status !== 'selesai'; }).length; };

        function daftarTanggal(a, b) {          // semua tanggal dari a s/d b (string Y-m-d)
            var hasil = [], d = keDate(a), akhir = keDate(b);
            while (d <= akhir) { hasil.push(dariDate(d)); d.setDate(d.getDate() + 1); }
            return hasil;
        }

        var mode = 'satu';
        var pilih = D.pilih;                     // mode satu
        var rMulai = null, rSelesai = null, rHover = null;   // mode rentang
        var lihat = parse(pilih);
        var batasMin = parse(D.min), batasMax = parse(D.max);
        var idx = function (y, m) { return y * 12 + m; };

        function setMode(m) {
            mode = m;
            $$('.pf-mode-btn').forEach(function (b) { b.classList.toggle('active', b.dataset.mode === m); });
            $('.pf-cal-card').classList.toggle('is-range-mode', m === 'rentang');
        }

        function kelasRentang() {             // update sorotan rentang tanpa menggambar ulang kalender
            var a = rMulai, b = rSelesai || rHover;
            if (a && b && b < a) { var x = a; a = b; b = x; }
            $$('.pf-cal-day').forEach(function (btn) {
                var t = btn.dataset.tgl;
                var dalam = mode === 'rentang' && a && b && t >= a && t <= b;
                btn.classList.toggle('is-range', !!dalam);
                btn.classList.toggle('is-range-preview', !!(dalam && !rSelesai));
                btn.classList.toggle('is-range-start', mode === 'rentang' && t === a);
                btn.classList.toggle('is-range-end', mode === 'rentang' && !!b && t === b);
            });
        }

        function renderKalender() {
            $('#pfCalTitle').textContent = BULAN[lihat.m] + ' ' + lihat.y;
            $('[data-cal="prev"]').disabled = idx(lihat.y, lihat.m) <= idx(batasMin.y, batasMin.m);
            $('[data-cal="next"]').disabled = idx(lihat.y, lihat.m) >= idx(batasMax.y, batasMax.m);

            var wadah = $('#pfCalDates');
            wadah.innerHTML = '';
            var kosong = (new Date(lihat.y, lihat.m, 1).getDay() + 6) % 7;     // kalender mulai Senin
            var jumlahHari = new Date(lihat.y, lihat.m + 1, 0).getDate();
            for (var i = 0; i < kosong; i++) wadah.appendChild(el('span', 'pf-cal-empty'));

            for (var d = 1; d <= jumlahHari; d++) {
                var tgl = ymd(lihat.y, lihat.m, d);
                var btn = el('button', 'pf-cal-day', String(d));
                btn.type = 'button';
                btn.dataset.tgl = tgl;
                if (new Date(lihat.y, lihat.m, d).getDay() === 0) btn.classList.add('pf-sun');
                if (tgl < D.hariIni) btn.classList.add('is-past');
                if (tgl === D.hariIni) btn.classList.add('is-today');
                if (mode === 'satu' && tgl === pilih) btn.classList.add('is-selected');
                if (adaCuti(tgl)) btn.classList.add('is-cuti');
                if (D.booking[tgl]) {
                    btn.classList.add('has-booking');
                    btn.appendChild(el('i', 'pf-cal-dot'));
                    btn.title = D.booking[tgl].length + ' pesanan';
                }
                btn.addEventListener('click', klikTanggal);
                btn.addEventListener('mouseenter', function () {
                    if (mode === 'rentang' && rMulai && !rSelesai && this.dataset.tgl >= D.hariIni) { rHover = this.dataset.tgl; kelasRentang(); }
                });
                wadah.appendChild(btn);
            }
            kelasRentang();
        }

        function klikTanggal(e) {
            var tgl = this.dataset.tgl;
            // Shift + klik dari mode satu tanggal = langsung jadi rentang
            if (mode === 'satu' && e.shiftKey && pilih && pilih !== tgl) {
                setMode('rentang');
                rMulai = pilih < tgl ? pilih : tgl;
                rSelesai = pilih < tgl ? tgl : pilih;
                if (rMulai < D.hariIni) rMulai = D.hariIni;
                if (rSelesai < D.hariIni) { rMulai = rSelesai = null; GL.toast('Rentang yang dipilih sudah lewat.', 'error'); }
                renderKalender(); renderPanel();
                return;
            }
            if (mode === 'satu') {
                pilih = tgl;
                var url = new URL(window.location.href);
                url.searchParams.set('tgl', pilih);
                history.replaceState(null, '', url);
            } else {
                if (tgl < D.hariIni) { GL.toast('Tanggal sudah lewat tidak bisa diubah.', 'error'); return; }
                if (!rMulai || rSelesai) { rMulai = tgl; rSelesai = null; rHover = null; }
                else if (tgl < rMulai) { rSelesai = rMulai; rMulai = tgl; }
                else { rSelesai = tgl; }
            }
            renderKalender();
            renderPanel();
        }

        function form(isi) {
            var f = el('form');
            f.method = 'POST';
            f.action = 'profil.php?tab=jadwal';
            Object.keys(isi).forEach(function (k) {
                var h = el('input'); h.type = 'hidden'; h.name = k; h.value = isi[k]; f.appendChild(h);
            });
            return f;
        }
        function tombol(cls, name, value, html) {
            var b = el('button', cls); b.type = 'submit'; b.name = name; if (value) b.value = value; b.innerHTML = html; return b;
        }

        function renderPanel() {
            var status = $('#pfDayStatus'), list = $('#pfDayList'), aksi = $('#pfDayAction'), label = $('#pfDayPanel .pf-label');
            status.innerHTML = ''; list.innerHTML = ''; aksi.innerHTML = '';

            /* ---------- MODE RENTANG ---------- */
            if (mode === 'rentang') {
                label.textContent = 'RENTANG CUTI';
                if (!rMulai) {
                    $('#pfDayTitle').textContent = 'Pilih tanggal awal';
                    list.appendChild(el('p', 'pf-muted pf-day-empty', 'Klik tanggal awal di kalender, lalu klik tanggal akhir. Atau pakai pintasan "Cuti sebulan penuh".'));
                    return;
                }
                if (!rSelesai) {
                    $('#pfDayTitle').textContent = tglPendek(rMulai) + ' – …';
                    list.appendChild(el('p', 'pf-muted pf-day-empty', 'Sekarang klik tanggal akhir. Klik tanggal yang sama untuk memilih 1 hari saja.'));
                    var batal = el('button', 'pf-btn-ghost pf-btn-sm', 'Batal pilih');
                    batal.type = 'button';
                    batal.addEventListener('click', function () { rMulai = null; rHover = null; renderKalender(); renderPanel(); });
                    aksi.appendChild(batal);
                    return;
                }

                var semua = daftarTanggal(rMulai, rSelesai);
                var sudah = semua.filter(adaCuti);
                var pesanan = semua.filter(function (t) { return !adaCuti(t) && bookingAktif(t) > 0; });
                var akan = semua.length - sudah.length - pesanan.length;

                $('#pfDayTitle').textContent = rMulai === rSelesai ? tglPanjang(rMulai) : tglPendek(rMulai) + ' – ' + tglPendek(rSelesai);
                var stat = el('div', 'pf-range-stats');
                stat.appendChild(el('span', 'pf-day-badge is-open', semua.length + ' hari dipilih'));
                if (sudah.length) stat.appendChild(el('span', 'pf-day-badge is-cuti', sudah.length + ' sudah cuti'));
                if (pesanan.length) stat.appendChild(el('span', 'pf-day-badge is-warn', pesanan.length + ' ada pesanan'));
                status.appendChild(stat);

                if (semua.length > 366) {
                    list.appendChild(el('p', 'pf-day-warn', 'Rentang maksimal 1 tahun. Pilih rentang yang lebih pendek.'));
                    return;
                }
                if (pesanan.length) {
                    var w = el('p', 'pf-day-warn');
                    w.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> ';
                    w.appendChild(document.createTextNode('Tanggal berikut punya pesanan aktif dan akan DILEWATI: ' +
                        pesanan.slice(0, 6).map(function (t) { var p = parse(t); return p.d + '/' + (p.m + 1); }).join(', ') +
                        (pesanan.length > 6 ? ', …' : '') + '.'));
                    list.appendChild(w);
                }

                var ket = el('input', 'pf-input');
                ket.type = 'text'; ket.name = 'keterangan'; ket.maxLength = 150;
                ket.placeholder = 'Keterangan (opsional), contoh: Liburan akhir tahun';

                var f = form({ cuti_rentang: '1', mulai: rMulai, selesai: rSelesai });
                if (akan > 0) {
                    f.appendChild(ket);
                    f.appendChild(tombol('pf-btn-danger pf-btn-block', 'aksi', 'tutup',
                        '<i class="fa-solid fa-umbrella-beach"></i> Tandai ' + akan + ' Hari sebagai Cuti'));
                }
                if (sudah.length) {
                    f.appendChild(tombol('pf-btn-outline pf-btn-block', 'aksi', 'buka',
                        '<i class="fa-solid fa-door-open"></i> Buka Kembali ' + sudah.length + ' Hari Cuti'));
                }
                if (!akan && !sudah.length) f.appendChild(el('p', 'pf-muted pf-day-empty', 'Tidak ada tanggal yang bisa diubah di rentang ini.'));
                aksi.appendChild(f);

                var reset = el('button', 'pf-link-btn', 'Pilih rentang lain');
                reset.type = 'button';
                reset.addEventListener('click', function () { rMulai = rSelesai = rHover = null; renderKalender(); renderPanel(); });
                aksi.appendChild(reset);
                return;
            }

            /* ---------- MODE SATU TANGGAL ---------- */
            label.textContent = 'KETERSEDIAAN';
            $('#pfDayTitle').textContent = tglPanjang(pilih);
            var lewat = pilih < D.hariIni, cuti = adaCuti(pilih), daftar = D.booking[pilih] || [], aktif = bookingAktif(pilih);

            status.appendChild(cuti ? el('span', 'pf-day-badge is-cuti', 'Cuti' + (D.libur[pilih] ? ' · ' + D.libur[pilih] : ''))
                : lewat ? el('span', 'pf-day-badge is-past', 'Tanggal sudah lewat')
                : el('span', 'pf-day-badge is-open', 'Terbuka untuk pengajuan klien'));

            if (!daftar.length) {
                list.appendChild(el('p', 'pf-muted pf-day-empty', 'Belum ada pesanan di tanggal ini.'));
            } else {
                daftar.forEach(function (b) {
                    var row = el('div', 'pf-day-row');
                    row.appendChild(el('span', 'pf-day-time', b.jam + ' WIB'));
                    var info = el('div', 'pf-day-info');
                    info.appendChild(el('b', '', b.klien));
                    info.appendChild(el('small', '', b.layanan + ' · ' + b.inv));
                    row.appendChild(info);
                    row.appendChild(el('span', 'pf-status pf-status-' + b.status, STATUS[b.status] || b.status));
                    list.appendChild(row);
                });
            }
            if (lewat) return;

            if (cuti) {
                var f1 = form({ tanggal: pilih });
                f1.appendChild(tombol('pf-btn-outline pf-btn-block', 'hapus_libur', '', '<i class="fa-solid fa-door-open"></i> Buka Kembali Tanggal Ini'));
                aksi.appendChild(f1);
            } else if (aktif > 0) {
                var w2 = el('p', 'pf-day-warn');
                w2.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> ';
                w2.appendChild(document.createTextNode('Ada ' + aktif + ' pesanan aktif di tanggal ini, jadi tidak bisa ditandai cuti. Tolak atau batalkan dulu di halaman Booking.'));
                aksi.appendChild(w2);
            } else {
                var f2 = form({ tanggal: pilih });
                var k2 = el('input', 'pf-input');
                k2.type = 'text'; k2.name = 'keterangan'; k2.maxLength = 150;
                k2.placeholder = 'Keterangan (opsional), contoh: Acara keluarga';
                f2.appendChild(k2);
                f2.appendChild(tombol('pf-btn-danger pf-btn-block', 'tambah_libur', '', '<i class="fa-solid fa-umbrella-beach"></i> Tandai sebagai Cuti'));
                aksi.appendChild(f2);
            }
        }

        // --- Tombol mode ---
        $$('.pf-mode-btn').forEach(function (b) {
            b.addEventListener('click', function () {
                if (b.dataset.mode === mode) return;
                setMode(b.dataset.mode);
                rMulai = rSelesai = rHover = null;
                renderKalender(); renderPanel();
            });
        });

        // --- Navigasi bulan ---
        $('[data-cal="prev"]').addEventListener('click', function () { lihat.m--; if (lihat.m < 0) { lihat.m = 11; lihat.y--; } renderKalender(); });
        $('[data-cal="next"]').addEventListener('click', function () { lihat.m++; if (lihat.m > 11) { lihat.m = 0; lihat.y++; } renderKalender(); });
        $('#pfCalDates').addEventListener('mouseleave', function () { if (rHover) { rHover = null; kelasRentang(); } });

        // --- Pintasan: cuti sebulan penuh (bulan yang sedang tampil) ---
        $('[data-shortcut="bulan"]').addEventListener('click', function () {
            var awal = ymd(lihat.y, lihat.m, 1), akhir = ymd(lihat.y, lihat.m, new Date(lihat.y, lihat.m + 1, 0).getDate());
            if (akhir < D.hariIni) { GL.toast('Bulan ini sudah lewat. Pindah ke bulan berikutnya dulu.', 'error'); return; }
            setMode('rentang');
            rMulai = awal < D.hariIni ? D.hariIni : awal;
            rSelesai = akhir;
            rHover = null;
            renderKalender(); renderPanel();
            if (window.innerWidth < 1000) $('#pfDayPanel').scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        // --- Pintasan: libur rutin mingguan (modal) ---
        var modalRutin = $('#pfModalRutin');
        if (modalRutin) {
            $('[data-shortcut="rutin"]').addEventListener('click', function () { hitungRutin(); GL.openModal(modalRutin); });
            modalRutin.addEventListener('click', function (e) {
                if (e.target === modalRutin || e.target.closest('[data-close-modal]')) GL.closeModal(modalRutin);
            });
            function hitungRutin() {
                var hari = $$('input[name="hari[]"]:checked', modalRutin).map(function (c) { return +c.value; });
                var bulan = +$('#pfRutinDurasi').value;
                var out = $('#pfRutinPreview'), btn = $('#pfRutinSubmit');
                if (!hari.length) { out.textContent = 'Pilih minimal satu hari.'; btn.disabled = true; return; }
                var sampai = keDate(D.hariIni); sampai.setMonth(sampai.getMonth() + bulan);
                var sampaiStr = dariDate(sampai) > D.max ? D.max : dariDate(sampai);
                var tgl = daftarTanggal(D.hariIni, sampaiStr).filter(function (t) { return hari.indexOf(keDate(t).getDay()) > -1; });
                var sudahCuti = tgl.filter(adaCuti).length;
                var lewati = tgl.filter(function (t) { return !adaCuti(t) && bookingAktif(t) > 0; }).length;
                var baru = tgl.length - sudahCuti - lewati;
                out.textContent = 'Akan menandai ' + baru + ' tanggal cuti baru hingga ' + tglPendek(sampaiStr) + '.' +
                    (sudahCuti ? ' ' + sudahCuti + ' tanggal sudah cuti.' : '') +
                    (lewati ? ' ' + lewati + ' tanggal dilewati karena ada pesanan aktif.' : '');
                btn.disabled = false;
            }
            modalRutin.addEventListener('change', hitungRutin);
        }

        renderKalender();
        renderPanel();
    }

    /* =========================================================
       4. INPUT CHIP (spesialisasi, area jangkauan, hashtag)
       <div class="pf-chip-input" data-target="idHidden" data-max="8" data-maxlen="30" data-list="idDatalist">
       Nilai disimpan di <input type="hidden" id="idHidden"> dipisah koma.
       ========================================================= */
    $$('.pf-chip-input').forEach(function (wadah) {
        var hidden = document.getElementById(wadah.dataset.target);
        var maks = +(wadah.dataset.max || 10);
        var maksLen = +(wadah.dataset.maxlen || 30);
        var hanyaDaftar = wadah.hasAttribute('data-strict');      // hanya boleh dari datalist
        var opsi = wadah.dataset.list ? $$('#' + wadah.dataset.list + ' option').map(function (o) { return o.value; }) : [];
        var nilai = hidden.value ? hidden.value.split(',').map(function (s) { return s.trim(); }).filter(Boolean) : [];

        var tombolTambah = el('button', 'pf-chip pf-chip-add');
        tombolTambah.type = 'button';
        tombolTambah.innerHTML = '<i class="fa-solid fa-plus"></i>';
        tombolTambah.setAttribute('aria-label', 'Tambah');

        var input = el('input', 'pf-chip-field');
        input.type = 'text';
        input.maxLength = maksLen;
        input.placeholder = wadah.dataset.placeholder || 'Ketik lalu Enter';
        if (wadah.dataset.list) input.setAttribute('list', wadah.dataset.list);

        function simpan() { hidden.value = nilai.join(','); hidden.dispatchEvent(new Event('change')); }

        function render() {
            $$('.pf-chip-item', wadah).forEach(function (c) { c.remove(); });
            nilai.forEach(function (v, i) {
                var chip = el('span', 'pf-chip pf-chip-item');
                chip.appendChild(document.createTextNode(v));
                var x = el('button', 'pf-chip-x');
                x.type = 'button';
                x.innerHTML = '&times;';
                x.setAttribute('aria-label', 'Hapus ' + v);
                x.addEventListener('click', function () { nilai.splice(i, 1); simpan(); render(); });
                chip.appendChild(x);
                wadah.insertBefore(chip, input.parentNode === wadah ? input : tombolTambah);
            });
            tombolTambah.style.display = (nilai.length >= maks || input.parentNode === wadah) ? 'none' : '';
        }

        function tambah(teks) {
            teks.split(',').forEach(function (t) {
                t = t.replace(/^#+/, '').trim().slice(0, maksLen);
                if (!t) return;
                if (hanyaDaftar && opsi.indexOf(t) === -1) { GL.toast('Pilih dari daftar yang tersedia.', 'error'); return; }
                var sudah = nilai.some(function (v) { return v.toLowerCase() === t.toLowerCase(); });
                if (sudah) return;
                if (nilai.length >= maks) { GL.toast('Maksimal ' + maks + ' item.', 'error'); return; }
                nilai.push(t);
            });
            simpan();
            render();
        }

        function tutupInput() {
            if (input.value.trim()) tambah(input.value);
            input.value = '';
            if (input.parentNode) input.remove();
            tombolTambah.style.display = nilai.length >= maks ? 'none' : '';
        }

        tombolTambah.addEventListener('click', function () {
            tombolTambah.style.display = 'none';
            wadah.insertBefore(input, tombolTambah);
            input.focus();
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                tambah(input.value);
                input.value = '';
            } else if (e.key === 'Escape') {
                input.value = '';
                tutupInput();
            } else if (e.key === 'Backspace' && input.value === '' && nilai.length) {
                nilai.pop(); simpan(); render();
            }
        });
        // Memilih saran dari datalist langsung ditambahkan
        input.addEventListener('input', function () {
            if (opsi.indexOf(input.value) > -1) { tambah(input.value); input.value = ''; }
        });
        input.addEventListener('blur', function () { setTimeout(tutupInput, 120); });

        wadah.appendChild(tombolTambah);
        render();
    });

    /* =========================================================
       5. EDIT PROFIL: preview foto sampul & foto profil
       ========================================================= */
    $$('input[type="file"][data-preview-target]').forEach(function (inp) {
        inp.addEventListener('change', function () {
            var f = inp.files && inp.files[0];
            if (!f) return;
            if (!/^image\/(jpeg|png|webp)$/.test(f.type)) { GL.toast('Format harus JPG, PNG, atau WEBP.', 'error'); inp.value = ''; return; }
            if (f.size > 10 * 1024 * 1024) { GL.toast('Ukuran foto lebih dari 10 MB.', 'error'); inp.value = ''; return; }
            var target = document.querySelector(inp.dataset.previewTarget);
            var url = URL.createObjectURL(f);
            if (target.tagName === 'IMG') { target.src = url; target.hidden = false; }
            else { target.style.backgroundImage = 'url("' + url + '")'; target.classList.remove('pf-cover--default'); }
            var ph = target.parentNode.querySelector('.pf-avatar-ph');
            if (ph && target.tagName === 'IMG') ph.hidden = true;
            if (window.GL) GL.fadeIn(target);
        });
    });

    // Hitung karakter textarea/input [data-counter="#idTeks"]
    $$('[data-counter]').forEach(function (inp) {
        var out = document.querySelector(inp.dataset.counter);
        var upd = function () { out.textContent = inp.value.length + '/' + inp.maxLength; };
        inp.addEventListener('input', upd);
        upd();
    });

    /* =========================================================
       6. BUAT POSTINGAN PORTFOLIO: pilih foto + pratinjau langsung
       ========================================================= */
    var formPorto = $('#pfFormPorto');
    if (formPorto) {
        var prevImg = $('#pfPrevImg'), prevKosong = $('#pfPrevKosong');
        var prevJudul = $('#pfPrevJudul'), prevTags = $('#pfPrevTags'), prevBadge = $('#pfPrevBadge');
        var inJudul = $('#pfJudul'), inTag = $('#pfTagHidden'), inFile = $('#pfFotoFile'), inKoleksi = $('#pfKoleksi');
        var namaFile = $('#pfFotoNama');
        var modeEdit = formPorto.hasAttribute('data-edit');

        function tampilFoto(url) {
            if (url) {
                prevImg.src = url; prevImg.hidden = false; prevKosong.hidden = true;
                GL.fadeIn(prevImg);
            } else {
                prevImg.hidden = true; prevKosong.hidden = false;
            }
        }

        // Pilih dari koleksi foto dokumentasi
        $$('.pf-koleksi-item').forEach(function (k) {
            k.addEventListener('click', function () {
                var dipilih = k.classList.contains('selected');
                $$('.pf-koleksi-item').forEach(function (x) { x.classList.remove('selected'); x.setAttribute('aria-pressed', 'false'); });
                if (dipilih) {           // klik lagi = batal pilih (mode edit: kembali ke foto lama)
                    inKoleksi.value = '';
                    namaFile.textContent = modeEdit ? 'Memakai foto saat ini' : 'Belum ada foto dipilih';
                    tampilFoto(prevImg.dataset.awal || null);
                    return;
                }
                k.classList.add('selected');
                k.setAttribute('aria-pressed', 'true');
                inKoleksi.value = k.dataset.file;
                inFile.value = '';
                namaFile.textContent = 'Dari dokumentasi: ' + k.dataset.label;
                tampilFoto(k.dataset.url);
            });
        });

        // Upload foto baru
        inFile.addEventListener('change', function () {
            var f = inFile.files && inFile.files[0];
            if (!f) return;
            if (!/^image\/(jpeg|png|webp)$/.test(f.type)) { GL.toast('Format harus JPG, PNG, atau WEBP.', 'error'); inFile.value = ''; return; }
            if (f.size > 10 * 1024 * 1024) { GL.toast('Ukuran foto lebih dari 10 MB.', 'error'); inFile.value = ''; return; }
            $$('.pf-koleksi-item').forEach(function (x) { x.classList.remove('selected'); x.setAttribute('aria-pressed', 'false'); });
            inKoleksi.value = '';
            namaFile.textContent = f.name + ' · ' + (f.size / 1024 / 1024).toFixed(1) + ' MB';
            tampilFoto(URL.createObjectURL(f));
        });

        function updateTeks() {
            prevJudul.textContent = inJudul.value.trim() || 'Judul Look Portofolio Baru';
            var tags = inTag.value ? inTag.value.split(',').filter(Boolean) : [];
            prevTags.innerHTML = '';
            tags.forEach(function (t) { prevTags.appendChild(el('span', '', '#' + t.replace(/\s+/g, ''))); });
            if (!tags.length) prevTags.appendChild(el('span', 'pf-muted', '#HashtagKamu'));
            prevBadge.textContent = tags[0] || 'Portfolio';
        }
        inJudul.addEventListener('input', updateTeks);
        inTag.addEventListener('change', updateTeks);
        updateTeks();

        // Validasi sebelum kirim
        formPorto.addEventListener('submit', function (e) {
            if (!modeEdit && !inFile.value && !inKoleksi.value) {
                e.preventDefault();
                e.stopImmediatePropagation();      // jangan ubah tombol jadi "Memproses..."
                GL.toast('Pilih foto dari koleksi atau unggah foto baru.', 'error');
                $('.pf-foto-section').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, true);
    }
})();