<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit(); }
include '../koneksi.php';

$id_user = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Token sederhana agar aksi verifikasi hanya bisa dikirim dari halaman ini
if (empty($_SESSION['admin_csrf'])) $_SESSION['admin_csrf'] = bin2hex(random_bytes(16));
$error_aksi = '';

// Proses tombol Setujui / Tolak (POST). Penolakan wajib menyertakan alasan untuk MUA.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $action_id = intval($_POST['id'] ?? 0);
    $token_ok  = hash_equals($_SESSION['admin_csrf'], $_POST['token'] ?? '');
    $alasan    = trim($_POST['alasan_tolak'] ?? '');

    if (!$token_ok) {
        $error_aksi = 'Sesi halaman sudah kedaluwarsa. Muat ulang lalu coba lagi.';
    } elseif ($_POST['aksi'] === 'approve') {
        $st = $koneksi->prepare("UPDATE users SET status_verifikasi = 'approved', alasan_tolak = NULL WHERE id_user = ? AND role = 'mua'");
        $st->bind_param('i', $action_id);
        $st->execute();
        header("Location: detail_verifikasi.php?id=" . $action_id);
        exit();
    } elseif ($_POST['aksi'] === 'reject') {
        if (mb_strlen($alasan) < 10 || mb_strlen($alasan) > 255) {
            $error_aksi = 'Tuliskan alasan penolakan (10–255 karakter) agar MUA tahu apa yang perlu diperbaiki.';
        } else {
            $st = $koneksi->prepare("UPDATE users SET status_verifikasi = 'rejected', alasan_tolak = ? WHERE id_user = ? AND role = 'mua'");
            $st->bind_param('si', $alasan, $action_id);
            $st->execute();
            header("Location: detail_verifikasi.php?id=" . $action_id);
            exit();
        }
    }
    $id_user = $action_id;
}

// Ambil data MUA berdasarkan ID
$query_mua = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = $id_user AND role = 'mua' LIMIT 1");
$mua = mysqli_fetch_assoc($query_mua);

if (!$mua) {
    header("Location: verifikasi_mua.php");
    exit();
}

