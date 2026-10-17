<?php
session_start();
require '../koneksi.php';

// Perbaikan syntax error logika OR (||)
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'mua') {
    header("Location: ../login.php");
    exit();
}

$id_mua =$_SESSION['id_user'];

// Proses jika tombol Simpan diklik
if (isset($_POST['submit_layanan'])) {$nama = mysqli_real_escape_string($koneksi,$_POST['nama_layanan']);
    $jenis = mysqli_real_escape_string($koneksi, $_POST['jenis']);$harga = preg_replace('/[^0-9]/', '', $_POST['harga']); // Hanya ambil angka$durasi = mysqli_real_escape_string($koneksi,$_POST['durasi']) . " Menit";
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);$fasilitas = mysqli_real_escape_string($koneksi,$_POST['fasilitas_hidden']); // Format JSON dari JS

    $query = "INSERT INTO services (id_mua, nama_layanan, kategori, harga, durasi, deskripsi, fasilitas) 
              VALUES ('$id_mua', '$nama', '$jenis', '$harga', '$durasi', '$deskripsi', '$fasilitas')";
    
    if(mysqli_query($koneksi,$query)) {
        header("Location: services.php?success=1");
        exit();
    } else {
        $error = "Gagal menyimpan layanan: " . mysqli_error($koneksi);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Layanan - GlamLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/mua_tambah_layanan.css">
</head>
<body>
    
    <!-- Memanggil Navbar Terpusat -->
    <?php include '../navbar.php'; ?>

    <div class="page-top-bg"></div>

    <main class="tambah-main">
        <div class="tambah-header">
            <a href="services.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i></a>
            <h2>Tambah Layanan</h2>
        </div>

        <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

        <!-- Form membungkus kedua sisi (Kiri & Kanan) agar tombol submit di kanan bisa berfungsi -->
        <form action="" method="POST" id="formLayanan" class="form-container">
            
            <!-- KOLOM KIRI: INPUT FORM -->
            <div class="form-section box-panel">
                <div class="form-group">
                    <label>NAMA PAKET LAYANAN <span class="text-red">*</span></label>
                    <input type="text" name="nama_layanan" id="in_nama" placeholder="Contoh: Riasan Pengantin Modern & Retouch" required>
                    <small>Gunakan nama yang jelas dan menarik perhatian calon klien.</small>
                </div>

                <div class="form-group">
                    <label>JENIS <span class="text-red">*</span></label>
                    <input type="text" name="jenis" id="in_jenis" placeholder="Contoh: Makeup saja" required>
                </div>

                <div class="form-row">
                    <div class="form-group half">
                        <label>TARIF LAYANAN (RP) <span class="text-red">*</span></label>
                        <input type="number" name="harga" id="in_harga" placeholder="650000" required>
                        <div class="quick-chips mt-2">
                            <span class="chip-btn" onclick="setHarga(350000)">350rb</span>
                            <span class="chip-btn" onclick="setHarga(550000)">550rb</span>
                            <span class="chip-btn" onclick="setHarga(750000)">750rb</span>
                            <span class="chip-btn" onclick="setHarga(1200000)">1,2jt</span>
                            <span class="chip-btn" onclick="setHarga(2500000)">2,5jt</span>
                        </div>
                    </div>
                    <div class="form-group half">
                        <label>ESTIMASI DURASI (MENIT) <span class="text-red">*</span></label>
                        <input type="number" name="durasi" id="in_durasi" placeholder="90" required>
                        <small>Waktu pengerjaan di venue kedatangan.</small>
                    </div>
                </div>

                <div class="form-group">
                    <label>DESKRIPSI LENGKAP PAKET</label>
                    <textarea name="deskripsi" id="in_deskripsi" rows="4" placeholder="Jelaskan teknik yang Anda gunakan, jenis complexion, ketahanan riasan..."></textarea>
                </div>

                <div class="form-group">
                    <label>FASILITAS & LAYANAN YANG TERMASUK (INCLUDED ITEMS)</label>
                    <div class="fasilitas-input-wrap">
                        <input type="text" id="in_fasilitas" placeholder="Ketik fasilitas baru (contoh: Pouch touchup gratis)...">
                        <button type="button" class="btn-add-fasilitas" onclick="addFasilitas()">+ Tambah</button>
                    </div>
                    <!-- Tempat munculnya tag fasilitas -->
                    <div id="fasilitas_tags" class="tags-container"></div>
                    
                    <small style="display:block; margin-top:15px; margin-bottom:10px;">Pilihan Cepat Tambahan:</small>
                    <div class="quick-chips">
                        <span class="chip-btn outline" onclick="addFasilitasText('Pouch mini retouch gratis')">+ Pouch mini retouch gratis</span>
                        <span class="chip-btn outline" onclick="addFasilitasText('Tata rambut blow / soft waves')">+ Tata rambut blow / soft waves</span>
                        <span class="chip-btn outline" onclick="addFasilitasText('Hijab styling rapi & aksesoris')">+ Hijab styling rapi & aksesoris</span>
                        <span class="chip-btn outline" onclick="addFasilitasText('Glow area pundak & leher')">+ Glow area pundak & leher</span>
                        <span class="chip-btn outline" onclick="addFasilitasText('Pemasangan softlens ramah mata')">+ Pemasangan softlens ramah mata</span>
                    </div>

                    <!-- Input Tersembunyi untuk menyimpan data fasilitas sbg JSON untuk dikirim ke PHP -->
                    <input type="hidden" name="fasilitas_hidden" id="fasilitas_hidden_input">
                </div>
            </div>

            <!-- KOLOM KANAN: LIVE PREVIEW -->
            <div class="preview-section">
                <p class="preview-title"><i class="fa-regular fa-eye"></i> Pratinjau Tampilan untuk Klien (Live Preview)</p>
                
                <div class="box-panel preview-card">
                    <div class="preview-head">
                        <span class="badge-jenis" id="prev_jenis">JENIS LAYANAN</span>
                        <span class="badge-tersedia">Tersedia</span>
                    </div>
                    
                    <h3 id="prev_nama">Nama Paket Layanan Baru</h3>
                    <p class="prev-desc" id="prev_deskripsi">Deskripsi paket akan muncul di sini saat Anda mulai mengetik.</p>
                    
                    <div class="preview-pricing">
                        <div>
                            <span class="lbl">TARIF LAYANAN</span>
                            <div class="val-harga">Rp <span id="prev_harga">0</span></div>
                        </div>
                        <div class="text-right">
                            <span class="lbl">DURASI</span>
                            <div class="val-durasi"><i class="fa-regular fa-clock"></i> <span id="prev_durasi">0</span> Menit</div>
                        </div>
                    </div>

                    <div class="preview-facilities">
                        <p class="lbl-bold">Fasilitas yang Termasuk:</p>
                        <ul id="prev_fasilitas_list">
                            <li class="empty-list">Belum ada fasilitas ditambahkan</li>
                        </ul>
                    </div>

                    <button type="submit" name="submit_layanan" class="btn-submit-layanan"><i class="fa-regular fa-floppy-disk"></i> Simpan & Publikasikan Layanan</button>
                    
                    <div class="guarantee-badge">
                        <i class="fa-solid fa-shield-halved"></i> Garansi Pelayanan Resmi GlamLink
                    </div>
                </div>
            </div>

        </form>
    </main>

    <!-- SCRIPT UNTUK LIVE PREVIEW & FASILITAS ARRAY -->
    <script>
        // 1. Data Binding Live Preview Text
        const bindInput = (inputId, previewId, defaultText, isCurrency = false) => {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            
            input.addEventListener('input', function() {
                let val = this.value;
                if(isCurrency) {
                    val = parseInt(val.replace(/[^0-9]/g, '')) || 0;
                    preview.innerText = new Intl.NumberFormat('id-ID').format(val);
                } else {
                    preview.innerText = val.trim() === '' ? defaultText : val;
                }
            });
        };

        bindInput('in_nama', 'prev_nama', 'Nama Paket Layanan Baru');
        bindInput('in_jenis', 'prev_jenis', 'JENIS LAYANAN');
        bindInput('in_deskripsi', 'prev_deskripsi', 'Deskripsi paket akan muncul di sini saat Anda mulai mengetik.');
        bindInput('in_durasi', 'prev_durasi', '0');
        bindInput('in_harga', 'prev_harga', '0', true);

        // Fungsi Tombol Cepat Harga
        function setHarga(nominal) {
            document.getElementById('in_harga').value = nominal;
            document.getElementById('prev_harga').innerText = new Intl.NumberFormat('id-ID').format(nominal);
        }

        // 2. Logic Tambah Fasilitas (Array)
        let fasilitasArray = [];

        function addFasilitas() {
            const input = document.getElementById('in_fasilitas');
            addFasilitasText(input.value);
            input.value = '';
        }

        function addFasilitasText(text) {
            if(text.trim() !== '' && !fasilitasArray.includes(text.trim())) {
                fasilitasArray.push(text.trim());
                renderFasilitas();
            }
        }

        function removeFasilitas(index) {
            fasilitasArray.splice(index, 1);
            renderFasilitas();
        }

        function renderFasilitas() {
            const tagsContainer = document.getElementById('fasilitas_tags');
            const prevList = document.getElementById('prev_fasilitas_list');
            const hiddenInput = document.getElementById('fasilitas_hidden_input');
            
            tagsContainer.innerHTML = '';
            prevList.innerHTML = '';

            if(fasilitasArray.length === 0) {
                prevList.innerHTML = '<li class="empty-list">Belum ada fasilitas ditambahkan</li>';
            } else {
                fasilitasArray.forEach((item, index) => {
                    // Render untuk Kolom Kiri (Tags)
                    tagsContainer.innerHTML += `
                        <div class="tag-item">
                            <i class="fa-regular fa-circle-check text-green"></i> ${item} 
                            <i class="fa-solid fa-xmark remove-tag" onclick="removeFasilitas(${index})"></i>
                        </div>
                    `;
                    // Render untuk Kolom Kanan (Live Preview List)
                    prevList.innerHTML += `<li><i class="fa-regular fa-circle-check text-green"></i> ${item}</li>`;
                });
            }
            // Simpan ke hidden input sebagai JSON string agar dikirim via POST PHP
            hiddenInput.value = JSON.stringify(fasilitasArray);
        }
        // Mengubah status menu navbar yang aktif menjadi 'Services'
        document.addEventListener("DOMContentLoaded", function() {
            const navLinks = document.querySelectorAll('.nav-menu-mua a');
            navLinks.forEach(link => link.classList.remove('active'));
            if(navLinks[1]) navLinks[1].classList.add('active'); // Index 1 adalah menu Services
        });
    </script>
</body>
</html>