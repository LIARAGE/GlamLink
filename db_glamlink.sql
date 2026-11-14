-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 14, 2026 at 10:11 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_glamlink`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id_booking` int(11) NOT NULL,
  `no_invoice` varchar(20) NOT NULL,
  `id_mua` int(11) NOT NULL,
  `id_pelanggan` int(11) NOT NULL,
  `id_layanan` int(11) NOT NULL,
  `tanggal_booking` date NOT NULL,
  `waktu_booking` time NOT NULL,
  `lokasi_acara` varchar(255) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `total_harga` int(11) NOT NULL,
  `status` enum('menunggu','dikonfirmasi','selesai','ditolak') NOT NULL DEFAULT 'menunggu',
  `is_mua_done` tinyint(1) DEFAULT 0,
  `is_client_done` tinyint(1) DEFAULT 0,
  `foto_dokumentasi` varchar(255) DEFAULT NULL,
  `rating` int(1) DEFAULT NULL,
  `ulasan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `nama_acara` varchar(150) DEFAULT NULL,
  `jenis_acara` varchar(50) DEFAULT NULL,
  `kota_acara` varchar(50) DEFAULT NULL,
  `biaya_platform` int(11) NOT NULL DEFAULT 0,
  `metode_bayar` varchar(50) DEFAULT NULL,
  `dibayar_pada` timestamp NULL DEFAULT NULL,
  `dibatalkan_oleh` enum('klien','mua') DEFAULT NULL,
  `dibatalkan_pada` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id_booking`, `no_invoice`, `id_mua`, `id_pelanggan`, `id_layanan`, `tanggal_booking`, `waktu_booking`, `lokasi_acara`, `catatan`, `total_harga`, `status`, `is_mua_done`, `is_client_done`, `foto_dokumentasi`, `rating`, `ulasan`, `created_at`, `nama_acara`, `jenis_acara`, `kota_acara`, `biaya_platform`, `metode_bayar`, `dibayar_pada`, `dibatalkan_oleh`, `dibatalkan_pada`) VALUES
