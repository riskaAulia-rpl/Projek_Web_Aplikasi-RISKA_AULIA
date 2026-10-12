-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 12, 2026 at 03:21 PM
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
-- Database: `db_bkjl`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_sistem`
--

CREATE TABLE `admin_sistem` (
  `id_admin` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `no_telp` varchar(15) DEFAULT NULL,
  `status_akun` varchar(20) DEFAULT 'Aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_sistem`
--

INSERT INTO `admin_sistem` (`id_admin`, `username`, `password`, `foto_profil`, `nama_lengkap`, `email`, `no_telp`, `status_akun`) VALUES
(1, 'admin_bkjl', '$2y$10$EeX0n53.ASHxA9ZuEt3XJezjWyYlNz8TXSLfL2TL4IPHFwcMlrYYW', 'foto_admin.jpeg', 'RISKA AULIA', 'admin_utama.bkjl@gmail.com', '0821-2002-5508', 'Super Admin');

-- --------------------------------------------------------

--
-- Table structure for table `hubungi_kami`
--

CREATE TABLE `hubungi_kami` (
  `id_pesan` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subjek` varchar(150) NOT NULL,
  `pesan` text NOT NULL,
  `tanggal_kirim` datetime NOT NULL DEFAULT current_timestamp(),
  `sudah_dibaca` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hubungi_kami`
--

INSERT INTO `hubungi_kami` (`id_pesan`, `nama`, `email`, `subjek`, `pesan`, `tanggal_kirim`, `sudah_dibaca`) VALUES
(5, 'Riska', 'riska@gmail.com', 'Jenis layanan', 'ASDFGH', '2026-07-16 13:36:59', 1),
(6, 'KEYZZZ', 'keyy@gmail.com', '123', '345678', '2026-07-16 13:37:26', 1),
(9, 'Yuni', 'yunyun@gmail.com', 'QNA', 'Helo', '2026-07-24 09:47:01', 1);

-- --------------------------------------------------------

--
-- Table structure for table `pendaftaran_member`
--

CREATE TABLE `pendaftaran_member` (
  `id_member` varchar(20) NOT NULL,
  `kategori_keanggotaan` varchar(50) NOT NULL,
  `nip` varchar(13) NOT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jenis_kelamin` varchar(1) DEFAULT NULL,
  `mulai_kerja` date DEFAULT NULL,
  `tanggal_kadaluwarsa_sim` date DEFAULT NULL,
  `nama_penanggung_jawab` varchar(100) NOT NULL,
  `jabatan` varchar(50) NOT NULL,
  `email_aktif` varchar(100) NOT NULL,
  `nomor_whatsapp` varchar(20) NOT NULL,
  `tanggal_join` date NOT NULL,
  `status_manifest` varchar(30) DEFAULT 'Labuh Tambat (Pending)',
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `gaji_pokok` varchar(50) NOT NULL DEFAULT '',
  `tunjangan` varchar(50) NOT NULL DEFAULT '',
  `role` varchar(30) NOT NULL DEFAULT 'member',
  `foto_profil` varchar(255) DEFAULT NULL,
  `lampiran_dokumen` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pendaftaran_member`
--

INSERT INTO `pendaftaran_member` (`id_member`, `kategori_keanggotaan`, `nip`, `tanggal_lahir`, `jenis_kelamin`, `mulai_kerja`, `tanggal_kadaluwarsa_sim`, `nama_penanggung_jawab`, `jabatan`, `email_aktif`, `nomor_whatsapp`, `tanggal_join`, `status_manifest`, `username`, `password`, `gaji_pokok`, `tunjangan`, `role`, `foto_profil`, `lampiran_dokumen`, `created_at`) VALUES
('PGW-001', 'Staff Kantor', '123456789', '2026-07-21', 'P', '2026-06-12', '2026-08-08', 'RISKA AULIA', 'WAKIL MANAJER', 'riska@gmail.com', '08212002', '2026-07-21', 'Aktif', 'MBG', '$2y$10$Pm/HoLGUpMCEw33t.JGdbOQSjbPWoEBZl7r/qhgXCSjtDpacwe0Gq', 'Rp 15.000.000', 'Rp 1.000.000', 'member', 'IKA_1782177877.jpeg', 'lampiran_PGW-001_1785121512.jpeg', '2026-07-23 07:28:34'),
('PGW-002', 'Stevedore', '123456788', '2010-01-14', 'P', '2026-07-20', '2026-07-30', 'KEYZHA', 'ADVISOR', 'keizhamaharani900@gmail.com', '082134568900', '2026-07-23', 'Aktif', 'KEYZA', '$2y$10$5ZNFDbtKolNhvG2Y3pVc0.n/OrZpMMNMSG2lQjf9W0hFkWFCsAfna', 'Rp 5.000.000', 'Rp 950.000', 'member', 'KEYZA_1782178920.jpeg', 'lampiran_PGW-002_1784789691.jpeg', '2026-07-23 07:28:34'),
('BKJL-018', 'Staff Kantor', '123456', '2026-07-20', 'P', '2026-07-22', '2026-07-28', 'GIA (Golf India Alpha)', 'FOREMAN KADE', 'ljfjjkah@gmail.com', '12345', '2026-07-23', 'Aktif', 'GIA', '$2y$10$9YYI8EUWIf.2qI3n26E/geIzp1hFeLm605aDq82F341woPhcdt8Kq', 'Rp 948.508', 'Rp 384.508', 'member', NULL, 'lampiran_123456_1784798316.jpeg', '2026-07-23 09:18:36'),
('BKJL-019', 'Cargodore', '123456789', NULL, NULL, NULL, '2026-12-27', 'IKA (India Kilo Alpha)', 'SQ CARGODORE', 'riskaaulia3838@gmail.com', '082120025508', '2026-07-24', 'Aktif', 'IKA', '$2y$10$L8VJghrSELXOGDhqyZZJOOOLA/QojvvjAoq1LkA1Mx.bO9i1jhHnW', '', '', 'member', '', NULL, '2026-07-24 02:36:54'),
('BKJL-020', 'Cargodore', '123456789', NULL, NULL, NULL, NULL, 'RISKA AULIA', 'CHIEF HATCH BOSS', 'riskaaulia12rplb@gmail.com', '082100000000', '2026-09-01', 'Pending', 'RISKA', '$2y$10$X5KHYyUfPRwcjF9Kt7oi5.baZUQYLQJDStwbCIfx19dbzl98KUjlG', '', '', 'member', '', NULL, '2026-09-01 10:02:59');

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan_cuti`
--

CREATE TABLE `pengajuan_cuti` (
  `id` int(11) NOT NULL,
  `id_member` varchar(50) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `nip` varchar(50) NOT NULL,
  `jabatan` varchar(100) NOT NULL,
  `team` varchar(100) NOT NULL,
  `tgl_ambil` date NOT NULL,
  `tgl_masuk` date NOT NULL,
  `keperluan` text NOT NULL,
  `alamat` text NOT NULL,
  `tgl_ajuan` date NOT NULL DEFAULT curdate(),
  `status` enum('Menunggu','Disetujui','Ditolak') NOT NULL DEFAULT 'Menunggu',
  `diarsipkan` tinyint(1) NOT NULL DEFAULT 0,
  `diketahui_user` tinyint(1) NOT NULL DEFAULT 0,
  `dilihat_admin` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_slip_gaji`
--

CREATE TABLE `request_slip_gaji` (
  `id` int(11) NOT NULL,
  `id_member` varchar(50) NOT NULL COMMENT 'FK ke pendaftaran_member.id_member',
  `nama` varchar(100) NOT NULL,
  `jabatan` varchar(100) NOT NULL,
  `nip` varchar(50) NOT NULL,
  `periode` varchar(20) NOT NULL COMMENT 'Contoh: December 2026',
  `tujuan` varchar(255) NOT NULL,
  `catatan` text DEFAULT NULL,
  `tgl_ajuan` date NOT NULL DEFAULT current_timestamp(),
  `status` enum('Menunggu','Disetujui','Ditolak') NOT NULL DEFAULT 'Menunggu',
  `diarsipkan` tinyint(1) NOT NULL DEFAULT 0,
  `diketahui_user` tinyint(1) NOT NULL DEFAULT 0,
  `dilihat_admin` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_sistem`
--
ALTER TABLE `admin_sistem`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `hubungi_kami`
--
ALTER TABLE `hubungi_kami`
  ADD PRIMARY KEY (`id_pesan`);

--
-- Indexes for table `pendaftaran_member`
--
ALTER TABLE `pendaftaran_member`
  ADD PRIMARY KEY (`id_member`);

--
-- Indexes for table `pengajuan_cuti`
--
ALTER TABLE `pengajuan_cuti`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `request_slip_gaji`
--
ALTER TABLE `request_slip_gaji`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_id_member` (`id_member`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_sistem`
--
ALTER TABLE `admin_sistem`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hubungi_kami`
--
ALTER TABLE `hubungi_kami`
  MODIFY `id_pesan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `pengajuan_cuti`
--
ALTER TABLE `pengajuan_cuti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `request_slip_gaji`
--
ALTER TABLE `request_slip_gaji`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
