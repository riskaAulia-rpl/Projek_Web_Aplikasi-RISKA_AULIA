-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 12, 2026 at 03:00 PM
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
-- Database: `supermarket_mtk`
--

-- --------------------------------------------------------

--
-- Table structure for table `produk`
--

CREATE TABLE `produk` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama` varchar(24) NOT NULL,
  `harga` int(10) UNSIGNED NOT NULL,
  `rak` varchar(32) NOT NULL,
  `emoji` varchar(16) NOT NULL DEFAULT '?',
  `dibuat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `produk`
--

INSERT INTO `produk` (`id`, `nama`, `harga`, `rak`, `emoji`, `dibuat`) VALUES
(1, 'Susu Stroberi', 6500, 'Minuman', '🥛', '2026-10-07 08:42:24'),
(2, 'Jus Jeruk', 5000, 'Minuman', '🧃', '2026-10-07 08:42:24'),
(3, 'Boba Cokelat', 12000, 'Minuman', '🧋', '2026-10-07 08:42:24'),
(4, 'Air Mineral', 3000, 'Minuman', '💧', '2026-10-07 08:42:24'),
(5, 'Kopi Susu', 8000, 'Minuman', '☕', '2026-10-07 08:42:24'),
(6, 'Popcorn', 7000, 'Camilan', '🍿', '2026-10-07 08:42:24'),
(7, 'Cokelat Batang', 9500, 'Camilan', '🍫', '2026-10-07 08:42:24'),
(8, 'Lolipop', 2500, 'Camilan', '🍭', '2026-10-07 08:42:24'),
(9, 'Biskuit Mentega', 4500, 'Camilan', '🍪', '2026-10-07 08:42:24'),
(10, 'Permen Jelly', 3500, 'Camilan', '🍬', '2026-10-07 08:42:24'),
(11, 'Apel Merah', 4000, 'Buah & Sayur', '🍎', '2026-10-07 08:42:24'),
(12, 'Pisang Sesisir', 12000, 'Buah & Sayur', '🍌', '2026-10-07 08:42:24'),
(13, 'Stroberi', 15000, 'Buah & Sayur', '🍓', '2026-10-07 08:42:24'),
(14, 'Wortel', 3000, 'Buah & Sayur', '🥕', '2026-10-07 08:42:24'),
(15, 'Semangka Mini', 20000, 'Buah & Sayur', '🍉', '2026-10-07 08:42:24'),
(16, 'Brokoli', 8500, 'Buah & Sayur', '🥦', '2026-10-07 08:42:24'),
(17, 'Roti Tawar', 14000, 'Roti & Susu', '🍞', '2026-10-07 08:42:24'),
(18, 'Croissant', 9000, 'Roti & Susu', '🥐', '2026-10-07 08:42:24'),
(19, 'Cupcake', 8000, 'Roti & Susu', '🧁', '2026-10-07 08:42:24'),
(20, 'Keju Slice', 11000, 'Roti & Susu', '🧀', '2026-10-07 08:42:24'),
(21, 'Telur Ayam', 22000, 'Roti & Susu', '🥚', '2026-10-07 08:42:24'),
(22, 'Donat Gula', 6000, 'Roti & Susu', '🍩', '2026-10-07 08:42:24'),
(23, 'Sabun Mandi', 5500, 'Rumah Tangga', '🧼', '2026-10-07 08:42:24'),
(24, 'Tisu Gulung', 9000, 'Rumah Tangga', '🧻', '2026-10-07 08:42:24'),
(25, 'Sampo', 18000, 'Rumah Tangga', '🧴', '2026-10-07 08:42:24'),
(26, 'Sikat Gigi', 7000, 'Rumah Tangga', '🪥', '2026-10-07 08:42:24'),
(27, 'Spons Cuci', 3500, 'Rumah Tangga', '🧽', '2026-10-07 08:42:24'),
(28, 'Onigiri', 10500, 'Camilan', '🍙', '2026-10-07 12:30:59'),
(29, 'Yoghurt original', 8500, 'Minuman', '🥛', '2026-10-08 00:21:35'),
(30, 'Udang Segar', 25000, 'Seafood', '🦐', '2026-10-08 05:45:20'),
(31, 'Cumi-cumi', 28000, 'Seafood', '🦑', '2026-10-08 05:45:20'),
(32, 'Kepiting', 45000, 'Seafood', '🦀', '2026-10-08 05:45:20'),
(33, 'Lobster', 85000, 'Seafood', '🦞', '2026-10-08 05:45:20'),
(34, 'Ikan Kembung', 18000, 'Seafood', '🐟', '2026-10-08 05:45:20'),
(35, 'Ikan Salmon', 42000, 'Seafood', '🐠', '2026-10-08 05:45:20'),
(36, 'Gurita', 38000, 'Seafood', '🐙', '2026-10-08 05:45:20'),
(37, 'Kerang', 15000, 'Seafood', '🦪', '2026-10-08 05:45:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `produk`
--
ALTER TABLE `produk`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
