-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 19, 2026 at 12:10 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_glamlink`
--
-- FILE GABUNGAN: dump terbaru dari tim (19 Okt 2026) + tabel/kolom fitur Detail MUA
-- (favorit, laporan_mua, mua_libur, portfolios.judul/deskripsi/tag).
-- Cara pakai: phpMyAdmin > db_glamlink > Import. PERINGATAN: semua tabel DIGANTI (DROP + CREATE).
-- File migrasi_detail_mua.sql TIDAK perlu dijalankan lagi.
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id_booking`, `no_invoice`, `id_mua`, `id_pelanggan`, `id_layanan`, `tanggal_booking`, `waktu_booking`, `lokasi_acara`, `catatan`, `total_harga`, `status`, `is_mua_done`, `is_client_done`, `foto_dokumentasi`, `rating`, `ulasan`, `created_at`) VALUES
(2, 'GL-8921', 2, 1, 1, '2026-10-24', '04:00:00', NULL, NULL, 3000000, 'ditolak', 0, 0, NULL, NULL, NULL, '2026-10-16 18:25:27'),
(3, 'GL-7742', 2, 1, 1, '2026-10-30', '16:00:00', NULL, NULL, 3000000, 'menunggu', 0, 0, NULL, NULL, NULL, '2026-10-16 18:25:27'),
(4, 'GL-6001', 2, 1, 1, '2026-10-10', '08:00:00', 'Fairmont Jakarta, Gelora Bung Karno', 'Makeup natural aja', 3000000, 'selesai', 1, 1, 'dokum_sample_1.jpg', 5, 'Suka banget sama hasilnya! Tahan seharian walau keringetan.', '2026-10-16 18:25:27'),
(5, 'GL-6002', 2, 1, 1, '2026-10-12', '10:00:00', 'Plataran Dharmawangsa, Jakarta Selatan', 'Minta flawless complexion', 3000000, 'selesai', 1, 1, 'dokum_5_1792401121.jpg', 4, 'Bagus, MUA-nya ramah dan on time. Sayang lipstiknya agak gampang hilang kalau dipakai makan.', '2026-10-16 18:25:27'),
(6, 'GL-4288', 2, 1, 1, '2026-09-14', '10:00:00', 'Hutan Kota by Plataran, GBK', 'Smokey eyes agak bold', 3000000, 'selesai', 1, 1, 'dokum_6_1792401183.jpg', NULL, NULL, '2026-10-19 08:25:30'),
(7, 'GL-9820', 2, 1, 2, '2026-10-19', '14:00:00', 'Hotel Mulia Senayan', 'Acaraku mulai jam 7 sebenarnya, tapi aku mau kelar sblm jam 6', 750000, 'dikonfirmasi', 0, 0, NULL, NULL, NULL, '2026-10-19 08:25:30');

-- --------------------------------------------------------

--
-- Table structure for table `chats`
--

DROP TABLE IF EXISTS `chats`;
CREATE TABLE `chats` (
  `id_chat` int(11) NOT NULL,
  `id_pengirim` int(11) NOT NULL,
  `id_penerima` int(11) NOT NULL,
  `pesan` text NOT NULL,
  `waktu_kirim` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mua_subscriptions`
--

DROP TABLE IF EXISTS `mua_subscriptions`;
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
(2, 'INV-SUB-2026-0419', 2, 2, 'BCA Virtual Account', 110000, '2026-09-19 02:00:00', '2026-10-20', 'aktif'),
(3, 'INV-SUB-2026-7476', 2, 2, 'QRIS Instan', 99000, '2026-10-19 10:06:38', '2026-11-19', 'aktif');

-- --------------------------------------------------------

--
-- Table structure for table `portfolios`
--

DROP TABLE IF EXISTS `portfolios`;
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
(3, 5, 'porto_c8c3e5e1d7a7bf5f.jpg', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
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

DROP TABLE IF EXISTS `subscription_packages`;
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

DROP TABLE IF EXISTS `users`;
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
(2, 'Kayla', 'kayla@gmail.com', '08123456789', 'Kayla Studio', 'Professional Make Up Artist in Greater Jakarta. Since 2020.', 'Greater Jakarta', 'profil_f503a6a7668d8e62.jpg', '$2y$10$bzoVf/3N4E6IW8/.zWqmLuKdHqVH7hgSD8M7wolWAquX/HzqrbU0u', 'mua', '2026-10-16 16:00:25', 'approved', NULL),
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
  ADD KEY `id_penerima` (`id_penerima`);

--
-- Indexes for table `mua_subscriptions`
--
ALTER TABLE `mua_subscriptions`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_mua` (`id_mua`),
  ADD KEY `id_paket` (`id_paket`);

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
  MODIFY `id_booking` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `chats`
--
ALTER TABLE `chats`
  MODIFY `id_chat` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mua_subscriptions`
--
ALTER TABLE `mua_subscriptions`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `portfolios`
--
ALTER TABLE `portfolios`
  MODIFY `id_portfolio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- Constraints for table `mua_subscriptions`
--
ALTER TABLE `mua_subscriptions`
  ADD CONSTRAINT `fk_sub_mua` FOREIGN KEY (`id_mua`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sub_paket` FOREIGN KEY (`id_paket`) REFERENCES `subscription_packages` (`id_paket`) ON DELETE CASCADE;

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
-- --------------------------------------------------------

--
-- Tabel baru (fitur Detail MUA): favorit klien
--

DROP TABLE IF EXISTS `favorit`;
CREATE TABLE `favorit` (
  `id_favorit` int(11) NOT NULL AUTO_INCREMENT,
  `id_pelanggan` int(11) NOT NULL,
  `id_mua` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_favorit`),
  UNIQUE KEY `uq_favorit` (`id_pelanggan`,`id_mua`),
  KEY `id_mua` (`id_mua`),
  CONSTRAINT `favorit_ibfk_1` FOREIGN KEY (`id_pelanggan`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `favorit_ibfk_2` FOREIGN KEY (`id_mua`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tabel baru: laporan klien terhadap MUA (ditinjau admin)
--

DROP TABLE IF EXISTS `laporan_mua`;
CREATE TABLE `laporan_mua` (
  `id_laporan` int(11) NOT NULL AUTO_INCREMENT,
  `id_pelapor` int(11) NOT NULL,
  `id_mua` int(11) NOT NULL,
  `alasan` varchar(100) NOT NULL,
  `detail` text NOT NULL,
  `bukti` varchar(255) DEFAULT NULL,
  `status` enum('baru','diproses','selesai') NOT NULL DEFAULT 'baru',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_laporan`),
  KEY `id_pelapor` (`id_pelapor`),
  KEY `id_mua` (`id_mua`),
  CONSTRAINT `laporan_mua_ibfk_1` FOREIGN KEY (`id_pelapor`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `laporan_mua_ibfk_2` FOREIGN KEY (`id_mua`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tabel baru: hari cuti MUA (merah di kalender Jadwal, tidak bisa dibooking)
--

DROP TABLE IF EXISTS `mua_libur`;
CREATE TABLE `mua_libur` (
  `id_libur` int(11) NOT NULL AUTO_INCREMENT,
  `id_mua` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id_libur`),
  UNIQUE KEY `uq_libur` (`id_mua`,`tanggal`),
  CONSTRAINT `mua_libur_ibfk_1` FOREIGN KEY (`id_mua`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