// Ambil portofolio MUA
$query_porto = mysqli_query($koneksi, "SELECT * FROM portfolios WHERE id_mua = $id_user");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tinjau Mitra MUA - Admin GlamLink</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <header class="admin-navbar">
        <div class="brand-container"><h2 style="color: #4A3B32; font-weight: 700;">GlamLink</h2></div>
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="kelola_pengguna.php" class="nav-link">Kelola Pengguna</a>
            <a href="verifikasi_mua.php" class="nav-link active">Verifikasi MUA</a>
        </nav>
        <div class="user-profile">
            <span style="font-size: 13px;">Halo, <strong><?= $_SESSION['admin_nama']; ?></strong> | <a href="../logout.php" style="color: #C62828; text-decoration: none;">Logout</a></span>
        </div>
    </header>

    <main class="main-wrapper" style="max-width: 900px; margin: 30px auto; padding: 0 20px;">
        <a href="verifikasi_mua.php" style="color: #7A6F68; text-decoration: none; font-size: 13px; font-weight: 600;">← Kembali ke Daftar Verifikasi</a>

        <div style="background: white; border-radius: 16px; border: 1px solid #EFECE6; padding: 30px; margin-top: 15px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #EFECE6; padding-bottom: 20px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div style="width: 65px; height: 65px; border-radius: 50%; background: #F5F0E8; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 700; color: #4A3B32;">
                        <?= strtoupper(substr($mua['nama'], 0, 1)); ?>
                    </div>
                    <div>
                        <h1 style="font-size: 22px; margin: 0; color: #332822;"><?= htmlspecialchars($mua['nama']); ?></h1>
                        <p style="color: #7A6F68; font-size: 13px; margin: 4px 0 0;">Studio: <strong><?= htmlspecialchars($mua['nama_brand'] ?? 'Studio Mandiri'); ?></strong></p>
                    </div>
                </div>
                <div>
                    <span class="badge <?= $mua['status_verifikasi'] == 'approved' ? 'badge-aktif' : ($mua['status_verifikasi'] == 'pending' ? 'badge-pending' : 'badge-tolak'); ?>">
                        <?= ucfirst($mua['status_verifikasi']); ?>
                    </span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; font-size: 14px;">
                <div>
                    <p style="color: #7A6F68; margin-bottom: 4px;">Email</p>
                    <strong><?= htmlspecialchars($mua['email']); ?></strong>
                </div>
                <div>
                    <p style="color: #7A6F68; margin-bottom: 4px;">Nomor Telepon</p>
                    <strong><?= htmlspecialchars($mua['nomor_telepon'] ?? '-'); ?></strong>
                </div>
                <div>
                    <p style="color: #7A6F68; margin-bottom: 4px;">Lokasi / Area Operasional</p>
                    <strong><?= htmlspecialchars($mua['lokasi'] ?? '-'); ?></strong>
                </div>
                <div>
                    <p style="color: #7A6F68; margin-bottom: 4px;">Tanggal Pendaftaran</p>
                    <strong><?= date('d M Y', strtotime($mua['dibuat_pada'])); ?></strong>
                </div>
                <div style="grid-column: span 2;">
                    <p style="color: #7A6F68; margin-bottom: 4px;">Bio / Deskripsi Studio</p>
                    <p style="background: #FAFAFA; padding: 12px; border-radius: 8px; border: 1px solid #EFECE6; margin: 0; color: #4A3B32;"><?= htmlspecialchars($mua['bio'] ?? 'Belum ada bio deskripsi.'); ?></p>
                </div>
            </div>

            <h3 style="font-size: 16px; margin-bottom: 12px; color: #332822;">Galeri Portofolio MUA</h3>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px;">
                <?php if (mysqli_num_rows($query_porto) > 0) : ?>
                    <?php while($p = mysqli_fetch_assoc($query_porto)) : ?>
                    <div style="border: 1px solid #EFECE6; border-radius: 10px; overflow: hidden; background: #FAFAFA;">
                        <img src="../assets/img/<?= $p['foto']; ?>" alt="Portofolio" style="width: 100%; height: 150px; object-fit: cover;" onerror="this.src='https://via.placeholder.com/200?text=No+Image'">
                    </div>
                    <?php endwhile; ?>
                <?php else : ?>
                    <p style="color: #7A6F68; font-size: 13px; grid-column: span 3;">Belum ada foto portofolio yang diunggah oleh MUA ini.</p>
                <?php endif; ?>
            </div>

            <!-- Tombol Aksi Verifikasi / Kotak Informasi Status -->
            <div style="border-top: 1px solid #EFECE6; padding-top: 20px;">
                <?php if ($mua['status_verifikasi'] == 'pending') : ?>
                    <?php if ($error_aksi !== '') : ?>
                        <p style="margin: 0 0 14px; background: #FFEBEE; border: 1px solid #F5C2C7; color: #B71C1C; padding: 10px 14px; border-radius: 8px; font-size: 13px;"><?= htmlspecialchars($error_aksi); ?></p>
                    <?php endif; ?>
                    <div style="display: flex; gap: 12px; align-items: flex-start; flex-wrap: wrap;">
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="aksi" value="approve">
                            <input type="hidden" name="id" value="<?= $mua['id_user']; ?>">
                            <input type="hidden" name="token" value="<?= $_SESSION['admin_csrf']; ?>">
                            <button type="submit" style="background: #1B5E20; color: white; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-family: inherit; font-weight: 600; font-size: 13px;">Setujui Akun MUA</button>
                        </form>
                        <details <?= $error_aksi !== '' ? 'open' : ''; ?> style="flex: 1; min-width: 260px;">
                            <summary style="display: inline-block; list-style: none; background: #C62828; color: white; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px;">Tolak Akun MUA</summary>
                            <form method="POST" style="margin-top: 12px; display: flex; flex-direction: column; gap: 10px;">
                                <input type="hidden" name="aksi" value="reject">
                                <input type="hidden" name="id" value="<?= $mua['id_user']; ?>">
                                <input type="hidden" name="token" value="<?= $_SESSION['admin_csrf']; ?>">
                                <label for="alasan_tolak" style="font-size: 12px; font-weight: 600; color: #4A3B32;">Alasan penolakan (ditampilkan ke MUA)</label>
                                <textarea id="alasan_tolak" name="alasan_tolak" rows="3" minlength="10" maxlength="255" required placeholder="Contoh: Foto portofolio buram. Unggah ulang minimal 3 foto hasil riasan asli." style="padding: 10px 12px; border: 1px solid #D7CCC8; border-radius: 8px; font-family: inherit; font-size: 13px; resize: vertical;"><?= htmlspecialchars($_POST['alasan_tolak'] ?? ''); ?></textarea>
                                <button type="submit" style="align-self: flex-start; background: #FFFFFF; color: #C62828; border: 1px solid #C62828; padding: 9px 18px; border-radius: 8px; cursor: pointer; font-family: inherit; font-weight: 600; font-size: 13px;">Kirim Penolakan</button>
                            </form>
                        </details>
                    </div>
                <?php else : ?>
                    <!-- Kotak Informasi dengan Ikon SVG Status/Shield Baru yang Lebih Estetis -->
                    <div style="display: flex; align-items: center; gap: 12px; background: #FAFAFA; border: 1px solid #EFECE6; padding: 14px 20px; border-radius: 10px; color: #7A6F68; font-size: 13px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; color: #4A3B32;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                        <span>Akun MUA ini sudah berstatus <strong><?= ucfirst($mua['status_verifikasi']); ?></strong>. Tidak ada tindakan lanjutan yang diperlukan.<?php if ($mua['status_verifikasi'] == 'rejected' && !empty($mua['alasan_tolak'])) : ?><br><b>Alasan:</b> <?= htmlspecialchars($mua['alasan_tolak']); ?><?php endif; ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>