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
       2. HAPUS PORTFOLIO (modal konfirmasi)
       ========================================================= */
    var modalHapus = $('#pfModalHapus');
    if (modalHapus) {
        $$('.pf-porto-del').forEach(function (b) {
            b.addEventListener('click', function () {
                $('#pfHapusId').value = b.dataset.id;
                $('#pfHapusJudul').textContent = b.dataset.judul;
                GL.openModal(modalHapus);
            });
        });
        modalHapus.addEventListener('click', function (e) {
            if (e.target === modalHapus || e.target.closest('[data-close-modal]')) GL.closeModal(modalHapus);
        });
    }

    /* =========================================================
       3. KALENDER JADWAL (tandai / buka tanggal cuti)
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

        var pilih = D.pilih;
        var lihat = parse(pilih);              // bulan yang sedang ditampilkan
        var batasMin = parse(D.min), batasMax = parse(D.max);
        var idx = function (y, m) { return y * 12 + m; };

        function bookingAktif(tgl) {
            return (D.booking[tgl] || []).filter(function (b) { return b.status !== 'selesai'; }).length;
        }

        function renderKalender() {
            $('#pfCalTitle').textContent = BULAN[lihat.m] + ' ' + lihat.y;
            $('[data-cal="prev"]').disabled = idx(lihat.y, lihat.m) <= idx(batasMin.y, batasMin.m);
            $('[data-cal="next"]').disabled = idx(lihat.y, lihat.m) >= idx(batasMax.y, batasMax.m);

            var wadah = $('#pfCalDates');
            wadah.innerHTML = '';
            var hariPertama = new Date(lihat.y, lihat.m, 1).getDay();      // 0 = Minggu
            var kosong = (hariPertama + 6) % 7;                              // kalender mulai Senin
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
                if (tgl === pilih) btn.classList.add('is-selected');
                if (Object.prototype.hasOwnProperty.call(D.libur, tgl)) btn.classList.add('is-cuti');
                if (D.booking[tgl]) {
                    btn.classList.add('has-booking');
                    btn.appendChild(el('i', 'pf-cal-dot'));
                    btn.title = D.booking[tgl].length + ' pesanan';
                }
                btn.addEventListener('click', function () {
                    pilih = this.dataset.tgl;
                    renderKalender();
                    renderPanel();
                    var url = new URL(window.location.href);
                    url.searchParams.set('tgl', pilih);
                    history.replaceState(null, '', url);
                });
                wadah.appendChild(btn);
            }
        }

        function renderPanel() {
            var p = parse(pilih);
            var dObj = new Date(p.y, p.m, p.d);
            $('#pfDayTitle').textContent = HARI[dObj.getDay()] + ', ' + p.d + ' ' + BULAN[p.m] + ' ' + p.y;

            var lewat = pilih < D.hariIni;
            var cuti = Object.prototype.hasOwnProperty.call(D.libur, pilih);
            var daftar = D.booking[pilih] || [];
            var aktif = bookingAktif(pilih);

            // --- status hari ---
            var status = $('#pfDayStatus');
            status.innerHTML = '';
            var badge;
            if (cuti) {
                badge = el('span', 'pf-day-badge is-cuti', 'Cuti' + (D.libur[pilih] ? ' · ' + D.libur[pilih] : ''));
            } else if (lewat) {
                badge = el('span', 'pf-day-badge is-past', 'Tanggal sudah lewat');
            } else {
                badge = el('span', 'pf-day-badge is-open', 'Terbuka untuk pengajuan klien');
            }
            status.appendChild(badge);

            // --- daftar pesanan di tanggal ini ---
            var list = $('#pfDayList');
            list.innerHTML = '';
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

            // --- tombol aksi ---
            var aksi = $('#pfDayAction');
            aksi.innerHTML = '';
            if (lewat) return;

            var form = el('form');
            form.method = 'POST';
            form.action = 'profil.php?tab=jadwal';
            var h = el('input'); h.type = 'hidden'; h.name = 'tanggal'; h.value = pilih;
            form.appendChild(h);

            if (cuti) {
                var b1 = el('button', 'pf-btn-outline pf-btn-block');
                b1.type = 'submit'; b1.name = 'hapus_libur';
                b1.innerHTML = '<i class="fa-solid fa-door-open"></i> Buka Kembali Tanggal Ini';
                form.appendChild(b1);
            } else if (aktif > 0) {
                form = el('p', 'pf-day-warn');
                form.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> ';
                form.appendChild(document.createTextNode('Ada ' + aktif + ' pesanan aktif di tanggal ini, jadi tidak bisa ditandai cuti. Tolak atau batalkan dulu di halaman Booking.'));
            } else {
                var ket = el('input', 'pf-input');
                ket.type = 'text'; ket.name = 'keterangan'; ket.maxLength = 150;
                ket.placeholder = 'Keterangan (opsional), contoh: Acara keluarga';
                form.appendChild(ket);
                var b2 = el('button', 'pf-btn-danger pf-btn-block');
                b2.type = 'submit'; b2.name = 'tambah_libur';
                b2.innerHTML = '<i class="fa-solid fa-umbrella-beach"></i> Tandai sebagai Cuti';
                form.appendChild(b2);
            }
            aksi.appendChild(form);
        }

        $('[data-cal="prev"]').addEventListener('click', function () {
            lihat.m--; if (lihat.m < 0) { lihat.m = 11; lihat.y--; } renderKalender();
        });
        $('[data-cal="next"]').addEventListener('click', function () {
            lihat.m++; if (lihat.m > 11) { lihat.m = 0; lihat.y++; } renderKalender();
        });

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
                if (dipilih) {           // klik lagi = batal pilih
                    inKoleksi.value = '';
                    namaFile.textContent = 'Belum ada foto dipilih';
                    tampilFoto(null);
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
            if (!inFile.value && !inKoleksi.value) {
                e.preventDefault();
                e.stopImmediatePropagation();      // jangan ubah tombol jadi "Memproses..."
                GL.toast('Pilih foto dari koleksi atau unggah foto baru.', 'error');
                $('.pf-foto-section').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, true);
    }
})();