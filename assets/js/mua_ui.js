/**
 * GLAMLINK - UI BERSAMA HALAMAN MUA
 * Dipanggil di akhir <body>:  <script src="../assets/js/mua_ui.js"></script>
 *
 * Fungsi yang bisa dipakai di halaman:
 *   GL.toast('Pesan', 'success' | 'error' | 'info')
 *   GL.openModal(elemen)   /  GL.closeModal(elemen)
 *   GL.fadeIn(elemen)
 *
 * Fitur otomatis (tanpa kode tambahan di halaman):
 *   - Tombol submit berubah jadi "Memproses..." dan form tidak bisa terkirim dua kali
 *   - Toast dari PHP (window.GL_TOASTS, diisi oleh includes/mua_ui.php)
 *   - Preview foto untuk <input type="file" data-preview>
 *   - Angka menghitung naik untuk elemen [data-countup]
 *   - Garis bawah tab bergeser untuk .booking-tabs
 */
(function () {
    'use strict';

    var kurangiAnimasi = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* =========================================================
       3. TOAST
       ========================================================= */
    var wadahToast = null;
    var IKON = {
        success: 'fa-solid fa-circle-check',
        error: 'fa-solid fa-circle-exclamation',
        info: 'fa-solid fa-circle-info'
    };

    function toast(pesan, tipe, durasi) {
        tipe = IKON[tipe] ? tipe : 'info';
        durasi = durasi || 3000;

        if (!wadahToast) {
            wadahToast = document.createElement('div');
            wadahToast.className = 'gl-toast-wrap';
            wadahToast.setAttribute('aria-live', 'polite');
            document.body.appendChild(wadahToast);
        }

        var el = document.createElement('div');
        el.className = 'gl-toast ' + tipe;
        el.setAttribute('role', tipe === 'error' ? 'alert' : 'status');
        el.style.setProperty('--gl-toast-durasi', durasi + 'ms');
        el.innerHTML = '<i class="gl-toast-icon ' + IKON[tipe] + '"></i>' +
                       '<div class="gl-toast-msg"></div>' +
                       '<button type="button" class="gl-toast-close" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>';
        el.querySelector('.gl-toast-msg').textContent = pesan;   // textContent = aman dari HTML injection
        wadahToast.appendChild(el);

        requestAnimationFrame(function () {
            requestAnimationFrame(function () { el.classList.add('gl-toast-show'); });
        });

        var timer = setTimeout(tutup, durasi);
        el.querySelector('.gl-toast-close').addEventListener('click', tutup);
        // Arahkan kursor ke toast = jangan hilang dulu
        el.addEventListener('mouseenter', function () { clearTimeout(timer); el.style.setProperty('--gl-toast-durasi', '0s'); });
        el.addEventListener('mouseleave', function () { timer = setTimeout(tutup, 1500); });

        function tutup() {
            clearTimeout(timer);
            el.classList.remove('gl-toast-show');
            setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 260);
        }
    }

    /* =========================================================
       2. MODAL BUKA-TUTUP
       ========================================================= */
    function openModal(modal) {
        if (typeof modal === 'string') modal = document.getElementById(modal);
        if (!modal) return;
        modal.style.display = 'flex';
        void modal.offsetWidth;                 // paksa browser menggambar dulu, baru animasi jalan
        modal.classList.add('gl-modal-open');
        document.body.style.overflow = 'hidden'; // halaman belakang tidak ikut ter-scroll
    }

    function closeModal(modal) {
        if (typeof modal === 'string') modal = document.getElementById(modal);
        if (!modal || !modal.classList.contains('gl-modal-open')) return;
        modal.classList.remove('gl-modal-open');
        document.body.style.overflow = '';
        setTimeout(function () {
            if (!modal.classList.contains('gl-modal-open')) modal.style.display = 'none';
        }, kurangiAnimasi ? 0 : 220);
    }

    // Tekan Esc untuk menutup modal yang sedang terbuka
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('.modal-overlay.gl-modal-open').forEach(closeModal);
    });

    /* =========================================================
       1. TOMBOL "SEDANG MEMPROSES" + CEGAH KIRIM DUA KALI
       Catatan: tombol TIDAK diberi atribut disabled, karena tombol disabled
       tidak ikut terkirim ke PHP (isset($_POST['terima_pesanan']) jadi gagal).
       ========================================================= */
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-no-loading')) return;

        if (form.dataset.glSubmitting === '1') {   // klik kedua dan seterusnya diabaikan
            e.preventDefault();
            return;
        }
        form.dataset.glSubmitting = '1';

        // Tombol yang diklik; jika form dikirim lewat JS, pakai tombol bertanda data-loading-button
        var tombol = e.submitter || form.querySelector('[data-loading-button], [type="submit"]');
        form.querySelectorAll('button, input[type="submit"]').forEach(function (b) {
            if (b !== tombol) b.classList.add('gl-btn-muted');
        });
        if (tombol) {
            tombol.dataset.glHtmlAsli = tombol.innerHTML;
            tombol.classList.add('gl-btn-loading');
            tombol.setAttribute('aria-busy', 'true');
            var teks = tombol.getAttribute('data-loading-text') || 'Memproses...';
            if (tombol.tagName === 'BUTTON') {
                tombol.innerHTML = '<span class="gl-spinner"></span>' + teks;
            } else {
                tombol.value = teks;
            }
        }
    }, false);

    // Jika pengguna menekan tombol Back, kembalikan tombol ke keadaan semula
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        document.querySelectorAll('form[data-gl-submitting="1"]').forEach(function (f) { delete f.dataset.glSubmitting; });
        document.querySelectorAll('.gl-btn-loading, .gl-btn-muted').forEach(function (b) {
            if (b.dataset.glHtmlAsli !== undefined) b.innerHTML = b.dataset.glHtmlAsli;
            b.classList.remove('gl-btn-loading', 'gl-btn-muted');
            b.removeAttribute('aria-busy');
        });
    });

    /* =========================================================
       4. PREVIEW FOTO SEBELUM UPLOAD
       <input type="file" data-preview>                -> thumbnail muncul di bawah input
       <input type="file" data-preview-bg=".selector">  -> ganti background elemen itu
       ========================================================= */
    document.addEventListener('change', function (e) {
        var input = e.target;
        if (!(input instanceof HTMLInputElement) || input.type !== 'file') return;
        if (!input.hasAttribute('data-preview') && !input.hasAttribute('data-preview-bg')) return;

        var file = input.files && input.files[0];
        var wadah = input.parentNode;
        var lama = wadah.querySelectorAll('.gl-upload-preview, .gl-upload-preview-info');
        lama.forEach(function (n) { n.parentNode.removeChild(n); });
        if (!file) return;

        if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
            toast('Format harus JPG, PNG, atau WEBP.', 'error');
            input.value = '';
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            toast('Ukuran foto lebih dari 10 MB.', 'error');
            input.value = '';
            return;
        }

        var url = URL.createObjectURL(file);

        if (input.hasAttribute('data-preview-bg')) {
            var target = document.querySelector(input.getAttribute('data-preview-bg'));
            if (target) {
                target.style.backgroundImage = 'url("' + url + '")';
                target.classList.remove('gl-bg-swap'); void target.offsetWidth; target.classList.add('gl-bg-swap');
            }
        } else {
            var img = document.createElement('img');
            img.className = 'gl-upload-preview';
            img.alt = 'Preview foto dokumentasi';
            img.src = url;
            var info = document.createElement('small');
            info.className = 'gl-upload-preview-info';
            info.textContent = file.name + ' · ' + (file.size / 1024 / 1024).toFixed(1) + ' MB';
            input.insertAdjacentElement('afterend', info);
            input.insertAdjacentElement('afterend', img);
        }

        // <input data-auto-submit>: langsung kirim form setelah foto dipilih (requestSubmit memicu status "Memproses...")
        if (input.hasAttribute('data-auto-submit') && input.form) {
            if (input.form.requestSubmit) input.form.requestSubmit(); else input.form.submit();
        }

    });

    /* =========================================================
       5. ANGKA MENGHITUNG NAIK
       <span data-countup="9000000" data-prefix="Rp " data-decimals="0">Rp 9.000.000</span>
       ========================================================= */
    function formatAngka(n, desimal) {
        // Bilangan bulat: pemisah ribuan titik (9.000.000), desimal: pakai titik seperti tampilan PHP (4.5)
        return desimal > 0 ? n.toFixed(desimal) : Math.round(n).toLocaleString('id-ID');
    }
    function countUp(el) {
        var target = parseFloat(el.getAttribute('data-countup'));
        if (isNaN(target)) return;
        var desimal = parseInt(el.getAttribute('data-decimals') || '0', 10);
        var prefix = el.getAttribute('data-prefix') || '';
        var suffix = el.getAttribute('data-suffix') || '';
        var tulis = function (n) { el.textContent = prefix + formatAngka(n, desimal) + suffix; };

        if (kurangiAnimasi || target === 0) { tulis(target); return; }

        var durasi = 800, mulai = null;
        function langkah(t) {
            if (!mulai) mulai = t;
            var p = Math.min((t - mulai) / durasi, 1);
            var ease = 1 - Math.pow(1 - p, 3);           // melambat di akhir
            tulis(p < 1 ? target * ease : target);
            if (p < 1) requestAnimationFrame(langkah);
        }
        tulis(0);
        requestAnimationFrame(langkah);
    }

    /* =========================================================
       7. GARIS BAWAH TAB BERGESER (.booking-tabs)
       Tab di GlamLink adalah link yang memuat ulang halaman, jadi:
       klik -> garis bergeser ke tab tujuan -> baru pindah halaman.
       ========================================================= */
    function initTabSlider(wadah) {
        var aktif = wadah.querySelector('.tab-link.active');
        if (!aktif) return;
        var garis = document.createElement('span');
        garis.className = 'gl-tab-indicator';
        wadah.appendChild(garis);
        wadah.classList.add('gl-has-slider');

        function geserKe(tab, animasi) {
            if (!animasi) garis.style.transition = 'none';
            garis.style.width = tab.offsetWidth + 'px';
            garis.style.transform = 'translateX(' + tab.offsetLeft + 'px)';
            if (!animasi) { void garis.offsetWidth; garis.style.transition = ''; }
        }
        geserKe(aktif, false);
        window.addEventListener('resize', function () { geserKe(wadah.querySelector('.tab-link.active') || aktif, false); });

        wadah.querySelectorAll('.tab-link').forEach(function (tab) {
            tab.addEventListener('click', function (e) {
                // Ctrl/Cmd+klik (buka tab baru) tetap normal
                if (tab === aktif || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
                e.preventDefault();
                aktif.classList.remove('active');
                tab.classList.add('active');
                geserKe(tab, true);
                setTimeout(function () { window.location.href = tab.href; }, kurangiAnimasi ? 0 : 230);
            });
        });
    }

    /* =========================================================
       6. FADE-IN (dipakai search bar)
       ========================================================= */
    function fadeIn(el) {
        el.classList.remove('gl-fade-in');
        void el.offsetWidth;
        el.classList.add('gl-fade-in');
    }

    /* =========================================================
       JALANKAN SAAT HALAMAN SIAP
       ========================================================= */
    function mulai() {
        (window.GL_TOASTS || []).forEach(function (t, i) {
            setTimeout(function () { toast(t.msg, t.type); }, 150 + i * 200);
        });
        document.querySelectorAll('[data-countup]').forEach(countUp);
        document.querySelectorAll('.booking-tabs').forEach(initTabSlider);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mulai);
    else mulai();

    window.GL = { toast: toast, openModal: openModal, closeModal: closeModal, fadeIn: fadeIn };
})();