(2, 'GL-8921', 2, 1, 1, '2026-10-24', '04:00:00', NULL, NULL, 3000000, 'ditolak', 0, 0, NULL, NULL, NULL, '2026-10-16 18:25:27', NULL, NULL, NULL, 0, NULL, NULL, 'mua', NULL),
(3, 'GL-7742', 2, 1, 1, '2026-10-30', '16:00:00', NULL, NULL, 3000000, 'menunggu', 0, 0, NULL, NULL, NULL, '2026-10-16 18:25:27', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(4, 'GL-6001', 2, 1, 1, '2026-10-10', '08:00:00', 'Fairmont Jakarta, Gelora Bung Karno', 'Makeup natural aja', 3000000, 'selesai', 1, 1, 'dokum_sample_1.jpg', 5, 'Suka banget sama hasilnya! Tahan seharian walau keringetan.', '2026-10-16 18:25:27', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(5, 'GL-6002', 2, 1, 1, '2026-10-12', '10:00:00', 'Plataran Dharmawangsa, Jakarta Selatan', 'Minta flawless complexion', 3000000, 'selesai', 1, 1, 'dokum_5_1792401121.jpg', 4, 'Bagus, MUA-nya ramah dan on time. Sayang lipstiknya agak gampang hilang kalau dipakai makan.', '2026-10-16 18:25:27', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(6, 'GL-4288', 2, 1, 1, '2026-09-14', '10:00:00', 'Hutan Kota by Plataran, GBK', 'Smokey eyes agak bold', 3000000, 'selesai', 1, 1, 'dokum_6_1792401183.jpg', NULL, NULL, '2026-10-19 08:25:30', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(7, 'GL-9820', 2, 1, 2, '2026-10-19', '14:00:00', 'Hotel Mulia Senayan', 'Acaraku mulai jam 7 sebenarnya, tapi aku mau kelar sblm jam 6', 750000, 'dikonfirmasi', 0, 0, NULL, NULL, NULL, '2026-10-19 08:25:30', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(8, 'GL-3950', 2, 6, 2, '2026-10-27', '10:00:00', 'St. Regis', 'yang bener ya', 750000, 'menunggu', 0, 0, NULL, NULL, NULL, '2026-10-26 14:00:38', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(9, 'GL-7558', 2, 6, 1, '2026-10-28', '16:00:00', 'sebrang cilok bojot aa', 'apa aja dah', 3000000, 'menunggu', 0, 0, NULL, NULL, NULL, '2026-10-26 14:02:06', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(10, 'GL-5256', 4, 6, 3, '2026-10-30', '12:00:00', 'Hotel YTTA, Jakarta Barat', 'yang mantab ya', 100000, 'menunggu', 0, 0, NULL, NULL, NULL, '2026-10-29 17:23:21', 'Yang tau tau aja', 'Party', 'Jakarta Barat', 5000, 'Transfer Bank', '2026-10-29 17:23:21', NULL, NULL),
(11, 'GL-9101', 2, 1, 2, '2026-11-18', '06:00:00', 'Senopati, Jakarta Selatan', 'Mohon datang tepat waktu, acara mulai jam 08.00 ya kak.', 750000, 'menunggu', 0, 0, NULL, NULL, NULL, '2026-11-13 10:21:10', 'Wisuda S1 Universitas Indonesia', 'Wisuda', 'Jakarta Selatan', 37500, 'QRIS', '2026-11-13 10:21:10', NULL, NULL),
(12, 'GL-9102', 2, 6, 1, '2026-11-25', '04:00:00', 'The Ritz-Carlton Pacific Place, Jakarta', 'Akad jam 08.00, resepsi jam 11.00. Mau look soft glam, tidak terlalu tebal.', 3000000, 'menunggu', 0, 0, NULL, NULL, NULL, '2026-11-13 09:31:10', 'Pernikahan Kuncoro & Sari', 'Pernikahan', 'Jakarta Selatan', 150000, 'BCA Virtual Account', '2026-11-13 09:31:10', NULL, NULL),
(13, 'GL-9103', 2, 1, 2, '2026-12-03', '15:00:00', 'Hotel Mulia Senayan, Jakarta', 'Untuk gala dinner kantor, dress warna navy.', 750000, 'menunggu', 0, 0, NULL, NULL, NULL, '2026-11-13 07:31:10', 'Gala Dinner Akhir Tahun', 'Party', 'Jakarta Pusat', 37500, 'GoPay', '2026-11-13 07:31:10', NULL, NULL),
(14, 'GL-9104', 2, 6, 2, '2026-11-14', '08:00:00', 'Plataran Dharmawangsa, Jl. Dharmawangsa VIII No. 26, Jakarta Selatan', 'Makeup lamaran, tema sage green.', 750000, 'dikonfirmasi', 0, 0, NULL, NULL, NULL, '2026-11-11 10:31:10', 'Lamaran Dinda', 'Lamaran', 'Jakarta Selatan', 37500, 'Transfer Bank', '2026-11-11 10:31:10', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `chats`
--

CREATE TABLE `chats` (
  `id_chat` int(11) NOT NULL,
  `id_pengirim` int(11) NOT NULL,
  `id_penerima` int(11) NOT NULL,
  `pesan` text NOT NULL,
  `waktu_kirim` timestamp NOT NULL DEFAULT current_timestamp(),
  `dibaca_pada` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chats`
--

INSERT INTO `chats` (`id_chat`, `id_pengirim`, `id_penerima`, `pesan`, `waktu_kirim`, `dibaca_pada`) VALUES
(1, 2, 1, 'Halo Jocelyn Thank u sudah book', '2026-11-13 11:17:26', NULL),
(2, 2, 6, 'Halo Kuncoro, terima kasih', '2026-11-13 11:18:56', '2026-11-14 08:53:05');

-- --------------------------------------------------------

--
-- Table structure for table `favorit`
--

CREATE TABLE `favorit` (
  `id_favorit` int(11) NOT NULL,
  `id_pelanggan` int(11) NOT NULL,
  `id_mua` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `klien_notifikasi`
--

CREATE TABLE `klien_notifikasi` (
  `id_notif` int(11) NOT NULL,
  `id_pelanggan` int(11) NOT NULL,
  `kunci` varchar(100) NOT NULL COMMENT 'Penanda unik sumber, contoh: baru:12, bayar:12, konfirm:12',
  `tipe` varchar(30) NOT NULL COMMENT 'booking | bayar | konfirm | tolak | selesai | jadwal',
  `judul` varchar(150) NOT NULL,
  `pesan` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `label_aksi` varchar(50) DEFAULT NULL,
  `waktu` datetime NOT NULL,
  `dibaca_pada` datetime DEFAULT NULL,
  `dihapus_pada` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `laporan_mua`
--

CREATE TABLE `laporan_mua` (
  `id_laporan` int(11) NOT NULL,
  `id_pelapor` int(11) NOT NULL,
  `id_mua` int(11) NOT NULL,
  `alasan` varchar(100) NOT NULL,
  `detail` text NOT NULL,
  `bukti` varchar(255) DEFAULT NULL,
  `status` enum('baru','diproses','selesai') NOT NULL DEFAULT 'baru',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laporan_mua`
--

INSERT INTO `laporan_mua` (`id_laporan`, `id_pelapor`, `id_mua`, `alasan`, `detail`, `bukti`, `status`, `created_at`) VALUES
(1, 6, 2, 'Lainnya', 'saya juga bingung', 'bukti_70b252c73bab484e.jpg', 'baru', '2026-10-26 13:59:05');

-- --------------------------------------------------------

--
-- Table structure for table `mua_libur`
--

CREATE TABLE `mua_libur` (
  `id_libur` int(11) NOT NULL,
  `id_mua` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mua_libur`
--

INSERT INTO `mua_libur` (`id_libur`, `id_mua`, `tanggal`, `keterangan`) VALUES
(1, 2, '2026-11-13', NULL),
(2, 2, '2026-11-14', NULL),
(3, 2, '2026-11-15', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `mua_notifikasi`
--

CREATE TABLE `mua_notifikasi` (
  `id_notif` int(11) NOT NULL,
  `id_mua` int(11) NOT NULL,
  `kunci` varchar(100) NOT NULL COMMENT 'Penanda unik sumber, contoh: pesanan:12, bayar:12, langganan:3:segera',
  `tipe` varchar(30) NOT NULL COMMENT 'pesanan | bayar | jadwal | langganan | batal | selesai | ulasan',
  `judul` varchar(150) NOT NULL,
  `pesan` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `label_aksi` varchar(50) DEFAULT NULL,
  `waktu` datetime NOT NULL,
  `dibaca_pada` datetime DEFAULT NULL,
  `dihapus_pada` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mua_notifikasi`
--

INSERT INTO `mua_notifikasi` (`id_notif`, `id_mua`, `kunci`, `tipe`, `judul`, `pesan`, `link`, `label_aksi`, `waktu`, `dibaca_pada`, `dihapus_pada`) VALUES
(1, 2, 'pesanan:2', 'pesanan', 'Permintaan Pesanan Baru Masuk!', 'Jocelyn Nicole mengajukan pesanan baru: Wedding Makeup untuk tanggal 24 Okt 2026 pukul 04:00 WIB.', 'booking.php?tab=cancelled', 'Lihat Detail', '2026-10-17 01:25:27', '2026-11-13 17:27:31', NULL),
(2, 2, 'pesanan:3', 'pesanan', 'Permintaan Pesanan Baru Masuk!', 'Jocelyn Nicole mengajukan pesanan baru: Wedding Makeup untuk tanggal 30 Okt 2026 pukul 16:00 WIB.', 'booking.php?tab=scheduled', 'Tinjau & Konfirmasi', '2026-10-17 01:25:27', '2026-11-13 17:27:31', NULL),
(3, 2, 'pesanan:4', 'pesanan', 'Permintaan Pesanan Baru Masuk!', 'Jocelyn Nicole mengajukan pesanan baru: Wedding Makeup untuk tanggal 10 Okt 2026 pukul 08:00 WIB di Fairmont Jakarta, Gelora Bung Karno.', 'booking.php?tab=done', 'Lihat Riwayat', '2026-10-17 01:25:27', '2026-11-13 17:27:31', NULL),
(4, 2, 'ulasan:4', 'ulasan', 'Ulasan Baru ★★★★★', 'Jocelyn Nicole memberi rating 5/5 untuk Wedding Makeup.', 'profil.php?tab=review', 'Lihat Ulasan', '2026-11-13 17:27:31', '2026-11-13 17:27:31', NULL),
(5, 2, 'pesanan:5', 'pesanan', 'Permintaan Pesanan Baru Masuk!', 'Jocelyn Nicole mengajukan pesanan baru: Wedding Makeup untuk tanggal 12 Okt 2026 pukul 10:00 WIB di Plataran Dharmawangsa, Jakarta Selatan.', 'booking.php?tab=done', 'Lihat Riwayat', '2026-10-17 01:25:27', '2026-11-13 17:27:31', NULL),
(6, 2, 'ulasan:5', 'ulasan', 'Ulasan Baru ★★★★', 'Jocelyn Nicole memberi rating 4/5 untuk Wedding Makeup.', 'profil.php?tab=review', 'Lihat Ulasan', '2026-11-13 17:27:31', '2026-11-13 17:27:31', NULL),
(7, 2, 'pesanan:6', 'pesanan', 'Permintaan Pesanan Baru Masuk!', 'Jocelyn Nicole mengajukan pesanan baru: Wedding Makeup untuk tanggal 14 Sep 2026 pukul 10:00 WIB di Hutan Kota by Plataran, GBK.', 'booking.php?tab=done', 'Lihat Riwayat', '2026-10-19 15:25:30', '2026-11-13 17:27:31', NULL),
(8, 2, 'pesanan:7', 'pesanan', 'Permintaan Pesanan Baru Masuk!', 'Jocelyn Nicole mengajukan pesanan baru: Graduation untuk tanggal 19 Okt 2026 pukul 14:00 WIB di Hotel Mulia Senayan.', 'booking.php?tab=scheduled', 'Lihat Jadwal', '2026-10-19 15:25:30', '2026-11-13 17:27:31', NULL),
(9, 2, 'pesanan:8', 'pesanan', 'Permintaan Pesanan Baru Masuk!', 'kuncoro mengajukan pesanan baru: Graduation untuk tanggal 27 Okt 2026 pukul 10:00 WIB di St. Regis.', 'booking.php?tab=scheduled', 'Tinjau & Konfirmasi', '2026-10-26 21:00:38', '2026-11-13 17:27:31', NULL),
(10, 2, 'pesanan:9', 'pesanan', 'Permintaan Pesanan Baru Masuk!', 'kuncoro mengajukan pesanan baru: Wedding Makeup untuk tanggal 28 Okt 2026 pukul 16:00 WIB di sebrang cilok bojot aa.', 'booking.php?tab=scheduled', 'Tinjau & Konfirmasi', '2026-10-26 21:02:06', '2026-11-13 17:27:31', NULL),
(71, 2, 'pesanan:11', 'pesanan', 'Permintaan Pesanan Baru Masuk!', 'Jocelyn Nicole mengajukan pesanan baru: Graduation untuk tanggal 18 Nov 2026 pukul 06:00 WIB di Senopati, Jakarta Selatan.', 'booking.php?tab=scheduled', 'Tinjau & Konfirmasi', '2026-11-13 17:21:10', NULL, NULL),
(72, 2, 'bayar:11', 'bayar', 'Pembayaran Pesanan Diterima', 'Pembayaran pesanan GL-9101 sebesar Rp 750.000 dari Jocelyn Nicole melalui QRIS telah diterima GlamLink.', 'booking.php?tab=scheduled', 'Lihat Pesanan', '2026-11-13 17:21:10', NULL, NULL),
(73, 2, 'pesanan:12', 'pesanan', 'Permintaan Pesanan Baru Masuk!', 'kuncoro mengajukan pesanan baru: Wedding Makeup untuk tanggal 25 Nov 2026 pukul 04:00 WIB di The Ritz-Carlton Pacific Place, Jakarta.', 'booking.php?tab=scheduled', 'Tinjau & Konfirmasi', '2026-11-13 16:31:10', NULL, NULL),
(74, 2, 'bayar:12', 'bayar', 'Pembayaran Pesanan Diterima', 'Pembayaran pesanan GL-9102 sebesar Rp 3.000.000 dari kuncoro melalui BCA Virtual Account telah diterima GlamLink.', 'booking.php?tab=scheduled', 'Lihat Pesanan', '2026-11-13 16:31:10', NULL, NULL),
(75, 2, 'pesanan:13', 'pesanan', 'Permintaan Pesanan Baru Masuk!', 'Jocelyn Nicole mengajukan pesanan baru: Graduation untuk tanggal 3 Des 2026 pukul 15:00 WIB di Hotel Mulia Senayan, Jakarta.', 'booking.php?tab=scheduled', 'Tinjau & Konfirmasi', '2026-11-13 14:31:10', NULL, NULL),
(76, 2, 'bayar:13', 'bayar', 'Pembayaran Pesanan Diterima', 'Pembayaran pesanan GL-9103 sebesar Rp 750.000 dari Jocelyn Nicole melalui GoPay telah diterima GlamLink.', 'booking.php?tab=scheduled', 'Lihat Pesanan', '2026-11-13 14:31:10', NULL, NULL),
(77, 2, 'pesanan:14', 'pesanan', 'Permintaan Pesanan Baru Masuk!', 'kuncoro mengajukan pesanan baru: Graduation untuk tanggal 14 Nov 2026 pukul 08:00 WIB di Plataran Dharmawangsa, Jl. Dharmawangsa VIII No. 26, Jakarta Selatan.', 'booking.php?tab=scheduled', 'Lihat Jadwal', '2026-11-11 17:31:10', '2026-11-13 17:31:15', NULL),
(78, 2, 'bayar:14', 'bayar', 'Pembayaran Pesanan Diterima', 'Pembayaran pesanan GL-9104 sebesar Rp 750.000 dari kuncoro melalui Transfer Bank telah diterima GlamLink.', 'booking.php?tab=scheduled', 'Lihat Pesanan', '2026-11-11 17:31:10', NULL, NULL),
(79, 2, 'jadwal:14:besok', 'jadwal', 'Pengingat Jadwal Besok', 'Anda memiliki sesi Graduation bersama kuncoro besok pukul 08:00 WIB di Plataran Dharmawangsa, Jl. Dharmawangsa VIII No. 26, Jakarta Selatan.', 'https://www.google.com/maps/search/?api=1&query=Plataran%20Dharmawangsa%2C%20Jl.%20Dharmawangsa%20VIII%20No.%2026%2C%20Jakarta%20Selatan', 'Buka Navigasi Lokasi', '2026-11-13 17:31:15', NULL, NULL),
(140, 2, 'dana:4', 'dana', 'Dana Masuk ke Saldo Dompet!', 'Klien & MUA telah mengonfirmasi sesi GL-6001 selesai. Saldo sebesar Rp 3.000.000 telah masuk ke saldo dompet dan siap ditarik ke rekening bank atau e-wallet.', 'keuangan.php', 'Tarik Saldo Sekarang', '2026-11-13 18:14:36', NULL, NULL),
(143, 2, 'dana:5', 'dana', 'Dana Masuk ke Saldo Dompet!', 'Klien & MUA telah mengonfirmasi sesi GL-6002 selesai. Saldo sebesar Rp 3.000.000 telah masuk ke saldo dompet dan siap ditarik ke rekening bank atau e-wallet.', 'keuangan.php', 'Tarik Saldo Sekarang', '2026-11-13 18:14:36', NULL, NULL),
(146, 2, 'dana:6', 'dana', 'Dana Masuk ke Saldo Dompet!', 'Klien & MUA telah mengonfirmasi sesi GL-4288 selesai. Saldo sebesar Rp 3.000.000 telah masuk ke saldo dompet dan siap ditarik ke rekening bank atau e-wallet.', 'keuangan.php', 'Tarik Saldo Sekarang', '2026-11-13 18:14:36', NULL, NULL),
(159, 2, 'tarik:WD-2026-4551', 'tarik', 'Penarikan Dana Berhasil Diproses', 'Penarikan saldo sebesar Rp 9.000.000 ke Rekening BCA (123456789) a/n Kayla telah berhasil ditransfer.', 'keuangan.php', 'Lihat Riwayat Penarikan', '2026-11-13 18:15:54', '2026-11-13 18:15:54', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `mua_penarikan`
--

CREATE TABLE `mua_penarikan` (
  `id_penarikan` int(11) NOT NULL,
  `no_referensi` varchar(20) NOT NULL,
  `id_mua` int(11) NOT NULL,
  `tujuan` varchar(30) NOT NULL COMMENT 'BCA, Mandiri, BRI, BNI, GoPay, OVO, DANA',
  `jenis` enum('bank','ewallet') NOT NULL,
  `nomor_tujuan` varchar(30) NOT NULL COMMENT 'No. rekening / No. HP e-wallet',
  `nama_penerima` varchar(100) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `status` enum('diproses','berhasil','gagal') NOT NULL DEFAULT 'berhasil',
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mua_penarikan`
--

INSERT INTO `mua_penarikan` (`id_penarikan`, `no_referensi`, `id_mua`, `tujuan`, `jenis`, `nomor_tujuan`, `nama_penerima`, `jumlah`, `status`, `dibuat_pada`) VALUES
(1, 'WD-2026-4551', 2, 'BCA', 'bank', '123456789', 'Kayla', 9000000, 'berhasil', '2026-11-13 11:15:54');

-- --------------------------------------------------------

--
-- Table structure for table `mua_profil`
--

CREATE TABLE `mua_profil` (
  `id_mua` int(11) NOT NULL,
  `tagline` varchar(150) DEFAULT NULL COMMENT 'Kalimat singkat di bawah nama brand',
  `alamat` varchar(255) DEFAULT NULL COMMENT 'Alamat studio/domisili lengkap',
  `spesialisasi` varchar(255) DEFAULT NULL COMMENT 'Dipisah koma, contoh: Pernikahan,Glam,Natural',
  `foto_sampul` varchar(255) DEFAULT NULL COMMENT 'Nama file foto cover di assets/img',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mua_profil`
--

INSERT INTO `mua_profil` (`id_mua`, `tagline`, `alamat`, `spesialisasi`, `foto_sampul`, `updated_at`) VALUES
(2, NULL, NULL, NULL, NULL, '2026-11-13 07:08:05');

-- --------------------------------------------------------

--
-- Table structure for table `mua_subscriptions`
--

CREATE TABLE `mua_subscriptions` (
  `id_transaksi` int(11) NOT NULL,
  `no_invoice` varchar(50) NOT NULL,
  `id_mua` int(11) NOT NULL,
  `id_paket` int(11) NOT NULL,
  `metode_bayar` varchar(50) NOT NULL,
  `nominal_bayar` int(11) NOT NULL,
  `tanggal_transaksi` timestamp NOT NULL DEFAULT current_timestamp(),
  `tanggal_berakhir` date NOT NULL,
  `status` enum('aktif','kedaluwarsa','menunggu') NOT NULL DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mua_subscriptions`
--

INSERT INTO `mua_subscriptions` (`id_transaksi`, `no_invoice`, `id_mua`, `id_paket`, `metode_bayar`, `nominal_bayar`, `tanggal_transaksi`, `tanggal_berakhir`, `status`) VALUES
(1, 'INV-SUB-2025-1012', 2, 2, 'GoPay', 110000, '2026-08-12 08:20:00', '2026-09-11', 'kedaluwarsa'),
(2, 'INV-SUB-2026-0419', 2, 2, 'BCA Virtual Account', 110000, '2026-09-19 02:00:00', '2026-10-20', 'kedaluwarsa'),
(3, 'INV-SUB-2026-7476', 2, 2, 'QRIS Instan', 99000, '2026-10-19 10:06:38', '2026-11-19', 'aktif'),
(4, 'INV-SUB-2026-429271', 2, 1, 'Kartu Kredit / Debit', 30000, '2026-11-13 05:54:51', '2026-11-26', 'aktif');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id_reset` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `kedaluwarsa` datetime NOT NULL,
  `dipakai_pada` datetime DEFAULT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id_reset`, `id_user`, `token_hash`, `kedaluwarsa`, `dipakai_pada`, `dibuat_pada`, `ip`) VALUES
(1, 2, '3949b1caab77dcf9de445747efffc9dcb28c2a20a72eb0d23064825e2f8b835e', '2026-11-13 19:06:29', '2026-11-13 18:37:00', '2026-11-13 11:36:29', '::1');

-- --------------------------------------------------------

--
-- Table structure for table `portfolios`
--

CREATE TABLE `portfolios` (
  `id_portfolio` int(11) NOT NULL,
  `id_mua` int(11) NOT NULL,
  `foto` varchar(255) NOT NULL,
  `judul` varchar(100) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `tag` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `portfolios`
--

INSERT INTO `portfolios` (`id_portfolio`, `id_mua`, `foto`, `judul`, `deskripsi`, `tag`) VALUES
(1, 2, 'porto_a47d0e26c8b16bdf.jpg', NULL, NULL, NULL),
(2, 4, 'porto_e683e900e710d963.jpg', NULL, NULL, NULL),
(3, 5, 'porto_c8c3e5e1d7a7bf5f.jpg', NULL, NULL, NULL),
(4, 2, 'porto_6b57e3c45859f6f9.jpg', 'Adat Jawa', 'Riasan akad dengan adat Jawa', 'Jawa,Siger,KoreanDewy,Adat');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id_service` int(11) NOT NULL,
  `id_mua` int(11) NOT NULL,
  `nama_layanan` varchar(150) NOT NULL,
  `kategori` varchar(100) DEFAULT NULL,
  `harga` decimal(10,2) NOT NULL,
  `durasi` varchar(50) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `fasilitas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id_service`, `id_mua`, `nama_layanan`, `kategori`, `harga`, `durasi`, `deskripsi`, `fasilitas`) VALUES
(1, 2, 'Wedding Makeup', 'Bridal', 3000000.00, '1 hari', '1 day service for Bride. Make Up and Touch Up.', NULL),
(2, 2, 'Graduation', 'Makeup', 750000.00, '120 menit', 'Complexion 1 hari', '[\"Pouch mini retouch gratis\",\"Glow area pundak & leher\",\"Pemasangan softlens ramah mata\"]'),
(3, 4, 'Make Up Wedding', 'Bridal', 100000.00, '120 menit', 'mantab 1000%', NULL),
(4, 5, 'ga tau dah', 'Bridal', 200000.00, '200 menit', 'dijamin bagus', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subscription_packages`
--

CREATE TABLE `subscription_packages` (
  `id_paket` int(11) NOT NULL,
  `nama_paket` varchar(100) NOT NULL,
  `durasi_hari` int(11) NOT NULL,
  `harga` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscription_packages`
--

INSERT INTO `subscription_packages` (`id_paket`, `nama_paket`, `durasi_hari`, `harga`) VALUES
(1, 'Starter Studio', 7, 30000),
(2, 'Pro Glam Artist', 30, 110000),
(3, 'Elite Master Studio', 90, 300000);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `nomor_telepon` varchar(20) DEFAULT NULL,
  `nama_brand` varchar(150) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `lokasi` varchar(150) DEFAULT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('mua','pelanggan','admin') NOT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp(),
  `status_verifikasi` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  `alasan_tolak` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `nama`, `email`, `nomor_telepon`, `nama_brand`, `bio`, `lokasi`, `foto_profil`, `password`, `role`, `dibuat_pada`, `status_verifikasi`, `alasan_tolak`) VALUES
(1, 'Jocelyn Nicole', 'jocelyn@gmail.com', NULL, NULL, NULL, NULL, '9f567205-162e-4c5e-a3c5-bbe07dd47cf0.jpg', '$2y$10$KfUdb8DbOKU8H2hrssUrjO9YmOMbX1OKKzTArE/VV78ZKkKkiyeXC', 'pelanggan', '2026-10-04 19:41:41', 'approved', NULL),
(2, 'Kayla', 'kayla@gmail.com', '08123456789', 'Kayla Studio', 'Professional Make Up Artist in Greater Jakarta. Since 2020.', 'Jakarta Timur, Bogor, Jakarta Barat, Jakarta Selatan', 'profil_f503a6a7668d8e62.jpg', '$2y$10$rjqMP/OoC4piHLtOwaFtY.pVKT4Vv3sXkxEjUzKWdNU6EnAh7ijby', 'mua', '2026-10-16 16:00:25', 'approved', NULL),
(4, 'alex', 'alex@gmail.com', '081234567890', 'Mpruy Studio', 'Studio tergacor', 'Jakarta Timur', 'profil_713a502cf5c43137.jpg', '$2y$10$ETqMldWHGvcdyxDIxSDb0ulfe5CAHNfIykRGXL4nxd0EjMZdYUWzy', 'mua', '2026-10-17 21:17:26', 'approved', NULL),
(5, 'ferdi', 'ferdi@gmail.com', '081234567890', 'Gacor Studio', 'pasti gacor', 'Jakarta Timur', 'profil_5092b841046d0c6c.jpg', '$2y$10$aceJkSjULSLhuVIcuapwPulTBP0xxBKnfmXJwjUA1m9JiZCbYoto2', 'mua', '2026-10-17 22:09:29', 'pending', NULL),
(6, 'kuncoro', 'kuncoro@gmail.com', '081234567890', NULL, NULL, NULL, NULL, '$2y$10$KbxJYMf3nJ0i8pHk1Y9p6OcggB.aY0CIfKzUW6NlfiPQu/OmpQebC', 'pelanggan', '2026-10-19 06:17:44', 'approved', NULL),
(7, 'Admin 1', 'admin@gmail.com', NULL, NULL, NULL, NULL, NULL, 'admin123', 'admin', '2026-10-19 13:55:41', 'approved', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id_booking`),
  ADD UNIQUE KEY `no_invoice` (`no_invoice`),
  ADD KEY `id_mua` (`id_mua`),
  ADD KEY `id_pelanggan` (`id_pelanggan`),
  ADD KEY `id_layanan` (`id_layanan`);

--
-- Indexes for table `chats`
--
ALTER TABLE `chats`
  ADD PRIMARY KEY (`id_chat`),
  ADD KEY `id_pengirim` (`id_pengirim`),
  ADD KEY `id_penerima` (`id_penerima`),
  ADD KEY `idx_chat_percakapan` (`id_pengirim`,`id_penerima`,`id_chat`);

--
-- Indexes for table `favorit`
--
ALTER TABLE `favorit`
  ADD PRIMARY KEY (`id_favorit`),
  ADD UNIQUE KEY `uq_favorit` (`id_pelanggan`,`id_mua`),
  ADD KEY `id_mua` (`id_mua`);

--
-- Indexes for table `klien_notifikasi`
--
ALTER TABLE `klien_notifikasi`
  ADD PRIMARY KEY (`id_notif`),
  ADD UNIQUE KEY `uq_klien_notif_kunci` (`id_pelanggan`,`kunci`),
  ADD KEY `idx_klien_notif_list` (`id_pelanggan`,`dihapus_pada`,`waktu`);

--
-- Indexes for table `laporan_mua`
--
ALTER TABLE `laporan_mua`
  ADD PRIMARY KEY (`id_laporan`),
  ADD KEY `id_pelapor` (`id_pelapor`),
  ADD KEY `id_mua` (`id_mua`);

--
-- Indexes for table `mua_libur`
--
ALTER TABLE `mua_libur`
  ADD PRIMARY KEY (`id_libur`),
  ADD UNIQUE KEY `uq_libur` (`id_mua`,`tanggal`);

--
-- Indexes for table `mua_notifikasi`
--
ALTER TABLE `mua_notifikasi`
  ADD PRIMARY KEY (`id_notif`),
  ADD UNIQUE KEY `uq_notif_kunci` (`id_mua`,`kunci`),
  ADD KEY `idx_notif_list` (`id_mua`,`dihapus_pada`,`waktu`);

--
-- Indexes for table `mua_penarikan`
--
ALTER TABLE `mua_penarikan`
  ADD PRIMARY KEY (`id_penarikan`),
  ADD UNIQUE KEY `uq_penarikan_ref` (`no_referensi`),
  ADD KEY `idx_penarikan_mua` (`id_mua`,`dibuat_pada`);

--
-- Indexes for table `mua_profil`
--
ALTER TABLE `mua_profil`
  ADD PRIMARY KEY (`id_mua`);

--
-- Indexes for table `mua_subscriptions`
--
ALTER TABLE `mua_subscriptions`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD UNIQUE KEY `uq_no_invoice_sub` (`no_invoice`),
  ADD KEY `id_mua` (`id_mua`),
  ADD KEY `id_paket` (`id_paket`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id_reset`),
  ADD UNIQUE KEY `uq_reset_token` (`token_hash`),
  ADD KEY `idx_reset_user` (`id_user`,`dibuat_pada`);

--
-- Indexes for table `portfolios`
--
ALTER TABLE `portfolios`
  ADD PRIMARY KEY (`id_portfolio`),
  ADD KEY `id_mua` (`id_mua`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id_service`),
  ADD KEY `id_mua` (`id_mua`);

--
-- Indexes for table `subscription_packages`
--
ALTER TABLE `subscription_packages`
  ADD PRIMARY KEY (`id_paket`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id_booking` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `chats`
--
ALTER TABLE `chats`
  MODIFY `id_chat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `favorit`
--
ALTER TABLE `favorit`
  MODIFY `id_favorit` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `klien_notifikasi`
--
ALTER TABLE `klien_notifikasi`
  MODIFY `id_notif` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `laporan_mua`
--
ALTER TABLE `laporan_mua`
  MODIFY `id_laporan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `mua_libur`
--
ALTER TABLE `mua_libur`
  MODIFY `id_libur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `mua_notifikasi`
--
ALTER TABLE `mua_notifikasi`
  MODIFY `id_notif` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=270;

--
-- AUTO_INCREMENT for table `mua_penarikan`
--
ALTER TABLE `mua_penarikan`
  MODIFY `id_penarikan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `mua_subscriptions`
--
ALTER TABLE `mua_subscriptions`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id_reset` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `portfolios`
--
ALTER TABLE `portfolios`
  MODIFY `id_portfolio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id_service` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `subscription_packages`
--
ALTER TABLE `subscription_packages`
  MODIFY `id_paket` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`id_mua`) REFERENCES `users` (`id_user`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`id_pelanggan`) REFERENCES `users` (`id_user`),
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`id_layanan`) REFERENCES `services` (`id_service`);

--
-- Constraints for table `chats`
--
ALTER TABLE `chats`
  ADD CONSTRAINT `chats_ibfk_1` FOREIGN KEY (`id_pengirim`) REFERENCES `users` (`id_user`),
  ADD CONSTRAINT `chats_ibfk_2` FOREIGN KEY (`id_penerima`) REFERENCES `users` (`id_user`);

--
-- Constraints for table `favorit`
--
ALTER TABLE `favorit`
  ADD CONSTRAINT `favorit_ibfk_1` FOREIGN KEY (`id_pelanggan`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorit_ibfk_2` FOREIGN KEY (`id_mua`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `klien_notifikasi`
--
ALTER TABLE `klien_notifikasi`
  ADD CONSTRAINT `fk_klien_notif_user` FOREIGN KEY (`id_pelanggan`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `laporan_mua`
--
ALTER TABLE `laporan_mua`
  ADD CONSTRAINT `laporan_mua_ibfk_1` FOREIGN KEY (`id_pelapor`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `laporan_mua_ibfk_2` FOREIGN KEY (`id_mua`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `mua_libur`
--
ALTER TABLE `mua_libur`
  ADD CONSTRAINT `mua_libur_ibfk_1` FOREIGN KEY (`id_mua`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `mua_notifikasi`
--
ALTER TABLE `mua_notifikasi`
  ADD CONSTRAINT `fk_notif_mua` FOREIGN KEY (`id_mua`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `mua_penarikan`
--
ALTER TABLE `mua_penarikan`
  ADD CONSTRAINT `fk_penarikan_mua` FOREIGN KEY (`id_mua`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `mua_profil`
--
ALTER TABLE `mua_profil`
  ADD CONSTRAINT `fk_profil_mua` FOREIGN KEY (`id_mua`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `mua_subscriptions`
--
ALTER TABLE `mua_subscriptions`
  ADD CONSTRAINT `fk_sub_mua` FOREIGN KEY (`id_mua`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sub_paket` FOREIGN KEY (`id_paket`) REFERENCES `subscription_packages` (`id_paket`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_reset_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `portfolios`
--
ALTER TABLE `portfolios`
  ADD CONSTRAINT `portfolios_ibfk_1` FOREIGN KEY (`id_mua`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`id_mua`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
