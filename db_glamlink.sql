-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 19, 2026 at 08:53 AM
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id_booking`, `no_invoice`, `id_mua`, `id_pelanggan`, `id_layanan`, `tanggal_booking`, `waktu_booking`, `lokasi_acara`, `catatan`, `total_harga`, `status`, `is_mua_done`, `is_client_done`, `created_at`) VALUES
(2, 'GL-8921', 2, 1, 1, '2026-10-24', '04:00:00', NULL, NULL, 3000000, 'menunggu', 0, 0, '2026-10-16 18:25:27'),
(3, 'GL-7742', 2, 1, 1, '2026-10-30', '16:00:00', NULL, NULL, 3000000, 'menunggu', 0, 0, '2026-10-16 18:25:27'),
(4, 'GL-6001', 2, 1, 1, '2026-10-10', '08:00:00', NULL, NULL, 3000000, 'selesai', 0, 0, '2026-10-16 18:25:27'),
(5, 'GL-6002', 2, 1, 1, '2026-10-12', '10:00:00', NULL, NULL, 3000000, 'selesai', 0, 0, '2026-10-16 18:25:27');

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
-- Table structure for table `portfolios`
--

DROP TABLE IF EXISTS `portfolios`;
CREATE TABLE `portfolios` (
  `id_portfolio` int(11) NOT NULL,
  `id_mua` int(11) NOT NULL,
  `foto` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `portfolios`
--

INSERT INTO `portfolios` (`id_portfolio`, `id_mua`, `foto`) VALUES
(1, 2, 'porto_a47d0e26c8b16bdf.jpg'),
(2, 4, 'porto_e683e900e710d963.jpg'),
(3, 5, 'porto_c8c3e5e1d7a7bf5f.jpg');

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
(2, 2, 'Graduation', 'Makeup', 750000.00, '', 'Complexion 1 hari', '[\"Pouch mini retouch gratis\",\"Glow area pundak & leher\",\"Pemasangan softlens ramah mata\"]'),
(3, 4, 'Make Up Wedding', 'Bridal', 100000.00, '120 menit', 'mantab 1000%', NULL),
(4, 5, 'ga tau dah', 'Bridal', 200000.00, '200 menit', 'dijamin bagus', NULL);

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
(1, 'Jocelyn Nicole', 'jocelyn@gmail.com', NULL, NULL, NULL, NULL, NULL, '$2y$10$KfUdb8DbOKU8H2hrssUrjO9YmOMbX1OKKzTArE/VV78ZKkKkiyeXC', 'pelanggan', '2026-10-04 19:41:41', 'approved', NULL),
(2, 'Kayla', 'kayla@gmail.com', '08123456789', 'Kayla Studio', 'Professional Make Up Artist in Greater Jakarta. Since 2020.', 'Greater Jakarta', 'profil_f503a6a7668d8e62.jpg', '$2y$10$bzoVf/3N4E6IW8/.zWqmLuKdHqVH7hgSD8M7wolWAquX/HzqrbU0u', 'mua', '2026-10-16 16:00:25', 'approved', NULL),
(4, 'alex', 'alex@gmail.com', '081234567890', 'Mpruy Studio', 'Studio tergacor', 'Jakarta Timur', 'profil_713a502cf5c43137.jpg', '$2y$10$ETqMldWHGvcdyxDIxSDb0ulfe5CAHNfIykRGXL4nxd0EjMZdYUWzy', 'mua', '2026-10-17 21:17:26', 'approved', NULL),
(5, 'ferdi', 'ferdi@gmail.com', '081234567890', 'Gacor Studio', 'pasti gacor', 'Jakarta Timur', 'profil_5092b841046d0c6c.jpg', '$2y$10$aceJkSjULSLhuVIcuapwPulTBP0xxBKnfmXJwjUA1m9JiZCbYoto2', 'mua', '2026-10-17 22:09:29', 'pending', NULL),
(6, 'kuncoro', 'kuncoro@gmail.com', '081234567890', NULL, NULL, NULL, NULL, '$2y$10$KbxJYMf3nJ0i8pHk1Y9p6OcggB.aY0CIfKzUW6NlfiPQu/OmpQebC', 'pelanggan', '2026-10-19 06:17:44', 'approved', NULL);

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
  MODIFY `id_booking` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `chats`
--
ALTER TABLE `chats`
  MODIFY `id_chat` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
