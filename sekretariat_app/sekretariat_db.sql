-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 12, 2026 at 03:43 PM
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
-- Database: `sekretariat_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `agenda`
--

CREATE TABLE `agenda` (
  `id` int(10) UNSIGNED NOT NULL,
  `judul` varchar(255) NOT NULL,
  `penanggung_jawab` varchar(150) NOT NULL,
  `lokasi` varchar(150) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `waktu_mulai` time NOT NULL,
  `waktu_selesai` time NOT NULL,
  `kategori` enum('direktur','perusahaan') NOT NULL DEFAULT 'perusahaan',
  `status` enum('belum','selesai') NOT NULL DEFAULT 'belum',
  `diarsipkan` tinyint(1) NOT NULL DEFAULT 0,
  `dibuat_oleh_user_id` int(11) DEFAULT NULL,
  `dibuat_oleh` varchar(100) DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp(),
  `diperbarui_pada` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `agenda`
--

INSERT INTO `agenda` (`id`, `judul`, `penanggung_jawab`, `lokasi`, `tanggal`, `waktu_mulai`, `waktu_selesai`, `kategori`, `status`, `diarsipkan`, `dibuat_oleh_user_id`, `dibuat_oleh`, `tanggal_selesai`, `dibuat_pada`, `diperbarui_pada`) VALUES
(7, 'vvc', 'hhhh', 'jhkj', '2026-07-27', '08:53:00', '09:53:00', 'direktur', 'selesai', 1, NULL, NULL, '2026-07-27', '2026-07-27 08:52:13', '2026-07-27 11:21:43'),
(8, 'ASDFGH', 'DFGH', 'HJJ', '2026-07-27', '17:00:00', '18:00:00', 'direktur', 'selesai', 1, NULL, NULL, '2026-07-27', '2026-07-27 09:28:14', '2026-07-27 11:25:56'),
(9, 'LJJAS', 'KFFKJAHJ', 'KSJDAHF', '2026-07-27', '12:00:00', '12:30:00', 'direktur', 'selesai', 1, NULL, NULL, '2026-07-27', '2026-07-27 09:28:44', '2026-07-27 11:13:22'),
(18, '123456', '123', 'KANTOR', '2026-08-05', '12:00:00', '15:00:00', 'direktur', 'selesai', 1, 2, 'DEA (riska aulia)', '2026-08-05', '2026-08-05 07:05:02', '2026-08-05 16:41:15'),
(19, 'MAKAN BERSAMA', 'Raya', 'PMK', '2026-09-10', '10:33:00', '10:57:00', 'direktur', 'selesai', 0, 2, 'DEA (riska aulia)', NULL, '2026-08-05 11:33:43', '2026-09-10 10:58:10'),
(20, 'tes', 'keizha', 'kantor', '2026-08-05', '16:00:00', '17:00:00', 'direktur', 'selesai', 0, 2, 'DEA', NULL, '2026-08-05 16:40:27', '2026-08-06 13:03:38'),
(21, 'CEK HALAMAN DASHBOARD', 'Riska', 'Kantor BKJL', '2026-09-10', '10:50:00', '11:00:00', 'perusahaan', 'selesai', 0, 2, 'DEA', NULL, '2026-08-06 10:00:30', '2026-09-10 11:01:19'),
(22, 'SJFAJ', 'keizha', 'kantor', '2026-08-05', '08:00:00', '09:00:00', 'direktur', 'selesai', 0, 2, 'DEA', NULL, '2026-08-07 09:01:19', '2026-08-11 13:44:50'),
(23, 'koordinasi pekerja', 'rehan', 'lapangan', '2026-09-14', '10:00:00', '11:00:00', 'perusahaan', 'selesai', 0, 2, 'DEA', NULL, '2026-09-09 09:45:53', '2026-09-17 12:30:58'),
(25, 'Makan diluar sma semua karyawan', 'Lusi', 'Bakso edo cigugur', '2026-09-11', '10:00:00', '11:00:00', 'perusahaan', 'selesai', 0, 2, 'DEA', NULL, '2026-09-10 10:29:18', '2026-09-17 12:30:58'),
(26, 'cek', 'roy', 'Kantor BKJL', '2026-09-10', '13:00:00', '15:00:00', 'direktur', 'selesai', 0, 2, 'DEA', NULL, '2026-09-10 10:45:37', '2026-09-10 17:45:50');

-- --------------------------------------------------------

--
-- Table structure for table `aset`
--

CREATE TABLE `aset` (
  `id` int(10) UNSIGNED NOT NULL,
  `kode_aset` varchar(50) NOT NULL COMMENT 'Contoh: AST-2024-001',
  `icon` varchar(10) NOT NULL DEFAULT '?',
  `nama_aset` varchar(150) NOT NULL,
  `kategori` varchar(100) NOT NULL DEFAULT 'Elektronik',
  `lokasi` varchar(150) NOT NULL,
  `status` enum('tersedia','dipinjam','rusak') NOT NULL DEFAULT 'tersedia',
  `kondisi` enum('baik','perlu','rusak') NOT NULL DEFAULT 'baik',
  `dibuat_oleh_user_id` int(11) DEFAULT NULL,
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp(),
  `diperbarui_pada` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `aset`
--

INSERT INTO `aset` (`id`, `kode_aset`, `icon`, `nama_aset`, `kategori`, `lokasi`, `status`, `kondisi`, `dibuat_oleh_user_id`, `dibuat_pada`, `diperbarui_pada`) VALUES
(1, '123', '🪑', 'Meja Lipat IT', 'Perabot', 'kantoR', 'rusak', 'rusak', 2, '2026-08-10 11:55:55', '2026-08-11 07:05:05'),
(2, '091', '🪑', 'Meja Lipat IT', 'Perabot', 'Kantor', 'tersedia', 'baik', 2, '2026-08-10 11:57:16', '2026-08-10 11:57:16'),
(3, '098', '💻', '💻                  Laptop acer pink', 'Elektronik', 'Kantor - Ruang hrd', 'tersedia', 'perlu', 2, '2026-08-10 12:03:26', '2026-09-15 17:53:32'),
(4, '876', '📦', 'Meja Lipat HRD', 'Perabot', 'Kantor', 'tersedia', 'baik', 2, '2026-08-10 12:06:22', '2026-08-10 12:06:22');

-- --------------------------------------------------------

--
-- Table structure for table `atk_barang`
--

CREATE TABLE `atk_barang` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama_barang` varchar(150) NOT NULL,
  `kode_barang` varchar(50) NOT NULL,
  `dibuat_oleh_user_id` int(11) DEFAULT NULL,
  `lokasi_penyimpanan` varchar(150) DEFAULT NULL,
  `jumlah` int(11) DEFAULT NULL,
  `satuan` varchar(20) NOT NULL DEFAULT 'pcs',
  `deskripsi` text DEFAULT NULL,
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp(),
  `diperbarui_pada` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `atk_barang`
--

INSERT INTO `atk_barang` (`id`, `nama_barang`, `kode_barang`, `dibuat_oleh_user_id`, `lokasi_penyimpanan`, `jumlah`, `satuan`, `deskripsi`, `dibuat_pada`, `diperbarui_pada`) VALUES
(8, 'Kertas HVS', '112.334', 2, 'rak', 21, 'lembar', '', '2026-08-05 16:58:05', '2026-08-05 16:58:05'),
(9, 'Sepidol merah', '0987', 2, 'Rak sekretariat', NULL, '', 'suka bocor tintanya', '2026-08-11 15:20:18', '2026-08-11 15:25:33'),
(10, 'pulpen cair merah', '12390', 2, 'Rak sekretariat', 30, 'pcs', 'baru beli kemarin', '2026-09-16 07:46:12', '2026-09-16 07:54:20'),
(11, 'cek lokasi', '123', 2, '', NULL, '', '', '2026-09-16 08:07:52', '2026-09-16 08:07:52');

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nama_user` varchar(100) NOT NULL DEFAULT 'Tidak dikenal',
  `modul` varchar(50) NOT NULL,
  `aksi` varchar(20) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` enum('berhasil','gagal') NOT NULL DEFAULT 'berhasil',
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `nama_user`, `modul`, `aksi`, `judul`, `keterangan`, `ip_address`, `status`, `dibuat_pada`) VALUES
(235, 2, 'DEA', 'ATK', 'create', 'Menambahkan barang ATK baru', 'Nama: Sepidol merah - Kode: 0987', NULL, 'berhasil', '2026-08-11 15:20:18'),
(236, 2, 'DEA', 'ATK', 'update', 'Memperbarui barang ATK', 'Nama: Sepidol merah · Kode: 0987 · Lokasi: Rak sekretariat · Jumlah: -', NULL, 'berhasil', '2026-08-11 15:25:33'),
(237, 2, 'DEA', 'Autentikasi', 'logout', 'Logout dari sistem', '', NULL, 'berhasil', '2026-08-11 15:46:22'),
(238, 1, 'LOVI', 'Autentikasi', 'login', 'Percobaan login gagal', 'Username: LOVI', NULL, 'gagal', '2026-08-11 15:46:31'),
(239, 1, 'LOVI', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-08-11 15:46:34'),
(240, 1, 'LOVI', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-08-16 18:47:08'),
(241, 1, 'LOVI', 'Autentikasi', 'logout', 'Logout dari sistem', '', NULL, 'berhasil', '2026-08-16 18:48:24'),
(242, 1, 'LOVI', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-08-16 18:48:46'),
(243, 1, 'LOVI', 'Surat Masuk', 'create', 'Menambahkan surat masuk baru', 'No. Surat: COBA/01/08/2026 · Dari: Riska · undangan', NULL, 'berhasil', '2026-08-16 18:50:18'),
(244, 2, 'DEA', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-08-20 11:57:17'),
(245, 2, 'DEA', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-08-30 19:23:08'),
(246, 2, 'DEA', 'Autentikasi', 'logout', 'Logout dari sistem', '', NULL, 'berhasil', '2026-08-30 19:33:13'),
(247, 2, 'DEA', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-08-30 19:34:20'),
(248, 2, 'DEA', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-09-04 19:05:08'),
(249, 2, 'DEA', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-09-08 06:48:23'),
(250, 2, 'DEA', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-09-09 07:12:54'),
(251, 2, 'DEA', 'Surat Masuk', 'create', 'Menambahkan surat masuk baru', 'No. Surat: cek · Dari: ris · ngecek', NULL, 'berhasil', '2026-09-09 07:17:26'),
(252, 2, 'DEA', 'Surat Masuk', 'update', 'Menandai surat masuk selesai', 'No. Surat: COBA/01/08/2026 · Dari: Riska', NULL, 'berhasil', '2026-09-09 07:26:14'),
(253, 2, 'DEA', 'Surat Masuk', 'update', 'Mengarsipkan surat masuk', 'No. Surat: COBA/01/08/2026 · Dari: Riska', NULL, 'berhasil', '2026-09-09 07:26:17'),
(254, 2, 'DEA', 'Buku Tamu', 'update', 'Memperbarui data tamu', 'Instansi: PENUIN INDEX 4', NULL, 'berhasil', '2026-09-09 09:46:54'),
(255, 2, 'DEA', 'Buku Tamu', 'update', 'Memperbarui data tamu', 'Instansi: TAMU DARI JKT', NULL, 'berhasil', '2026-09-09 09:46:54'),
(256, 2, 'DEA', 'Buku Tamu', 'update', 'Memperbarui data tamu', 'Instansi: PT. Software Enginering', NULL, 'berhasil', '2026-09-09 09:46:54'),
(257, 2, 'DEA', 'Buku Tamu', 'create', 'Menambahkan data tamu', 'Instansi: smp 2', NULL, 'berhasil', '2026-09-09 09:48:00'),
(258, 2, 'DEA', 'Buku Tamu', 'create', 'Menambahkan data tamu', 'Instansi: ss', NULL, 'berhasil', '2026-09-09 09:48:41'),
(259, 2, 'DEA', 'Surat Keluar', 'create', 'Menambahkan surat keluar baru', 'No. Surat: 001/22 · Tujuan: smkn 2 · Undanga spesial', NULL, 'berhasil', '2026-09-09 11:07:35'),
(260, 2, 'DEA', 'Surat Keluar', 'create', 'Menambahkan surat keluar baru', 'No. Surat: 001/2254 · Tujuan: smkn 2 · Undanga spesial', NULL, 'berhasil', '2026-09-09 11:07:44'),
(261, 2, 'DEA', 'Surat Keluar', 'create', 'Menambahkan surat keluar baru', 'No. Surat: 002/543 · Tujuan: smkn 2 · Undanga spesial', NULL, 'berhasil', '2026-09-09 11:07:56'),
(262, 2, 'DEA', 'Surat Keluar', 'create', 'Menambahkan surat keluar baru', 'No. Surat: 7865/7 · Tujuan: smkn 2 · Undanga spesial', NULL, 'berhasil', '2026-09-09 11:08:13'),
(263, 2, 'DEA', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-09-10 06:58:37'),
(264, 2, 'DEA', 'Surat Keluar', 'create', 'Menambahkan surat keluar baru', 'No. Surat: cek · Tujuan: risri · cek', NULL, 'berhasil', '2026-09-10 07:01:20'),
(265, 2, 'DEA', 'Surat Keluar', 'create', 'Menambahkan surat keluar baru', 'No. Surat: cekcecek · Tujuan: risri · cek', NULL, 'berhasil', '2026-09-10 07:01:28'),
(266, 2, 'DEA', 'Surat Keluar', 'create', 'Menambahkan surat keluar baru', 'No. Surat: 880/CEK0223/2026 · Tujuan: umkm · coba boba', NULL, 'berhasil', '2026-09-10 07:03:29'),
(267, 2, 'DEA', 'Surat Keluar', 'create', 'Menambahkan surat keluar baru', 'No. Surat: 000/CEK0223/2027 · Tujuan: umkm · coba boba', NULL, 'berhasil', '2026-09-10 07:03:40'),
(268, 2, 'DEA', 'Surat Keluar', 'delete', 'Menghapus surat keluar', 'No. Surat: 000/CEK0223/2027 · Tujuan: umkm', NULL, 'berhasil', '2026-09-10 07:03:58'),
(269, 2, 'DEA', 'Surat Keluar', 'update', 'Memperbarui surat keluar', 'No. Surat: 001/22 · Tujuan: smkn 2 · Undanga spesial', NULL, 'berhasil', '2026-09-10 07:23:25'),
(270, 2, 'DEA', 'Surat Keluar', 'update', 'Memperbarui surat keluar', 'No. Surat: 001/2254 · Tujuan: smkn 2 · Undanga spesial', NULL, 'berhasil', '2026-09-10 07:23:34'),
(271, 2, 'DEA', 'Surat Keluar', 'update', 'Memperbarui surat keluar', 'No. Surat: 002/543 · Tujuan: smkn 2 · Undanga spesial', NULL, 'berhasil', '2026-09-10 07:23:41'),
(272, 2, 'DEA', 'Surat Keluar', 'update', 'Memperbarui surat keluar', 'No. Surat: 7865/7 · Tujuan: smkn 2 · Undanga spesial', NULL, 'berhasil', '2026-09-10 07:23:52'),
(273, 2, 'DEA', 'Surat Keluar', 'update', 'Menyetujui surat keluar', 'No. Surat: cekcecek · Tujuan: risri', NULL, 'berhasil', '2026-09-10 07:23:55'),
(274, 2, 'DEA', 'Surat Keluar', 'update', 'Mengirim surat keluar', 'No. Surat: cekcecek · Tujuan: risri · Metode: Email', NULL, 'berhasil', '2026-09-10 07:40:43'),
(275, 2, 'DEA', 'Surat Keluar', 'update', 'Memperbarui surat keluar', 'No. Surat: 880/CEK01/2026 · Tujuan: Karyawan baru di lapangan · Pemberitahuan masuk kerja', NULL, 'berhasil', '2026-09-10 07:43:06'),
(276, 2, 'DEA', 'Surat Keluar', 'update', 'Mengirim surat keluar', 'No. Surat: 880/CEK01/2026 · Tujuan: Karyawan baru di lapangan · Metode: Email', NULL, 'berhasil', '2026-09-10 07:43:44'),
(277, 2, 'DEA', 'Surat Keluar', 'update', 'Menyetujui surat keluar', 'No. Surat: 880/CEK01/2026 · Tujuan: Karyawan baru di lapangan', NULL, 'berhasil', '2026-09-10 07:46:41'),
(278, 2, 'DEA', 'Surat Keluar', 'update', 'Memperbarui surat keluar', 'No. Surat: 880/CEK01/2026 · Tujuan: Karyawan baru di lapangan · Pemberitahuan masuk kerja', NULL, 'berhasil', '2026-09-10 07:47:50'),
(279, 2, 'DEA', 'Surat Keluar', 'update', 'Mengirim surat keluar', 'No. Surat: 880/CEK01/2026 · Tujuan: Karyawan baru di lapangan · Metode: Email', NULL, 'berhasil', '2026-09-10 07:50:28'),
(280, 2, 'DEA', 'Surat Keluar', 'update', 'Menyetujui surat keluar', 'No. Surat: 880/CEK01/2026 · Tujuan: Karyawan baru di lapangan', NULL, 'berhasil', '2026-09-10 07:51:38'),
(281, 2, 'DEA', 'Surat Keluar', 'update', 'Memperbarui surat keluar', 'No. Surat: 880/CEK01/2026 · Tujuan: Karyawan baru di lapangan · Pemberitahuan masuk kerja', NULL, 'berhasil', '2026-09-10 08:00:19'),
(282, 2, 'DEA', 'Surat Keluar', 'update', 'Memperbarui surat keluar', 'No. Surat: 001/22 · Tujuan: X poes · Undanga spesial', NULL, 'berhasil', '2026-09-10 08:06:07'),
(283, 2, 'DEA', 'Surat Keluar', 'update', 'Mengirim surat keluar', 'No. Surat: 001/22 · Tujuan: X poes · Metode: WhatsApp', NULL, 'berhasil', '2026-09-10 08:16:09'),
(284, 2, 'DEA', 'Surat Keluar', 'update', 'Memperbarui surat keluar', 'No. Surat: 001/22 · Tujuan: X poes · Undanga spesial', NULL, 'berhasil', '2026-09-10 08:16:32'),
(285, 2, 'DEA', 'Surat Keluar', 'update', 'Mengirim surat keluar', 'No. Surat: 001/22 · Tujuan: X poes · Metode: WhatsApp', NULL, 'berhasil', '2026-09-10 08:16:37'),
(286, 2, 'DEA', 'Surat Keluar', 'update', 'Menyetujui surat keluar', 'No. Surat: 001/2254 · Tujuan: smkn 2', NULL, 'berhasil', '2026-09-10 08:22:24'),
(287, 2, 'DEA', 'Surat Keluar', 'update', 'Mengarsipkan surat keluar', 'No. Surat: 880/CEK01/2026 · Tujuan: Karyawan baru di lapangan', NULL, 'berhasil', '2026-09-10 08:42:05'),
(288, 2, 'DEA', 'Surat Keluar', 'update', 'Mengarsipkan surat keluar', 'No. Surat: 001/22 · Tujuan: X poes', NULL, 'berhasil', '2026-09-10 08:42:07'),
(289, 2, 'DEA', 'Surat Keluar', 'update', 'Mengarsipkan surat keluar', 'No. Surat: cekcecek · Tujuan: risri', NULL, 'berhasil', '2026-09-10 08:42:49'),
(290, 2, 'DEA', 'Surat Masuk', 'update', 'Menandai surat masuk selesai', 'No. Surat: tes garfik/05/08/2026 · Dari: Pak Sandi', NULL, 'berhasil', '2026-09-10 08:56:52'),
(291, 2, 'DEA', 'Surat Masuk', 'update', 'Mengarsipkan surat masuk', 'No. Surat: tes garfik/05/08/2026 · Dari: Pak Sandi', NULL, 'berhasil', '2026-09-10 08:56:54'),
(292, 2, 'DEA', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-09-10 12:33:19'),
(293, 2, 'DEA', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-09-10 17:43:21'),
(294, 2, 'DEA', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-09-11 10:50:25'),
(295, 2, 'DEA', 'Buku Tamu', 'update', 'Memperbarui data tamu', 'Instansi: smp 2', NULL, 'berhasil', '2026-09-11 10:51:11'),
(296, 2, 'DEA', 'Buku Tamu', 'update', 'Memperbarui data tamu', 'Instansi: smp 2', NULL, 'berhasil', '2026-09-11 11:38:21'),
(297, 2, 'DEA', 'Buku Tamu', 'update', 'Memperbarui data tamu', 'Instansi: PENUIN INDEX 4', NULL, 'berhasil', '2026-09-11 11:38:29'),
(298, 2, 'DEA', 'Buku Tamu', 'update', 'Memperbarui data tamu', 'Instansi: smp 2', NULL, 'berhasil', '2026-09-11 11:38:41'),
(299, 2, 'DEA', 'Buku Tamu', 'update', 'Memperbarui data tamu', 'Instansi: smp 2', NULL, 'berhasil', '2026-09-11 11:50:21'),
(300, 2, 'DEA', 'Buku Tamu', 'update', 'Memperbarui data tamu', 'Instansi: smp 2', NULL, 'berhasil', '2026-09-11 11:50:41'),
(301, 2, 'DEA', 'Buku Tamu', 'delete', 'Membersihkan semua data buku tamu aktif', '', NULL, 'berhasil', '2026-09-11 12:16:05'),
(302, 2, 'DEA', 'Buku Tamu', 'create', 'Menambahkan data tamu', 'Instansi: X POES', NULL, 'berhasil', '2026-09-11 12:20:51'),
(303, 2, 'DEA', 'Buku Tamu', 'update', 'Memperbarui data tamu', 'Instansi: X POES', NULL, 'berhasil', '2026-09-11 12:22:42'),
(304, 1, 'LOVI', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-09-14 08:32:15'),
(305, 1, 'LOVI', 'Perjanjian Kerjasama', 'create', 'Menambahkan dokumen perjanjian kerjasama', 'Nomor: PKS-0001/14/September/2026 - cek cek', NULL, 'berhasil', '2026-09-14 10:08:59'),
(306, 1, 'LOVI', 'Perjanjian Kerjasama', 'create', 'Menambahkan dokumen perjanjian kerjasama', 'Nomor: PKS-0002/14/September/2026 - 123', NULL, 'berhasil', '2026-09-14 10:11:43'),
(307, 1, 'LOVI', 'Perjanjian Kerjasama', 'create', 'Menambahkan dokumen perjanjian kerjasama', 'Nomor: PKS-0003/14/September/2026 - dokumen perjanjian ke 10', NULL, 'berhasil', '2026-09-14 10:25:38'),
(308, 2, 'DEA', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-09-15 07:41:25'),
(309, 2, 'DEA', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-09-15 07:41:25'),
(310, 2, 'DEA', 'Dokumen', 'create', 'Mengunggah dokumen baru', 'Nama: himpunan.jpeg - Kategori: Presentasi', NULL, 'berhasil', '2026-09-15 07:48:07'),
(311, 2, 'DEA', 'Dokumen', 'create', 'Mengunggah dokumen baru', 'Nama: multivariabel 2.jpeg - Kategori: Laporan', NULL, 'berhasil', '2026-09-15 07:48:26'),
(312, 2, 'DEA', 'Dokumen', 'create', 'Mengunggah dokumen baru', 'Nama: deret tak terhingga.jpeg - Kategori: Notulen', NULL, 'berhasil', '2026-09-15 07:48:41'),
(313, 2, 'DEA', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-09-15 16:50:19'),
(314, 2, 'DEA', 'Peminjaman Aset', 'update', 'Menyetujui peminjaman aset', 'KANIA - MOUSE KOMPUTER (2 unit)', NULL, 'berhasil', '2026-09-15 17:20:12'),
(315, 2, 'DEA', 'Peminjaman Aset', 'create', 'Mengajukan peminjaman aset', 'RISKA AULIA - Laptop php silver (1 unit)', NULL, 'berhasil', '2026-09-15 17:32:41'),
(316, 2, 'DEA', 'Peminjaman Aset', 'create', 'Mengajukan peminjaman aset', 'Ika - Laptop acer old (2 unit)', NULL, 'berhasil', '2026-09-15 17:33:45'),
(317, 2, 'DEA', 'Peminjaman Aset', 'update', 'Menolak peminjaman aset', 'RISKA AULIA - Laptop php silver (1 unit)', NULL, 'berhasil', '2026-09-15 17:36:08'),
(318, 2, 'DEA', 'Inventaris & Aset', 'update', 'Memperbarui data aset', 'Kode: 098 - 💻                  Laptop acer pink', NULL, 'berhasil', '2026-09-15 17:53:32'),
(319, 2, 'DEA', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-09-16 07:43:49'),
(320, 2, 'DEA', 'ATK', 'create', 'Menambahkan barang ATK baru', 'Nama: pulpen cair merah - Kode: 12390', NULL, 'berhasil', '2026-09-16 07:46:12'),
(321, 2, 'DEA', 'ATK', 'update', 'Memperbarui barang ATK', 'Nama: pulpen cair merah · Kode: 12390 · Lokasi: Rak sekretariat · Jumlah: 30 pcs', NULL, 'berhasil', '2026-09-16 07:54:20'),
(322, 2, 'DEA', 'ATK', 'create', 'Menambahkan barang ATK baru', 'Nama: cek lokasi - Kode: 123', NULL, 'berhasil', '2026-09-16 08:07:52'),
(323, 2, 'DEA', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-09-16 12:25:13'),
(324, 2, 'DEA', 'Autentikasi', 'logout', 'Logout dari sistem', '', NULL, 'berhasil', '2026-09-16 12:27:15'),
(325, 1, 'LOVI', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-09-16 12:27:25'),
(326, 2, 'DEA', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-09-16 13:42:13'),
(327, 2, 'DEA', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-09-17 06:52:53'),
(328, 2, 'DEA', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-09-17 06:52:54'),
(329, 2, 'DEA', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-09-17 10:52:39'),
(330, 2, 'DEA', 'Autentikasi', 'logout', 'Logout dari sistem', '', NULL, 'berhasil', '2026-09-17 12:07:20'),
(331, 1, 'LOVI', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-09-17 12:08:37'),
(332, 1, 'LOVI', 'Autentikasi', 'logout', 'Logout dari sistem', '', NULL, 'berhasil', '2026-09-17 12:08:53'),
(333, 1, 'LOVI', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-09-17 12:10:33'),
(334, 1, 'LOVI', 'Autentikasi', 'logout', 'Logout dari sistem', '', NULL, 'berhasil', '2026-09-17 12:10:46'),
(335, NULL, 'HERMAN', 'Autentikasi', 'login', 'Percobaan login gagal', 'Username: HERMAN', NULL, 'gagal', '2026-09-17 12:11:14'),
(336, NULL, 'HERMAN', 'Autentikasi', 'login', 'Percobaan login gagal', 'Username: HERMAN', NULL, 'gagal', '2026-09-17 12:11:22'),
(337, 3, 'HERMAN', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-09-17 12:11:39'),
(338, 3, 'HERMAN', 'Autentikasi', 'logout', 'Logout dari sistem', '', NULL, 'berhasil', '2026-09-17 12:14:04'),
(339, 3, 'HERMAN', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-09-17 12:14:17'),
(340, 3, 'HERMAN', 'Surat Keluar', 'update', 'Menyetujui surat keluar', 'No. Surat: cek · Tujuan: risri', NULL, 'berhasil', '2026-09-17 12:41:42'),
(341, 3, 'HERMAN', 'Buku Tamu', 'update', 'Memperbarui data tamu', 'Instansi: X POES', NULL, 'berhasil', '2026-09-17 12:55:34'),
(342, 3, 'HERMAN', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-09-18 16:55:49'),
(343, 3, 'HERMAN', 'Autentikasi', 'login', 'Login ke sistem', '', NULL, 'berhasil', '2026-09-19 10:26:51');

-- --------------------------------------------------------

--
-- Table structure for table `audit_log_sampah`
--

CREATE TABLE `audit_log_sampah` (
  `id` int(11) NOT NULL,
  `id_asli` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nama_user` varchar(150) NOT NULL DEFAULT '',
  `aksi` varchar(30) NOT NULL DEFAULT 'lainnya',
  `judul` varchar(255) NOT NULL DEFAULT '',
  `modul` varchar(100) NOT NULL DEFAULT '',
  `status` varchar(20) NOT NULL DEFAULT 'berhasil',
  `keterangan` text DEFAULT NULL,
  `dibuat_pada` datetime NOT NULL,
  `dipindahkan_pada` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log_sampah`
--

INSERT INTO `audit_log_sampah` (`id`, `id_asli`, `user_id`, `nama_user`, `aksi`, `judul`, `modul`, `status`, `keterangan`, `dibuat_pada`, `dipindahkan_pada`) VALUES
(1, 208, 2, 'DEA', 'update', 'Memperbarui rapat', 'Rapat', 'berhasil', 'ABC - ID #13', '2026-08-06 09:13:46', '2026-08-07 09:01:46'),
(2, 209, 0, 'update', 'Mengarsipkan rapat', 'ID #13', '', 'berhasil', '', '2026-08-06 09:58:23', '2026-08-07 09:01:46'),
(3, 210, 0, 'create', 'Menambahkan agenda b', 'CEK HALAMAN DASHBOARD - 2026-08-14', '', 'berhasil', '', '2026-08-06 10:00:30', '2026-08-07 09:01:46'),
(4, 211, 0, 'create', 'Menambahkan tamu bar', 'Instansi: TAMU DARI JKT - PJ: Riska', '', 'berhasil', '', '2026-08-06 10:03:14', '2026-08-07 09:01:46'),
(5, 212, 0, 'create', 'Menambahkan tamu bar', 'Instansi: PT. Software Enginering - PJ: Riska', '', 'berhasil', '', '2026-08-06 10:07:41', '2026-08-07 09:01:46'),
(6, 213, 0, 'update', 'Memperbarui data tam', 'ID #17 - Instansi: TAMU DARI JKT', '', 'berhasil', '', '2026-08-06 10:09:53', '2026-08-07 09:01:46'),
(7, 214, 0, 'update', 'Memperbarui data tam', 'ID #18 - Instansi: PT. Software Enginering', '', 'berhasil', '', '2026-08-06 10:10:46', '2026-08-07 09:01:46'),
(8, 215, 0, 'create', 'Menambahkan rapat ba', 'PENUIN INDEX 1 - PIC: R', '', 'berhasil', '', '2026-08-06 10:25:54', '2026-08-07 09:01:46'),
(9, 216, 0, 'create', 'Menambahkan rapat ba', 'PENUIN INDEX 2 - PIC: I', '', 'berhasil', '', '2026-08-06 10:26:22', '2026-08-07 09:01:46'),
(10, 217, 0, 'create', 'Menambahkan tamu bar', 'Instansi: PENUIN INDEX 4 - PJ: Riska', '', 'berhasil', '', '2026-08-06 10:27:14', '2026-08-07 09:01:46'),
(11, 218, 0, 'update', 'Memperbarui agenda', 'tes - ID #20', '', 'berhasil', '', '2026-08-06 13:03:38', '2026-08-07 09:01:46'),
(12, 219, 0, 'update', 'Menandai agenda sele', 'ID #20', '', 'berhasil', '', '2026-08-06 13:03:38', '2026-08-07 09:01:46'),
(13, 220, 0, 'create', 'Menambahkan rapat ba', 'PERCOBAAN - PIC: RISKA KEYZA', '', 'berhasil', '', '2026-08-06 15:40:59', '2026-08-07 09:01:46'),
(14, 221, 0, 'create', 'Menambahkan tamu bar', 'Instansi: pt ayam - PJ: keyza', '', 'berhasil', '', '2026-08-06 15:53:02', '2026-08-07 09:01:46'),
(15, 222, 0, 'update', 'Memperbarui data tam', 'ID #20 - Instansi: pt ayam', '', 'berhasil', '', '2026-08-06 15:54:12', '2026-08-07 09:01:46'),
(16, 223, 0, 'update', 'Memperbarui data tam', 'ID #20 - Instansi: pt ayam', '', 'berhasil', '', '2026-08-07 07:57:24', '2026-08-07 09:01:46'),
(17, 224, 2, 'DEA', 'create', 'Menambahkan agenda baru', 'Agenda', 'berhasil', 'SJFAJ - 2026-08-05', '2026-08-07 09:01:19', '2026-08-07 09:01:46'),
(18, 225, 2, 'DEA', 'update', 'Menandai agenda selesai', 'Agenda', 'berhasil', 'ID #224', '2026-08-07 09:01:19', '2026-08-07 09:01:46'),
(19, 226, 2, 'DEA', 'login', 'Login ke sistem', 'Autentikasi', 'berhasil', '', '2026-08-10 10:39:02', '2026-08-11 15:19:22'),
(20, 227, 2, 'DEA', 'login', 'Login ke sistem', 'Autentikasi', 'berhasil', '', '2026-08-11 06:52:34', '2026-08-11 15:19:22'),
(21, 228, 2, 'DEA', 'update', 'Mengarsipkan rapat', 'Rapat', 'berhasil', 'ID #15', '2026-08-11 07:10:23', '2026-08-11 15:19:22'),
(22, 229, 2, 'DEA', 'logout', 'Logout dari sistem', 'Autentikasi', 'berhasil', '', '2026-08-11 11:25:10', '2026-08-11 15:19:22'),
(23, 230, 2, 'DEA', 'login', 'Login ke sistem', 'Autentikasi', 'berhasil', '', '2026-08-11 11:25:47', '2026-08-11 15:19:22'),
(24, 231, 2, 'DEA', 'delete', 'Menghapus pengajuan peminjaman', 'Peminjaman Aset', 'berhasil', 'AJI - MEJA LIPAT (20 unit)', '2026-08-11 13:21:30', '2026-08-11 15:19:22'),
(25, 232, 2, 'DEA', 'update', 'Menandai agenda selesai', 'Agenda', 'berhasil', 'ID #19', '2026-08-11 13:44:50', '2026-08-11 15:19:22'),
(26, 233, 2, 'DEA', 'update', 'Menandai agenda selesai', 'Agenda', 'berhasil', 'ID #22', '2026-08-11 13:44:50', '2026-08-11 15:19:22'),
(27, 234, 2, 'DEA', 'login', 'Login ke sistem', 'Autentikasi', 'berhasil', '', '2026-08-11 15:07:57', '2026-08-11 15:19:22');

-- --------------------------------------------------------

--
-- Table structure for table `catatan`
--

CREATE TABLE `catatan` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `isi` text NOT NULL,
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp(),
  `diperbarui_pada` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `catatan_reminder`
--

CREATE TABLE `catatan_reminder` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `isi` varchar(500) NOT NULL,
  `selesai` tinyint(1) NOT NULL DEFAULT 0,
  `urutan` int(11) NOT NULL DEFAULT 0,
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp(),
  `diupdate_pada` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `catatan_reminder`
--

INSERT INTO `catatan_reminder` (`id`, `id_user`, `isi`, `selesai`, `urutan`, `dibuat_pada`, `diupdate_pada`) VALUES
(3, NULL, 'Bawa buku saat rapat MAKAN BERSAMA', 1, 1, '2026-09-10 10:55:20', '2026-09-10 10:55:44'),
(4, NULL, 'Bawa stample untuk rapat b', 0, 2, '2026-09-10 10:55:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `catatan_reminder_perjanjian`
--

CREATE TABLE `catatan_reminder_perjanjian` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama_dokumen` varchar(255) NOT NULL,
  `kategori` enum('Nota Kesepahaman','Kontrak Kerja Sama','Perjanjian Sewa/Aset','Perpanjangan Kerjasama') NOT NULL,
  `mitra_kerjasama` varchar(255) NOT NULL,
  `masa_berlaku` varchar(150) DEFAULT NULL,
  `diunggah_oleh` varchar(150) DEFAULT NULL,
  `ukuran` varchar(50) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `ditandai_selesai` tinyint(1) NOT NULL DEFAULT 0,
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp(),
  `diperbarui_pada` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `catatan_reminder_perjanjian`
--

INSERT INTO `catatan_reminder_perjanjian` (`id`, `nama_dokumen`, `kategori`, `mitra_kerjasama`, `masa_berlaku`, `diunggah_oleh`, `ukuran`, `file_path`, `ditandai_selesai`, `dibuat_pada`, `diperbarui_pada`) VALUES
(7, 'cek 1', 'Kontrak Kerja Sama', 'cek', '06 Agu 2026 s/d 21 Agu 2026', 'DEA', '29 KB', 'uploads/catatan_perjanjian/catpk_6a730790c142f_logo_bkjl.jpeg', 0, '2026-08-05 16:51:12', '2026-08-05 16:51:12');

-- --------------------------------------------------------

--
-- Table structure for table `catatan_reminder_tamu`
--

CREATE TABLE `catatan_reminder_tamu` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `isi` varchar(500) NOT NULL,
  `selesai` tinyint(1) NOT NULL DEFAULT 0,
  `urutan` int(11) NOT NULL DEFAULT 0,
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp(),
  `diupdate_pada` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dokumen`
--

CREATE TABLE `dokumen` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama_dokumen` varchar(255) NOT NULL,
  `kategori_id` int(10) UNSIGNED NOT NULL,
  `jenis` varchar(10) NOT NULL COMMENT 'PDF, DOCX, XLSX, PPTX, ZIP, dll (dipakai untuk icon/badge)',
  `nama_file` varchar(255) NOT NULL COMMENT 'nama file asli saat diupload',
  `path_file` varchar(500) NOT NULL COMMENT 'lokasi/path file di server, mis. uploads/dokumen/xxx.pdf',
  `ukuran_bytes` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'ukuran file dalam bytes, ditampilkan sbg KB/MB di UI',
  `diunggah_oleh_nama` varchar(100) NOT NULL COMMENT 'nama pengunggah, mis. Siti Nurhaliza',
  `diunggah_oleh_jabatan` varchar(50) DEFAULT 'Sekretariat' COMMENT 'ditampilkan di bawah nama, mis. Sekretariat',
  `diunggah_oleh_user_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'opsional: relasi ke tabel users jika login sudah terpasang',
  `diarsipkan` tinyint(1) NOT NULL DEFAULT 0,
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp(),
  `diperbarui_pada` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dokumen`
--

INSERT INTO `dokumen` (`id`, `nama_dokumen`, `kategori_id`, `jenis`, `nama_file`, `path_file`, `ukuran_bytes`, `diunggah_oleh_nama`, `diunggah_oleh_jabatan`, `diunggah_oleh_user_id`, `diarsipkan`, `dibuat_pada`, `diperbarui_pada`) VALUES
(20, 'himpunan.jpeg', 5, 'JPEG', 'himpunan.jpeg', 'uploads/dokumen/himpunan_20260915024807_492480.jpeg', 55252, 'DEA', 'Sekretariat', NULL, 0, '2026-09-15 02:48:07', '2026-09-15 07:48:07'),
(21, 'multivariabel 2.jpeg', 2, 'JPEG', 'multivariabel 2.jpeg', 'uploads/dokumen/multivariabel_2_20260915024826_46146f.jpeg', 63552, 'DEA', 'Sekretariat', NULL, 0, '2026-09-15 02:48:26', '2026-09-15 07:48:26'),
(22, 'deret tak terhingga.jpeg', 4, 'JPEG', 'deret tak terhingga.jpeg', 'uploads/dokumen/deret_tak_terhingga_20260915024841_f08ca8.jpeg', 68474, 'DEA', 'Sekretariat', NULL, 0, '2026-09-15 02:48:41', '2026-09-15 07:48:41');

-- --------------------------------------------------------

--
-- Table structure for table `dokumen_log`
--

CREATE TABLE `dokumen_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `aksi` varchar(20) NOT NULL,
  `dokumen_id` int(10) UNSIGNED DEFAULT NULL,
  `nama_dokumen` varchar(255) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `pelaku_nama` varchar(100) NOT NULL DEFAULT 'Sekretariat Admin',
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dokumen_log`
--

INSERT INTO `dokumen_log` (`id`, `aksi`, `dokumen_id`, `nama_dokumen`, `keterangan`, `pelaku_nama`, `dibuat_pada`) VALUES
(1, 'unggah', 8, 'logo rpl.jpeg', NULL, 'percobaan', '2026-07-30 10:27:20'),
(2, 'unggah', 9, 'banner.jpeg', NULL, 'LOVI', '2026-08-01 10:21:26'),
(3, 'hapus', 8, 'logo rpl.jpeg', NULL, 'Sekretariat Admin', '2026-08-01 10:21:44'),
(4, 'tag_tambah', 9, 'banner.jpeg', 'PENTING UNTUK BULAN DEPAN', 'Sekretariat Admin', '2026-08-01 10:21:59'),
(5, 'hapus', 5, 'th.png', NULL, 'Sekretariat Admin', '2026-08-01 10:24:29'),
(6, 'hapus', 6, 'Arsip_Surat_Masuk_PERCOBAN_3_20260729.xlsx', NULL, 'Sekretariat Admin', '2026-08-01 10:24:29'),
(7, 'tag_tambah', 4, 'Arsip_Surat_Masuk_PERCOBAN_1_20260729.doc', 'CADANGAN DATA', 'Sekretariat Admin', '2026-08-03 08:36:46'),
(8, 'unggah', 10, 'logo bkjl.jpeg', NULL, 'DEA (riska aulia)', '2026-08-03 08:37:28'),
(9, 'unggah', 11, 'smk.jpeg', NULL, 'DEA (riska aulia)', '2026-08-03 08:37:59'),
(10, 'unggah', 12, 'Arsip_BARU_TES_123_20260729.pdf', NULL, 'DEA (riska aulia)', '2026-08-03 10:23:38'),
(11, 'hapus', 4, 'Arsip_Surat_Masuk_PERCOBAN_1_20260729.doc', NULL, 'Sekretariat Admin', '2026-08-03 10:57:45'),
(12, 'hapus', 7, 'Arsip_Surat_Masuk_PERCOBAN_1_20260729.doc', NULL, 'Sekretariat Admin', '2026-08-03 10:57:49'),
(13, 'hapus', 9, 'banner.jpeg', NULL, 'Sekretariat Admin', '2026-08-03 10:58:24'),
(14, 'hapus', 10, 'logo bkjl.jpeg', NULL, 'Sekretariat Admin', '2026-08-04 09:14:13'),
(15, 'hapus', 11, 'smk.jpeg', NULL, 'Sekretariat Admin', '2026-08-04 09:14:13'),
(16, 'tag_tambah', 12, 'Arsip_BARU_TES_123_20260729.pdf', 'cek log activity', 'DEA (riska aulia)', '2026-08-04 09:22:49'),
(17, 'hapus', 12, 'Arsip_BARU_TES_123_20260729.pdf', NULL, 'DEA (riska aulia)', '2026-08-04 09:22:57'),
(18, 'unggah', 13, 'Arsip_Surat_Keluar_B_20260729.xlsx', NULL, 'DEA (riska aulia)', '2026-08-04 09:24:42'),
(19, 'hapus', 13, 'Arsip_Surat_Keluar_B_20260729.xlsx', NULL, 'DEA (riska aulia)', '2026-08-04 09:24:52'),
(20, 'unggah', 14, 'ris2.jpeg', NULL, 'DEA (riska aulia)', '2026-08-04 09:29:04'),
(21, 'edit', 14, 'ris2.jpeg', NULL, 'DEA (riska aulia)', '2026-08-04 09:29:18'),
(22, 'tag_tambah', 14, 'ris2.jpeg', 'CEK LOOOOOOOOOOOOOOOOOOOG', 'DEA (riska aulia)', '2026-08-04 09:29:35'),
(23, 'hapus', 14, 'ris2.jpeg', NULL, 'DEA (riska aulia)', '2026-08-04 09:33:15'),
(24, 'unggah', 15, 'martabak.jpeg', NULL, 'DEA (riska aulia)', '2026-08-04 09:36:06'),
(25, 'edit', 15, 'martabak.jpeg', NULL, 'DEA (riska aulia)', '2026-08-04 09:36:52'),
(26, 'hapus', 15, 'martabak.jpeg', NULL, 'DEA (riska aulia)', '2026-08-04 09:37:02'),
(27, 'unggah', 16, 'smk.jpeg', NULL, 'DEA (riska aulia)', '2026-08-04 09:38:54'),
(28, 'edit', 16, 'smk.jpeg', 'File lampiran diganti', 'DEA (riska aulia)', '2026-08-04 09:45:50'),
(29, 'edit', 16, 'smk.jpeg', 'File lampiran diganti', 'DEA (riska aulia)', '2026-08-04 09:46:21'),
(30, 'edit', 16, 'smk.jpeg', 'File lampiran diganti', 'DEA (riska aulia)', '2026-08-04 09:52:26'),
(31, 'edit', 16, 'martabak.jpeg', 'File lampiran diganti', 'DEA (riska aulia)', '2026-08-04 09:53:11'),
(32, 'edit', 16, 'Arsip_Surat_Keluar_B_20260729.xlsx', 'File lampiran diganti', 'DEA (riska aulia)', '2026-08-04 09:56:07'),
(33, 'edit', 16, 'SURAT UNTUK KLIEN', NULL, 'DEA (riska aulia)', '2026-08-04 09:56:33'),
(34, 'hapus', 16, 'SURAT UNTUK KLIEN', NULL, 'DEA (riska aulia)', '2026-08-04 10:02:34'),
(35, 'unggah', 17, 'Surat klien', NULL, 'DEA (riska aulia)', '2026-08-04 10:03:07'),
(36, 'edit', 17, 'Surat khusus', NULL, 'DEA (riska aulia)', '2026-08-04 10:03:23'),
(37, 'unggah', 18, 'cek', NULL, 'DEA (riska aulia)', '2026-08-04 10:06:36'),
(38, 'hapus', 17, 'Surat khusus', NULL, 'LOVI', '2026-08-04 12:58:50'),
(39, 'hapus', 18, 'cek', NULL, 'LOVI', '2026-08-04 12:58:50'),
(40, 'unggah', 19, 'arsip-tamu-2026-08-05.xls', NULL, 'DEA', '2026-08-05 16:55:44'),
(41, 'tag_tambah', 19, 'arsip-tamu-2026-08-05.xls', 'tess', 'DEA', '2026-08-05 16:56:16'),
(42, 'hapus', 19, 'arsip-tamu-2026-08-05.xls', NULL, 'DEA', '2026-08-05 16:56:47'),
(43, 'unggah', 20, 'himpunan.jpeg', NULL, 'DEA', '2026-09-15 07:48:07'),
(44, 'unggah', 21, 'multivariabel 2.jpeg', NULL, 'DEA', '2026-09-15 07:48:26'),
(45, 'unggah', 22, 'deret tak terhingga.jpeg', NULL, 'DEA', '2026-09-15 07:48:41');

-- --------------------------------------------------------

--
-- Table structure for table `dokumen_tag`
--

CREATE TABLE `dokumen_tag` (
  `id` int(10) UNSIGNED NOT NULL,
  `dokumen_id` int(10) UNSIGNED NOT NULL,
  `tag` varchar(100) NOT NULL,
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id`, `nama_kategori`) VALUES
(1, 'Alat Tulis'),
(2, 'Kertas'),
(3, 'Peralatan Kantor');

-- --------------------------------------------------------

--
-- Table structure for table `kategori_dokumen`
--

CREATE TABLE `kategori_dokumen` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama_kategori` varchar(50) NOT NULL,
  `warna` varchar(20) DEFAULT NULL COMMENT 'contoh: pink, mint, yellow, dsb (untuk badge di UI)',
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori_dokumen`
--

INSERT INTO `kategori_dokumen` (`id`, `nama_kategori`, `warna`, `dibuat_pada`) VALUES
(1, 'Surat Edaran', 'yellow', '2026-07-30 08:29:34'),
(2, 'Laporan', 'mint', '2026-07-30 08:29:34'),
(3, 'Inventaris', 'yellow', '2026-07-30 08:29:34'),
(4, 'Notulen', 'blue', '2026-07-30 08:29:34'),
(5, 'Presentasi', 'lavender', '2026-07-30 08:29:34'),
(6, 'Dokumentasi', 'pink', '2026-07-30 08:29:34');

-- --------------------------------------------------------

--
-- Table structure for table `login_log`
--

CREATE TABLE `login_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `login_at` datetime NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `device` varchar(150) DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_log`
--

INSERT INTO `login_log` (`id`, `user_id`, `login_at`, `ip_address`, `device`, `location`) VALUES
(1, 1, '2026-08-01 14:54:10', '::1', 'Windows · Edge', 'Lokasi tidak diketahui (izin ditolak)'),
(2, 2, '2026-08-01 18:53:19', '::1', 'Windows · Edge', 'Lokasi tidak diketahui'),
(3, 2, '2026-08-01 18:56:20', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(4, 3, '2026-08-01 20:05:46', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(5, 2, '2026-08-01 20:07:47', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(6, 2, '2026-08-02 10:07:42', '::1', 'Windows · Edge', 'Lokasi tidak diketahui (izin ditolak)'),
(7, 2, '2026-08-02 19:34:54', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(8, 2, '2026-08-02 20:42:18', '::1', 'Windows · Edge', 'Lokasi tidak diketahui (izin ditolak)'),
(9, 2, '2026-08-03 07:44:15', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(10, 2, '2026-08-03 13:11:49', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(11, 2, '2026-08-03 13:37:10', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(12, 2, '2026-08-03 19:25:25', '::1', 'Windows · Edge', 'Lokasi tidak diketahui (izin ditolak)'),
(13, 2, '2026-08-04 07:39:58', '::1', 'Windows · Edge', NULL),
(14, 2, '2026-08-04 07:40:00', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(15, 2, '2026-08-04 08:26:07', '127.0.0.1', 'Windows · Edge', 'Subang, Indonesia'),
(16, 1, '2026-08-04 12:36:50', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(17, 2, '2026-08-05 07:00:18', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(18, 2, '2026-08-05 07:38:52', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(19, 2, '2026-08-05 09:00:45', '::1', 'Windows · Edge', 'Lokasi tidak diketahui (izin ditolak)'),
(20, 2, '2026-08-05 11:10:37', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(21, 2, '2026-08-05 13:26:52', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(22, 2, '2026-08-05 15:49:51', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(23, 2, '2026-08-05 16:19:02', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(24, 2, '2026-08-05 17:17:07', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(25, 2, '2026-08-06 08:02:10', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(26, 2, '2026-08-06 10:35:44', '::1', 'Windows · Edge', NULL),
(27, 2, '2026-08-06 10:35:52', '::1', 'Windows · Edge', NULL),
(28, 2, '2026-08-06 10:35:55', '::1', 'Windows · Edge', NULL),
(29, 1, '2026-08-06 10:36:24', '::1', 'Windows · Edge', NULL),
(30, 1, '2026-08-06 10:36:25', '::1', 'Windows · Edge', NULL),
(31, 1, '2026-08-06 10:36:25', '::1', 'Windows · Edge', NULL),
(32, 1, '2026-08-06 10:36:25', '::1', 'Windows · Edge', NULL),
(33, 1, '2026-08-06 10:36:25', '::1', 'Windows · Edge', NULL),
(34, 1, '2026-08-06 10:36:25', '::1', 'Windows · Edge', NULL),
(35, 1, '2026-08-06 10:36:26', '::1', 'Windows · Edge', NULL),
(36, 1, '2026-08-06 10:36:26', '::1', 'Windows · Edge', NULL),
(37, 1, '2026-08-06 10:36:26', '::1', 'Windows · Edge', NULL),
(38, 1, '2026-08-06 10:36:26', '::1', 'Windows · Edge', NULL),
(39, 1, '2026-08-06 10:36:26', '::1', 'Windows · Edge', NULL),
(40, 2, '2026-08-06 10:43:24', '::1', 'Windows · Edge', NULL),
(41, 2, '2026-08-06 12:39:21', '::1', 'Windows · Edge', NULL),
(42, 2, '2026-08-07 07:45:16', '::1', 'Windows · Edge', NULL),
(43, 2, '2026-08-07 07:45:29', '::1', 'Windows · Edge', NULL),
(44, 2, '2026-08-07 07:45:30', '::1', 'Windows · Edge', NULL),
(45, 2, '2026-08-07 07:45:31', '::1', 'Windows · Edge', NULL),
(46, 2, '2026-08-07 07:45:31', '::1', 'Windows · Edge', NULL),
(47, 2, '2026-08-07 07:45:32', '::1', 'Windows · Edge', NULL),
(48, 2, '2026-08-07 07:45:32', '::1', 'Windows · Edge', NULL),
(49, 2, '2026-08-07 07:46:03', '::1', 'Windows · Edge', NULL),
(50, 2, '2026-08-07 07:46:06', '::1', 'Windows · Edge', NULL),
(51, 2, '2026-08-10 10:39:01', '::1', 'Windows · Edge', 'Lokasi tidak diketahui (izin ditolak)'),
(52, 2, '2026-08-11 06:52:34', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(53, 2, '2026-08-11 11:25:47', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(54, 2, '2026-08-11 15:07:57', '::1', 'Windows · Edge', 'Lokasi tidak diketahui (izin ditolak)'),
(55, 1, '2026-08-11 15:46:34', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(56, 1, '2026-08-16 18:47:08', '::1', 'Windows · Edge', 'Lokasi tidak diketahui (izin ditolak)'),
(57, 1, '2026-08-16 18:48:46', '::1', 'Windows · Edge', 'Lokasi tidak diketahui (izin ditolak)'),
(58, 2, '2026-08-20 11:57:17', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(59, 2, '2026-08-30 19:23:08', '::1', 'Windows · Edge', 'Lokasi tidak diketahui (izin ditolak)'),
(60, 2, '2026-08-30 19:34:20', '::1', 'Windows · Edge', 'Lokasi tidak diketahui (izin ditolak)'),
(61, 2, '2026-09-04 19:05:08', '::1', 'Windows · Edge', 'Lokasi tidak diketahui (izin ditolak)'),
(62, 2, '2026-09-08 06:48:23', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(63, 2, '2026-09-09 07:12:54', '::1', 'Windows · Edge', 'Lokasi tidak diketahui (izin ditolak)'),
(64, 2, '2026-09-10 06:58:37', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(65, 2, '2026-09-10 12:33:19', '::1', 'Windows · Edge', 'Lokasi tidak diketahui (izin ditolak)'),
(66, 2, '2026-09-10 17:43:21', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(67, 2, '2026-09-11 10:50:25', '::1', 'Windows · Edge', 'Lokasi tidak diketahui (izin ditolak)'),
(68, 1, '2026-09-14 08:32:15', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(69, 2, '2026-09-15 07:41:25', '::1', 'Windows · Chrome', NULL),
(70, 2, '2026-09-15 07:41:25', '::1', 'Windows · Chrome', 'Lokasi tidak diketahui'),
(71, 2, '2026-09-15 16:50:19', '::1', 'Windows · Edge', 'Lokasi tidak diketahui'),
(72, 2, '2026-09-16 07:43:49', '::1', 'Windows · Edge', 'Lokasi tidak diketahui (izin ditolak)'),
(73, 2, '2026-09-16 12:25:13', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(74, 1, '2026-09-16 12:27:25', '::1', 'Windows · Edge', NULL),
(75, 2, '2026-09-16 13:42:13', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(76, 2, '2026-09-17 06:52:53', '::1', 'Windows · Edge', NULL),
(77, 2, '2026-09-17 06:52:54', '::1', 'Windows · Edge', 'Lokasi tidak diketahui (izin ditolak)'),
(78, 2, '2026-09-17 10:52:39', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(79, 1, '2026-09-17 12:08:37', '::1', 'Windows · Edge', 'Lokasi tidak diketahui'),
(80, 1, '2026-09-17 12:10:33', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(81, 3, '2026-09-17 12:11:39', '::1', 'Windows · Edge', 'Subang, Indonesia'),
(82, 3, '2026-09-17 12:14:17', '::1', 'Windows · Edge', 'Lokasi tidak diketahui'),
(83, 3, '2026-09-18 16:55:49', '::1', 'Windows · Edge', 'Lokasi tidak diketahui (izin ditolak)'),
(84, 3, '2026-09-19 10:26:51', '::1', 'Windows · Edge', 'Subang, Indonesia');

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas_di_profil`
--

CREATE TABLE `log_aktivitas_di_profil` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tipe` varchar(30) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_aktivitas_di_profil`
--

INSERT INTO `log_aktivitas_di_profil` (`id`, `user_id`, `tipe`, `judul`, `keterangan`, `dibuat_pada`) VALUES
(1, 2, 'profil', 'Mengubah Profil', NULL, '2026-08-02 22:21:55'),
(2, 2, 'profil', 'Mengubah Profil', 'Nama: \"DEA (riska aulia)\"', '2026-08-02 22:28:49'),
(3, 2, 'profil', 'Menghapus Foto Profil', NULL, '2026-08-02 22:28:58'),
(4, 2, 'profil', 'Mengubah Foto Profil', NULL, '2026-08-02 22:29:08'),
(5, 2, 'profil', 'Mengubah Profil', 'Alamat: \"Jawa Barat, Indonesia\"', '2026-08-02 22:29:32'),
(6, 1, 'profil', 'Mengubah Foto Profil', NULL, '2026-08-04 14:37:56'),
(7, 2, 'profil', 'Mengubah Profil', 'Bio: \"Sekretariat\"', '2026-08-05 08:46:16'),
(8, 2, 'profil', 'Mengubah Profil', 'Bio: \"Sekretariat - 123\"', '2026-08-05 08:46:28'),
(9, 2, 'profil', 'Mengubah Profil', 'Nama: \"DEA\"', '2026-08-05 11:47:16'),
(10, 2, 'profil', 'Mengubah Profil', 'Nama: \"Dea\"', '2026-08-05 12:34:58'),
(11, 2, 'profil', 'Mengubah Profil', 'Nama: \"DEA\"', '2026-08-05 12:35:20'),
(12, 2, 'profil', 'Mengubah Foto Profil', NULL, '2026-08-05 17:00:20'),
(13, 2, 'profil', 'Mengubah Profil', 'Bio: (kosong)', '2026-08-05 17:02:11'),
(14, 2, 'profil', 'Mengubah Profil', 'Nama: \"RISKA AULIA\"', '2026-08-11 11:26:06'),
(15, 2, 'profil', 'Mengubah Profil', 'Nama: \"LINTANG\"', '2026-08-11 13:46:28'),
(16, 2, 'profil', 'Mengubah Profil', 'Nama: \"DEA\"', '2026-08-11 13:47:11'),
(17, 2, 'profil', 'Mengubah Profil', 'No. HP: (kosong)', '2026-09-17 11:47:20'),
(18, 2, 'profil', 'Mengubah Profil', 'Bio: \"anak kantor\", No. HP: \"08\"', '2026-09-17 11:48:38'),
(19, 3, 'profil', 'Mengubah Profil', 'Nama: \"HERMAN\", Email: \"herhis@gmail.com\"', '2026-09-17 12:12:27'),
(20, 3, 'password', 'Mengubah Username & Kata Sandi', 'Username: \"HERMAN\", Kata sandi diperbarui', '2026-09-17 12:13:20'),
(21, 3, 'profil', 'Mengubah Foto Profil', NULL, '2026-09-17 12:13:55');

-- --------------------------------------------------------

--
-- Table structure for table `peminjaman_aset`
--

CREATE TABLE `peminjaman_aset` (
  `id` int(10) UNSIGNED NOT NULL,
  `aset_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Opsional, kalau mau dikaitkan ke tabel aset',
  `nama_peminjam` varchar(150) NOT NULL,
  `divisi` varchar(100) NOT NULL,
  `no_wa` varchar(30) NOT NULL,
  `barang` varchar(150) NOT NULL COMMENT 'Nama barang yang dipinjam (teks bebas dari form)',
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `keperluan` text DEFAULT NULL,
  `status` enum('Menunggu','Disetujui','Ditolak') NOT NULL DEFAULT 'Menunggu',
  `diproses_oleh_user_id` int(11) DEFAULT NULL COMMENT 'Admin yang menyetujui/menolak',
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp(),
  `diperbarui_pada` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `peminjaman_aset`
--

INSERT INTO `peminjaman_aset` (`id`, `aset_id`, `nama_peminjam`, `divisi`, `no_wa`, `barang`, `jumlah`, `tanggal_mulai`, `tanggal_selesai`, `keperluan`, `status`, `diproses_oleh_user_id`, `dibuat_pada`, `diperbarui_pada`) VALUES
(3, NULL, 'AULIA', 'PEMASARAN', '082120025508', 'Kursi kondangan', 4, '2026-08-10', '2026-08-29', 'Buat nikahan anak', 'Disetujui', 2, '2026-08-10 11:33:35', '2026-08-10 11:44:09'),
(5, NULL, 'KANIA', 'OPRASIONAL', '082120025508', 'MOUSE KOMPUTER', 2, '2026-08-10', '2026-08-30', 'Aku pinjem yang warna biru sama pink ya', 'Disetujui', 2, '2026-08-11 12:37:10', '2026-09-15 17:20:12'),
(6, NULL, 'RISKA AULIA', 'it', '082120025508', 'Laptop php silver', 1, '2026-09-15', '2026-09-30', 'Untuk ngerjain laporan PKL yang melelahkan :>', 'Ditolak', 2, '2026-09-15 17:32:41', '2026-09-15 17:36:08'),
(7, NULL, 'Ika', 'rpl', '082120025508', 'Laptop acer old', 2, '2026-09-15', '2026-09-29', '', 'Menunggu', NULL, '2026-09-15 17:33:45', '2026-09-15 17:33:45');

-- --------------------------------------------------------

--
-- Table structure for table `perjanjian_kerjasama`
--

CREATE TABLE `perjanjian_kerjasama` (
  `id` int(10) UNSIGNED NOT NULL,
  `nomor_pks` varchar(50) NOT NULL COMMENT 'Contoh: PKS-001/SEK/I/2024',
  `nama_dokumen` varchar(255) NOT NULL COMMENT 'Nama file asli, contoh: Nota Kesepahaman Kerjasama Pendidikan.pdf',
  `deskripsi` text DEFAULT NULL COMMENT 'Isi kolom "Tentang" pada tabel',
  `kategori` enum('mou','kontrak','sewa','perpanjangan') NOT NULL DEFAULT 'mou' COMMENT 'mou=Nota Kesepahaman, kontrak=Kontrak Kerja Sama, sewa=Sewa/Aset, perpanjangan=Perpanjangan Kerjasama',
  `tipe_file` varchar(10) NOT NULL DEFAULT '',
  `path_file` varchar(255) DEFAULT NULL COMMENT 'Lokasi file di server, mis. uploads/perjanjian/xxx.pdf',
  `ukuran_file` int(10) UNSIGNED DEFAULT NULL COMMENT 'Ukuran file dalam bytes (format ke KB/MB saat ditampilkan)',
  `tanggal_mulai` date NOT NULL COMMENT 'Tanggal dokumen berlaku / kolom "Tanggal"',
  `tanggal_selesai` date DEFAULT NULL COMMENT 'Masa berlaku s/d (kolom tersembunyi "Masa Berlaku")',
  `nama_mitra` varchar(150) NOT NULL COMMENT 'Kolom "Mitra Kerjasama"',
  `keterangan_mitra` varchar(150) DEFAULT NULL COMMENT 'Contoh: Mitra Pendidikan, Mitra Katering',
  `diunggah_oleh` varchar(100) NOT NULL COMMENT 'Kolom "Diunggah Oleh"',
  `diunggah_oleh_role` varchar(100) DEFAULT 'Sekretariat',
  `is_favorit` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Toggle bintang favorit di UI',
  `diarsipkan` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 = masih aktif, 1 = sudah diarsipkan',
  `diarsipkan_pada` datetime DEFAULT NULL,
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp(),
  `diperbarui_pada` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dokumen perjanjian kerjasama (aktif & arsip)';

--
-- Dumping data for table `perjanjian_kerjasama`
--

INSERT INTO `perjanjian_kerjasama` (`id`, `nomor_pks`, `nama_dokumen`, `deskripsi`, `kategori`, `tipe_file`, `path_file`, `ukuran_file`, `tanggal_mulai`, `tanggal_selesai`, `nama_mitra`, `keterangan_mitra`, `diunggah_oleh`, `diunggah_oleh_role`, `is_favorit`, `diarsipkan`, `diarsipkan_pada`, `dibuat_pada`, `diperbarui_pada`) VALUES
(20, 'PKS-0001/05/Agustus/2026', 'percobaan', 'percobaan', 'mou', '', '', 0, '2026-08-06', '2026-08-29', 'percobaan', 'Mitra Kerjasama', 'DEA', 'Sekretariat', 0, 1, '2026-08-05 16:53:50', '2026-08-05 16:52:36', '2026-08-05 16:53:50'),
(21, 'PKS-0001/14/September/2026', 'cek cek', 'Nota Kesepahaman dengan pt mie jebew.', 'mou', 'jpeg', 'uploads/pks-0001-14-september-2026_1789355339_WhatsApp_Image_2026-09-10_at_19.06.40.jpeg', 38723, '2026-09-15', '2026-09-30', 'pt mie jebew', 'Mitra Kerjasama', 'LOVI', 'Sekretariat', 0, 0, NULL, '2026-09-14 10:08:59', '2026-09-14 10:08:59'),
(22, 'PKS-0002/14/September/2026', '123', 'contoh dokumen yng udah berakhir', 'mou', '', '', 0, '2026-09-12', '2026-09-13', 'pt ayam geprek', 'Mitra Kerjasama', 'LOVI', 'Sekretariat', 0, 0, NULL, '2026-09-14 10:11:43', '2026-09-14 10:11:43'),
(23, 'PKS-0003/14/September/2026', 'dokumen perjanjian ke 10', 'ini yang berakhirnya masih jauh', 'mou', '', '', 0, '2026-12-31', '2028-01-01', 'pt. speda listrik', 'Mitra Kerjasama', 'LOVI', 'Sekretariat', 0, 0, NULL, '2026-09-14 10:25:37', '2026-09-14 10:25:37');

-- --------------------------------------------------------

--
-- Table structure for table `perjanjian_kerjasama_password`
--

CREATE TABLE `perjanjian_kerjasama_password` (
  `id` int(11) NOT NULL,
  `password_hash` varchar(255) NOT NULL COMMENT 'Hasil password_hash() PHP, bukan teks polos',
  `diubah_oleh` varchar(100) DEFAULT NULL,
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp(),
  `diubah_pada` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `perjanjian_kerjasama_password`
--

INSERT INTO `perjanjian_kerjasama_password` (`id`, `password_hash`, `diubah_oleh`, `dibuat_pada`, `diubah_pada`) VALUES
(1, '$2y$10$yOrTqtH0jIgsG0/ejbKF/.HTmc2NrnDiqqDYEKPS7N3slE7O9M2L6', 'DEA', '2026-07-29 16:24:09', '2026-08-05 16:53:28');

-- --------------------------------------------------------

--
-- Table structure for table `rapat`
--

CREATE TABLE `rapat` (
  `id` int(10) UNSIGNED NOT NULL,
  `judul` varchar(150) NOT NULL,
  `pic` varchar(150) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu_mulai` time NOT NULL,
  `waktu_selesai` time DEFAULT NULL,
  `lokasi` varchar(150) NOT NULL,
  `status` enum('aktif','selesai') NOT NULL DEFAULT 'aktif',
  `diarsipkan` tinyint(1) NOT NULL DEFAULT 0,
  `id_user` int(11) DEFAULT NULL,
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp(),
  `diupdate_pada` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rapat`
--

INSERT INTO `rapat` (`id`, `judul`, `pic`, `tanggal`, `waktu_mulai`, `waktu_selesai`, `lokasi`, `status`, `diarsipkan`, `id_user`, `dibuat_pada`, `diupdate_pada`) VALUES
(11, 'Rapat mengenai penarikan anak PKL', 'Riska', '2026-08-05', '11:06:00', '11:25:00', 'Kantor - Ruang meeting A', 'selesai', 1, 2, '2026-08-05 11:25:59', '2026-08-05 16:43:52'),
(12, 'RAPAT PELEPASAN', 'Tia', '2026-09-17', '08:00:00', '13:00:00', 'Kantor', 'aktif', 0, 2, '2026-08-05 11:35:13', '2026-09-17 12:51:13'),
(13, 'ABC', 'RISKA', '2026-08-06', '08:00:00', '09:14:00', 'KANTOR', 'selesai', 1, 2, '2026-08-05 16:23:32', '2026-08-06 09:58:23'),
(14, 'PENUIN INDEX 1', 'R', '2026-08-12', '10:00:00', '11:00:00', 'Meeting room A', 'aktif', 0, 2, '2026-08-06 10:25:54', '2026-08-06 10:25:54'),
(15, 'PENUIN INDEX 2', 'I', '2026-08-10', '15:00:00', '17:00:00', 'Meeting room B', 'selesai', 1, 2, '2026-08-06 10:26:22', '2026-08-11 07:10:23'),
(16, 'PERCOBAAN', 'RISKA KEYZA', '2026-08-28', '08:00:00', '10:00:00', 'Kantor - Metingroom A', 'selesai', 0, 2, '2026-08-06 15:40:59', '2026-09-10 13:15:37'),
(17, 'PERCOBAAN', 'Riska', '2026-09-18', '08:00:00', '09:00:00', 'Kantor', 'aktif', 0, 2, '2026-09-09 09:44:21', '2026-09-09 09:44:21'),
(19, 'cek hari ini 1', 'riska', '2026-09-10', '12:40:00', '13:00:00', 'SMK', 'aktif', 0, 2, '2026-09-10 12:38:57', '2026-09-10 12:38:57'),
(20, 'cek hari ini 2', 'riska', '2026-09-10', '14:00:00', '14:20:00', 'SMK', 'aktif', 0, 2, '2026-09-10 12:39:27', '2026-09-10 12:39:27'),
(21, 'cek hari ini 3', 'riska', '2026-09-10', '15:00:00', '15:20:00', 'KANTOR RPL', 'aktif', 0, 2, '2026-09-10 12:40:01', '2026-09-10 12:40:01'),
(22, 'percepat', 'riska', '2026-09-10', '12:52:00', '12:55:00', 'smmk', 'aktif', 0, 2, '2026-09-10 12:51:40', '2026-09-10 12:51:58');

-- --------------------------------------------------------

--
-- Table structure for table `rapat_action_items`
--

CREATE TABLE `rapat_action_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_rapat` int(10) UNSIGNED NOT NULL,
  `teks` varchar(255) NOT NULL,
  `selesai` tinyint(1) NOT NULL DEFAULT 0,
  `urutan` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rapat_action_items`
--

INSERT INTO `rapat_action_items` (`id`, `id_rapat`, `teks`, `selesai`, `urutan`, `dibuat_pada`) VALUES
(13, 12, 'Bawa buku', 0, 0, '2026-08-05 11:35:13'),
(14, 12, 'Bawa kueh', 0, 0, '2026-08-05 11:35:13'),
(15, 17, 'bawa buku', 0, 0, '2026-09-09 09:44:21'),
(16, 16, 'yuni bagian luar dokumen', 0, 0, '2026-09-10 13:11:53'),
(17, 16, 'rani susun kalimat paragrah 1-8', 1, 1, '2026-09-10 13:12:06'),
(18, 16, 'ratna desain coveer sma tira', 0, 2, '2026-09-10 13:12:37'),
(19, 16, 'lovi atur kegiatan', 0, 3, '2026-09-10 13:12:49');

-- --------------------------------------------------------

--
-- Table structure for table `rapat_dokumen`
--

CREATE TABLE `rapat_dokumen` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_rapat` int(10) UNSIGNED NOT NULL,
  `nama_file` varchar(255) NOT NULL,
  `tipe_file` varchar(100) DEFAULT NULL,
  `ukuran_bytes` int(10) UNSIGNED DEFAULT NULL,
  `path_file` varchar(255) NOT NULL,
  `diunggah_pada` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rapat_dokumen`
--

INSERT INTO `rapat_dokumen` (`id`, `id_rapat`, `nama_file`, `tipe_file`, `ukuran_bytes`, `path_file`, `diunggah_pada`) VALUES
(11, 13, 'logo rpl.jpeg', 'image/jpeg', 8825, 'uploads/rapat/rpt_6a73064786d30_logo_rpl.jpeg', '2026-08-05 16:45:43'),
(12, 16, 'laporan pkl  riska & keizha - BKJL.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 11640904, 'uploads/rapat/rpt_6aa24a805eb662.07218942.docx', '2026-09-10 13:13:20');

-- --------------------------------------------------------

--
-- Table structure for table `rapat_log_aktivitas`
--

CREATE TABLE `rapat_log_aktivitas` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_rapat` int(10) UNSIGNED NOT NULL,
  `teks` varchar(255) NOT NULL,
  `nama_user` varchar(150) DEFAULT NULL,
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rapat_log_aktivitas`
--

INSERT INTO `rapat_log_aktivitas` (`id`, `id_rapat`, `teks`, `nama_user`, `dibuat_pada`) VALUES
(53, 11, 'Rapat dibuat', 'DEA (riska aulia)', '2026-08-05 11:25:59'),
(54, 11, 'Detail rapat diperbarui', 'DEA (riska aulia)', '2026-08-05 11:27:09'),
(55, 11, 'Detail rapat diperbarui', 'DEA (riska aulia)', '2026-08-05 11:27:29'),
(56, 12, 'Rapat dibuat', 'DEA (riska aulia)', '2026-08-05 11:35:13'),
(57, 12, 'Menambahkan action item: Bawa buku', 'DEA (riska aulia)', '2026-08-05 11:35:13'),
(58, 12, 'Menambahkan action item: Bawa kueh', 'DEA (riska aulia)', '2026-08-05 11:35:13'),
(59, 13, 'Rapat dibuat', 'DEA', '2026-08-05 16:23:32'),
(60, 11, 'Rapat ditandai selesai', 'DEA', '2026-08-05 16:43:47'),
(61, 11, 'Rapat diarsipkan & dipindahkan ke Arsip Rapat', 'DEA', '2026-08-05 16:43:52'),
(62, 13, 'Mengunggah dokumen: logo rpl.jpeg', 'DEA', '2026-08-05 16:45:43'),
(63, 13, 'Detail rapat diperbarui', 'DEA', '2026-08-06 09:13:46'),
(64, 13, 'Rapat diarsipkan & dipindahkan ke Arsip Rapat', 'DEA', '2026-08-06 09:58:23'),
(65, 14, 'Rapat dibuat', 'DEA', '2026-08-06 10:25:54'),
(66, 15, 'Rapat dibuat', 'DEA', '2026-08-06 10:26:22'),
(67, 16, 'Rapat dibuat', 'DEA', '2026-08-06 15:40:59'),
(68, 15, 'Rapat diarsipkan & dipindahkan ke Arsip Rapat', 'DEA', '2026-08-11 07:10:23'),
(69, 17, 'Rapat dibuat', 'DEA', '2026-09-09 09:44:21'),
(70, 17, 'Menambahkan action item: bawa buku', 'DEA', '2026-09-09 09:44:21'),
(72, 19, 'Rapat dibuat', 'DEA', '2026-09-10 12:38:57'),
(73, 20, 'Rapat dibuat', 'DEA', '2026-09-10 12:39:27'),
(74, 21, 'Rapat dibuat', 'DEA', '2026-09-10 12:40:01'),
(75, 22, 'Rapat dibuat', 'DEA', '2026-09-10 12:51:40'),
(76, 22, 'Detail rapat diperbarui', 'DEA', '2026-09-10 12:51:58'),
(77, 16, 'Menambahkan action item: yuni bagian luar dokumen', 'DEA', '2026-09-10 13:11:53'),
(78, 16, 'Menambahkan action item: rani susun kalimat paragrah 1-8', 'DEA', '2026-09-10 13:12:06'),
(79, 16, 'Menambahkan action item: ratna desain coveer sma tira', 'DEA', '2026-09-10 13:12:37'),
(80, 16, 'Menambahkan action item: lovi atur kegiatan', 'DEA', '2026-09-10 13:12:49'),
(81, 16, 'Menambahkan topik: laporan kantor', 'DEA', '2026-09-10 13:13:06'),
(82, 16, 'Mengunggah dokumen: laporan pkl  riska & keizha - BKJL.docx', 'DEA', '2026-09-10 13:13:20'),
(83, 16, 'Rapat ditandai selesai', 'DEA', '2026-09-10 13:15:37'),
(84, 12, 'Detail rapat diperbarui', 'HERMAN', '2026-09-17 12:51:13');

-- --------------------------------------------------------

--
-- Table structure for table `rapat_topik`
--

CREATE TABLE `rapat_topik` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_rapat` int(10) UNSIGNED NOT NULL,
  `teks` varchar(255) NOT NULL,
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rapat_topik`
--

INSERT INTO `rapat_topik` (`id`, `id_rapat`, `teks`, `dibuat_pada`) VALUES
(7, 16, 'laporan kantor', '2026-09-10 13:13:06');

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_pengiriman_surat_keluar`
--

CREATE TABLE `riwayat_pengiriman_surat_keluar` (
  `id` int(10) UNSIGNED NOT NULL,
  `surat_keluar_id` int(10) UNSIGNED NOT NULL,
  `metode` enum('Email','WhatsApp','Kurir Internal','Pos') NOT NULL,
  `catatan_pengantar` text DEFAULT NULL,
  `dikirim_pada` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Riwayat pengiriman tiap surat keluar';

--
-- Dumping data for table `riwayat_pengiriman_surat_keluar`
--

INSERT INTO `riwayat_pengiriman_surat_keluar` (`id`, `surat_keluar_id`, `metode`, `catatan_pengantar`, `dikirim_pada`) VALUES
(9, 37, 'Pos', 'Trlampir', '2026-08-05 09:06:36'),
(10, 38, 'WhatsApp', 'Masa berakhir pkl anak smk', '2026-08-05 09:10:57'),
(11, 38, 'WhatsApp', 'Kirim lewat wa', '2026-08-05 16:37:24'),
(12, 44, 'Email', 'Surat ini dijadikan contoh untuk laporan PKL', '2026-09-10 07:40:43'),
(13, 37, 'Email', 'Surat ini dijadikan contoh untuk laporan pkl.', '2026-09-10 07:43:44'),
(14, 37, 'Email', 'Surat ini dijadikan contoh untuk laporan pkl.', '2026-09-10 07:50:28'),
(15, 39, 'WhatsApp', '', '2026-09-10 08:16:09'),
(16, 39, 'WhatsApp', '', '2026-09-10 08:16:37');

-- --------------------------------------------------------

--
-- Table structure for table `surat_keluar`
--

CREATE TABLE `surat_keluar` (
  `id` int(10) UNSIGNED NOT NULL,
  `no_surat` varchar(50) NOT NULL COMMENT 'Contoh: 098/SK-OUT/VI/2026',
  `tanggal_pengajuan` date NOT NULL,
  `tujuan` varchar(150) NOT NULL COMMENT 'Contoh: Yth. Kepala Dinas Pendidikan',
  `email_tujuan` varchar(150) DEFAULT NULL,
  `no_wa_tujuan` varchar(20) DEFAULT NULL,
  `perihal` varchar(255) NOT NULL,
  `dibuat_oleh` varchar(100) DEFAULT NULL COMMENT 'Nama/username pembuat surat',
  `jenis` enum('Surat Rahasia','Surat Biasa','Surat Tugas','Surat Perintah','Surat Keputusan','Surat Jalan','Surat Izin','Nota Perusahaan','Surat Edaran') NOT NULL,
  `file_surat` varchar(255) DEFAULT NULL COMMENT 'Nama/path file surat yang diunggah',
  `status` enum('Draft','Menunggu Approval','Disetujui','Revisi','Terkirim','Diterima') NOT NULL DEFAULT 'Draft',
  `diarsipkan` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 = masih aktif di daftar Surat Keluar, 1 = sudah diarsipkan',
  `tgl_arsip` datetime DEFAULT NULL,
  `sudah_dibaca` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 = belum dibaca (tanda merah), 1 = sudah dibaca',
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp(),
  `diperbarui_pada` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Data surat keluar instansi';

--
-- Dumping data for table `surat_keluar`
--

INSERT INTO `surat_keluar` (`id`, `no_surat`, `tanggal_pengajuan`, `tujuan`, `email_tujuan`, `no_wa_tujuan`, `perihal`, `dibuat_oleh`, `jenis`, `file_surat`, `status`, `diarsipkan`, `tgl_arsip`, `sudah_dibaca`, `dibuat_pada`, `diperbarui_pada`) VALUES
(37, '880/CEK01/2026', '2026-08-04', 'Karyawan baru di lapangan', 'riskaaulia3838@gmail.com', '', 'Pemberitahuan masuk kerja', 'DEA', 'Surat Izin', 'logo-sekretariat.jpg.jpeg|uploads/surat_keluar/sk_6a7193579597b_logo-sekretariat_jpg.jpeg', 'Terkirim', 1, '2026-09-10 08:42:05', 0, '2026-08-04 14:23:03', '2026-09-10 08:42:05'),
(38, 'ABC/CEK/05/08/2026', '2026-08-07', 'Anak PKL', 'riskaaulia3838@gmail.com', '082120025508', 'Pemberitahuan akhir pkl', 'DEA (riska aulia)', 'Surat Biasa', 'logo bkjl.jpeg|uploads/surat_keluar/sk_6a729b8fe815f_logo_bkjl.jpeg', 'Terkirim', 1, '2026-08-05 16:38:09', 0, '2026-08-05 09:10:23', '2026-08-05 16:38:09'),
(39, '001/22', '2026-09-09', 'X poes', '', '082120025508', 'Undanga spesial', 'DEA', 'Surat Biasa', 'logo smk - tanpa bg.png|uploads/surat_keluar/sk_6aa2027f7f4530.85118480.png', 'Terkirim', 1, '2026-09-10 08:42:07', 0, '2026-09-09 11:07:35', '2026-09-10 08:42:07'),
(40, '001/2254', '2026-09-09', 'smkn 2', 'smkn2@gmail.com', '083245678954', 'Undanga spesial', 'DEA', 'Surat Biasa', NULL, 'Disetujui', 0, NULL, 0, '2026-09-09 11:07:44', '2026-09-10 08:22:24'),
(41, '002/543', '2026-09-09', 'smkn 2', 'smkn2@gmail.com', '083245678954', 'Undanga spesial', 'DEA', 'Surat Biasa', NULL, 'Disetujui', 0, NULL, 0, '2026-09-09 11:07:56', '2026-09-10 07:23:41'),
(42, '7865/7', '2026-09-09', 'smkn 2', 'smkn2@gmail.com', '083245678954', 'Undanga spesial', 'DEA', 'Surat Biasa', NULL, 'Menunggu Approval', 0, NULL, 0, '2026-09-09 11:08:13', '2026-09-10 07:23:52'),
(43, 'cek', '2026-09-10', 'risri', 'adklfj@gmail.com', '', 'cek', 'DEA', 'Nota Perusahaan', NULL, 'Disetujui', 0, NULL, 0, '2026-09-10 07:01:20', '2026-09-17 12:41:42'),
(44, 'cekcecek', '2026-09-10', 'risri', 'adklfj@gmail.com', '', 'cek', 'DEA', 'Nota Perusahaan', NULL, 'Terkirim', 1, '2026-09-10 08:42:49', 0, '2026-09-10 07:01:28', '2026-09-10 08:42:49'),
(45, '880/CEK0223/2026', '2026-09-10', 'umkm', '', '', 'coba boba', 'DEA', 'Surat Perintah', NULL, 'Draft', 0, NULL, 0, '2026-09-10 07:03:29', '2026-09-10 07:03:29');

-- --------------------------------------------------------

--
-- Table structure for table `surat_masuk`
--

CREATE TABLE `surat_masuk` (
  `id` int(10) UNSIGNED NOT NULL,
  `kode_surat` varchar(10) NOT NULL,
  `kategori` varchar(30) NOT NULL,
  `no_surat` varchar(50) NOT NULL,
  `tgl_terima` date NOT NULL,
  `pengirim` varchar(150) NOT NULL,
  `perihal` varchar(255) NOT NULL,
  `file_lampiran` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` enum('Baru','Disposisi','Diproses','Selesai') NOT NULL DEFAULT 'Baru',
  `sudah_dibaca` tinyint(1) NOT NULL DEFAULT 0,
  `diarsipkan` tinyint(1) NOT NULL DEFAULT 0,
  `tgl_arsip` datetime DEFAULT NULL,
  `dibuat_oleh` varchar(100) DEFAULT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp(),
  `diperbarui_pada` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `surat_masuk`
--

INSERT INTO `surat_masuk` (`id`, `kode_surat`, `kategori`, `no_surat`, `tgl_terima`, `pengirim`, `perihal`, `file_lampiran`, `file_path`, `status`, `sudah_dibaca`, `diarsipkan`, `tgl_arsip`, `dibuat_oleh`, `dibuat_pada`, `diperbarui_pada`) VALUES
(42, 'ADM', 'Administratif', '01/PERCOBAAN1/04/08/2026', '2026-01-07', 'Rina', 'Undangan kerjasama', 'Arsip_BARU_TES_123_20260729.pdf', 'sm_6a7186c1a26341.40977026.pdf', 'Selesai', 1, 1, '2026-08-05 11:50:24', 'DEA', '2026-08-04 06:29:21', '2026-08-05 04:50:24'),
(43, 'INT', 'Internal', '123/RK/05/08/2026', '2026-01-06', 'santoso', 'Kirim kabar', 'logo rpl.jpeg', 'sm_6a72964598f850.23867452.jpeg', 'Baru', 1, 0, NULL, 'DEA', '2026-08-05 01:47:49', '2026-08-05 02:02:54'),
(44, 'UND', 'Undangan', 'tes garfik/05/08/2026', '2026-01-04', 'Pak Sandi', 'Pemberitahuan', 'logo rpl.jpeg', 'sm_6a72c00111c3e3.43236691.jpeg', 'Selesai', 1, 1, '2026-09-10 08:56:54', 'DEA', '2026-08-05 04:45:53', '2026-09-10 01:56:54'),
(45, 'EKS', 'Eksternal', 'CEK/05/08/2026', '2026-08-03', 'RISKA', 'PERCOBAAN', 'logo bkjl.jpeg', 'sm_6a73029d13a183.01172851.jpeg', 'Selesai', 1, 1, '2026-08-05 16:31:46', 'RISKA', '2026-08-05 09:30:05', '2026-08-05 09:31:46'),
(46, 'UND', 'Undangan', 'COBA/01/08/2026', '2026-08-16', 'Riska', 'undangan', 'logo rpl.jpeg', 'sm_6a81a3fa811a11.96293429.jpeg', 'Selesai', 1, 1, '2026-09-09 07:26:17', 'LOVI', '2026-08-16 11:50:18', '2026-09-09 00:26:17'),
(47, 'UND', 'Undangan', 'cek', '2026-09-07', 'ris', 'ngecek', NULL, NULL, 'Baru', 1, 0, NULL, 'DEA', '2026-09-09 00:17:26', '2026-09-09 00:17:26');

-- --------------------------------------------------------

--
-- Table structure for table `tamu`
--

CREATE TABLE `tamu` (
  `id` int(10) UNSIGNED NOT NULL,
  `instansi` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `penanggung_jawab` varchar(150) DEFAULT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `keperluan` varchar(255) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `waktu` time DEFAULT NULL,
  `selesai` time DEFAULT NULL,
  `keterangan_waktu` varchar(50) DEFAULT NULL,
  `status` enum('pending','upcoming','done','deleted') NOT NULL DEFAULT 'pending',
  `diarsipkan` tinyint(1) NOT NULL DEFAULT 0,
  `diarsipkan_pada` datetime DEFAULT NULL,
  `avatar` varchar(10) NOT NULL DEFAULT '?',
  `sudah_dibaca` tinyint(1) NOT NULL DEFAULT 0,
  `id_user` int(11) DEFAULT NULL,
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp(),
  `diupdate_pada` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tamu`
--

INSERT INTO `tamu` (`id`, `instansi`, `email`, `penanggung_jawab`, `jabatan`, `keperluan`, `tanggal`, `waktu`, `selesai`, `keterangan_waktu`, `status`, `diarsipkan`, `diarsipkan_pada`, `avatar`, `sudah_dibaca`, `id_user`, `dibuat_pada`, `diupdate_pada`) VALUES
(16, 'PERCOBAAN', 'keyyy@gmail.com', 'keizha', 'siswa pkl', 'Presentasi', '2026-08-05', '16:00:00', '17:00:00', '', 'done', 1, '2026-08-05 16:47:57', '🏢', 0, 2, '2026-08-05 16:47:25', '2026-08-05 16:47:57'),
(23, 'X POES', 'kesiswaanpoes@gmail.com', 'RISKA AULIA', 'RPL', 'Pinjem dulu 100.000', '2026-09-11', '12:22:00', '13:00:00', 'on timee', 'done', 0, NULL, '🏦', 0, 2, '2026-09-11 12:20:51', '2026-09-17 12:55:34');

-- --------------------------------------------------------

--
-- Table structure for table `todo_list`
--

CREATE TABLE `todo_list` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `tugas` varchar(255) NOT NULL,
  `selesai` tinyint(1) NOT NULL DEFAULT 0,
  `urutan` int(11) NOT NULL DEFAULT 0,
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp(),
  `diperbarui_pada` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `todo_list`
--

INSERT INTO `todo_list` (`id`, `user_id`, `tugas`, `selesai`, `urutan`, `dibuat_pada`, `diperbarui_pada`) VALUES
(1, 1, 'Beli buku', 0, 1, '2026-07-30 15:27:18', '2026-07-30 15:27:18'),
(2, 1, 'Buat email', 0, 2, '2026-07-30 15:27:18', '2026-07-30 15:27:18');

-- --------------------------------------------------------

--
-- Table structure for table `tugas_deadline`
--

CREATE TABLE `tugas_deadline` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `tugas` varchar(255) NOT NULL,
  `selesai` tinyint(1) NOT NULL DEFAULT 0,
  `urutan` int(11) NOT NULL DEFAULT 0,
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp(),
  `diperbarui_pada` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `nama` varchar(150) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `no_hp` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `password_updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_seen` datetime DEFAULT NULL,
  `kuis_benar` int(11) NOT NULL DEFAULT 0,
  `kuis_salah` int(11) NOT NULL DEFAULT 0,
  `slot_menang` int(11) NOT NULL DEFAULT 0,
  `slot_putaran` int(11) NOT NULL DEFAULT 0,
  `badge_atk_unlocked` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `nama`, `bio`, `no_hp`, `email`, `alamat`, `foto`, `password`, `password_updated_at`, `created_at`, `last_seen`, `kuis_benar`, `kuis_salah`, `slot_menang`, `slot_putaran`, `badge_atk_unlocked`) VALUES
(1, 'LOVI', 'LOVI', 'Mommy gaull', '0821 2990 5786', 'lovi123@gmail.com', 'Jawa Barat.', 'user_1_1785829076.jpg', '$2y$10$jHZ.bRjA8g7/S8Wuy4tkjO4xTcrMNbt8Dmo2lN19H4Hj/7KFWBykq', '2026-07-31 07:16:39', '2026-07-31 06:53:44', '2026-09-17 12:10:38', 1, 0, 0, 0, 0),
(2, 'DEA', 'DEA', 'anak kantor', '08', 'dea02@gmail.com', 'Jawa Barat, Indonesia', 'user_2_1785924020.jpg', '$2y$10$bWmF5/HHCUV82/2kG2b./uBU0oWlw.Qun09rf3eo2FGnN/4Yv9JHG', NULL, '2026-07-31 06:53:44', '2026-09-17 12:07:14', 0, 0, 0, 0, 0),
(3, 'HERMAN', 'HERMAN', '', '', 'herhis@gmail.com', '', 'user_3_1789622035.png', '$2y$10$1QQO3lKP2ez6w3o2LvmsO.fLiQR5wiNXJdg/ngSLXuVxH6ROIH1OK', '2026-09-17 05:13:20', '2026-07-31 06:53:44', '2026-09-19 10:55:37', 0, 0, 0, 0, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `agenda`
--
ALTER TABLE `agenda`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `idx_kategori` (`kategori`),
  ADD KEY `idx_agenda_dibuat_oleh_user` (`dibuat_oleh_user_id`);

--
-- Indexes for table `aset`
--
ALTER TABLE `aset`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_kode_aset` (`kode_aset`),
  ADD KEY `idx_aset_status` (`status`),
  ADD KEY `idx_aset_kategori` (`kategori`),
  ADD KEY `idx_aset_dibuat_oleh_user` (`dibuat_oleh_user_id`);

--
-- Indexes for table `atk_barang`
--
ALTER TABLE `atk_barang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_kode_barang` (`kode_barang`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dibuat_pada` (`dibuat_pada`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `audit_log_sampah`
--
ALTER TABLE `audit_log_sampah`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dipindahkan_pada` (`dipindahkan_pada`);

--
-- Indexes for table `catatan`
--
ALTER TABLE `catatan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_catatan_user` (`user_id`);

--
-- Indexes for table `catatan_reminder`
--
ALTER TABLE `catatan_reminder`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_catatan_reminder_user` (`id_user`);

--
-- Indexes for table `catatan_reminder_perjanjian`
--
ALTER TABLE `catatan_reminder_perjanjian`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `catatan_reminder_tamu`
--
ALTER TABLE `catatan_reminder_tamu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_catatan_reminder_tamu_user` (`id_user`);

--
-- Indexes for table `dokumen`
--
ALTER TABLE `dokumen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kategori` (`kategori_id`),
  ADD KEY `idx_jenis` (`jenis`),
  ADD KEY `idx_tanggal` (`dibuat_pada`),
  ADD KEY `idx_diarsipkan` (`diarsipkan`);

--
-- Indexes for table `dokumen_log`
--
ALTER TABLE `dokumen_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dokumen_id` (`dokumen_id`),
  ADD KEY `idx_dibuat_pada` (`dibuat_pada`);

--
-- Indexes for table `dokumen_tag`
--
ALTER TABLE `dokumen_tag`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_dokumen_tag` (`dokumen_id`,`tag`),
  ADD KEY `idx_dokumen_id` (`dokumen_id`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_kategori` (`nama_kategori`);

--
-- Indexes for table `kategori_dokumen`
--
ALTER TABLE `kategori_dokumen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_nama_kategori` (`nama_kategori`);

--
-- Indexes for table `login_log`
--
ALTER TABLE `login_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_login_log_user` (`user_id`),
  ADD KEY `idx_login_log_user_time` (`user_id`,`login_at`);

--
-- Indexes for table `log_aktivitas_di_profil`
--
ALTER TABLE `log_aktivitas_di_profil`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_log_aktivitas_user_id` (`user_id`),
  ADD KEY `idx_log_aktivitas_dibuat_pada` (`dibuat_pada`);

--
-- Indexes for table `peminjaman_aset`
--
ALTER TABLE `peminjaman_aset`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_peminjaman_status` (`status`),
  ADD KEY `idx_peminjaman_divisi` (`divisi`),
  ADD KEY `idx_peminjaman_tanggal_mulai` (`tanggal_mulai`),
  ADD KEY `idx_peminjaman_aset_id` (`aset_id`),
  ADD KEY `idx_peminjaman_diproses_oleh_user` (`diproses_oleh_user_id`);

--
-- Indexes for table `perjanjian_kerjasama`
--
ALTER TABLE `perjanjian_kerjasama`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_nomor_pks` (`nomor_pks`),
  ADD KEY `idx_diarsipkan` (`diarsipkan`),
  ADD KEY `idx_kategori` (`kategori`),
  ADD KEY `idx_dibuat_pada` (`dibuat_pada`);

--
-- Indexes for table `perjanjian_kerjasama_password`
--
ALTER TABLE `perjanjian_kerjasama_password`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rapat`
--
ALTER TABLE `rapat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rapat_tanggal` (`tanggal`),
  ADD KEY `idx_rapat_status` (`status`);

--
-- Indexes for table `rapat_action_items`
--
ALTER TABLE `rapat_action_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rai_id_rapat` (`id_rapat`);

--
-- Indexes for table `rapat_dokumen`
--
ALTER TABLE `rapat_dokumen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rd_id_rapat` (`id_rapat`);

--
-- Indexes for table `rapat_log_aktivitas`
--
ALTER TABLE `rapat_log_aktivitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rla_id_rapat` (`id_rapat`);

--
-- Indexes for table `rapat_topik`
--
ALTER TABLE `rapat_topik`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rt_id_rapat` (`id_rapat`);

--
-- Indexes for table `riwayat_pengiriman_surat_keluar`
--
ALTER TABLE `riwayat_pengiriman_surat_keluar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_surat_keluar_id` (`surat_keluar_id`);

--
-- Indexes for table `surat_keluar`
--
ALTER TABLE `surat_keluar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_surat` (`no_surat`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_jenis` (`jenis`),
  ADD KEY `idx_tanggal_pengajuan` (`tanggal_pengajuan`),
  ADD KEY `idx_diarsipkan` (`diarsipkan`);

--
-- Indexes for table `surat_masuk`
--
ALTER TABLE `surat_masuk`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_no_surat` (`no_surat`),
  ADD KEY `idx_kategori` (`kategori`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_tgl_terima` (`tgl_terima`);

--
-- Indexes for table `tamu`
--
ALTER TABLE `tamu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tamu_user` (`id_user`);

--
-- Indexes for table `todo_list`
--
ALTER TABLE `todo_list`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_todo_user` (`user_id`);

--
-- Indexes for table `tugas_deadline`
--
ALTER TABLE `tugas_deadline`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tugas_deadline_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `agenda`
--
ALTER TABLE `agenda`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `aset`
--
ALTER TABLE `aset`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `atk_barang`
--
ALTER TABLE `atk_barang`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=344;

--
-- AUTO_INCREMENT for table `audit_log_sampah`
--
ALTER TABLE `audit_log_sampah`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `catatan`
--
ALTER TABLE `catatan`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `catatan_reminder`
--
ALTER TABLE `catatan_reminder`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `catatan_reminder_perjanjian`
--
ALTER TABLE `catatan_reminder_perjanjian`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `catatan_reminder_tamu`
--
ALTER TABLE `catatan_reminder_tamu`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `dokumen`
--
ALTER TABLE `dokumen`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `dokumen_log`
--
ALTER TABLE `dokumen_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `dokumen_tag`
--
ALTER TABLE `dokumen_tag`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `kategori_dokumen`
--
ALTER TABLE `kategori_dokumen`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `login_log`
--
ALTER TABLE `login_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `log_aktivitas_di_profil`
--
ALTER TABLE `log_aktivitas_di_profil`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `peminjaman_aset`
--
ALTER TABLE `peminjaman_aset`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `perjanjian_kerjasama`
--
ALTER TABLE `perjanjian_kerjasama`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `perjanjian_kerjasama_password`
--
ALTER TABLE `perjanjian_kerjasama_password`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `rapat`
--
ALTER TABLE `rapat`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `rapat_action_items`
--
ALTER TABLE `rapat_action_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `rapat_dokumen`
--
ALTER TABLE `rapat_dokumen`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `rapat_log_aktivitas`
--
ALTER TABLE `rapat_log_aktivitas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `rapat_topik`
--
ALTER TABLE `rapat_topik`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `riwayat_pengiriman_surat_keluar`
--
ALTER TABLE `riwayat_pengiriman_surat_keluar`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `surat_keluar`
--
ALTER TABLE `surat_keluar`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `surat_masuk`
--
ALTER TABLE `surat_masuk`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `tamu`
--
ALTER TABLE `tamu`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `todo_list`
--
ALTER TABLE `todo_list`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tugas_deadline`
--
ALTER TABLE `tugas_deadline`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `aset`
--
ALTER TABLE `aset`
  ADD CONSTRAINT `fk_aset_dibuat_oleh` FOREIGN KEY (`dibuat_oleh_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `catatan_reminder`
--
ALTER TABLE `catatan_reminder`
  ADD CONSTRAINT `fk_catatan_reminder_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `catatan_reminder_tamu`
--
ALTER TABLE `catatan_reminder_tamu`
  ADD CONSTRAINT `fk_catatan_reminder_tamu_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dokumen`
--
ALTER TABLE `dokumen`
  ADD CONSTRAINT `fk_dokumen_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_dokumen` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `peminjaman_aset`
--
ALTER TABLE `peminjaman_aset`
  ADD CONSTRAINT `fk_peminjaman_aset` FOREIGN KEY (`aset_id`) REFERENCES `aset` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_peminjaman_diproses_oleh` FOREIGN KEY (`diproses_oleh_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `rapat_action_items`
--
ALTER TABLE `rapat_action_items`
  ADD CONSTRAINT `fk_rai_rapat` FOREIGN KEY (`id_rapat`) REFERENCES `rapat` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rapat_dokumen`
--
ALTER TABLE `rapat_dokumen`
  ADD CONSTRAINT `fk_rd_rapat` FOREIGN KEY (`id_rapat`) REFERENCES `rapat` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rapat_log_aktivitas`
--
ALTER TABLE `rapat_log_aktivitas`
  ADD CONSTRAINT `fk_rla_rapat` FOREIGN KEY (`id_rapat`) REFERENCES `rapat` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rapat_topik`
--
ALTER TABLE `rapat_topik`
  ADD CONSTRAINT `fk_rt_rapat` FOREIGN KEY (`id_rapat`) REFERENCES `rapat` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `riwayat_pengiriman_surat_keluar`
--
ALTER TABLE `riwayat_pengiriman_surat_keluar`
  ADD CONSTRAINT `fk_riwayat_surat_keluar` FOREIGN KEY (`surat_keluar_id`) REFERENCES `surat_keluar` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tamu`
--
ALTER TABLE `tamu`
  ADD CONSTRAINT `fk_tamu_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
