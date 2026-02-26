-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 26, 2026 at 02:12 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_sistem_honor_udinus`
--

-- --------------------------------------------------------

--
-- Table structure for table `t_jadwal`
--

CREATE TABLE `t_jadwal` (
  `id_jdwl` int NOT NULL,
  `semester` varchar(5) COLLATE utf8mb4_general_ci NOT NULL,
  `kode_matkul` varchar(7) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_matkul` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `id_user` int NOT NULL,
  `jml_mhs` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `t_jadwal`
--

INSERT INTO `t_jadwal` (`id_jdwl`, `semester`, `kode_matkul`, `nama_matkul`, `id_user`, `jml_mhs`) VALUES
(2, '20262', 'SI101', 'Algoritma pemrograman', 1, 3),
(3, '20261', 'SI200', 'Informatika', 99, 36),
(5, '20241', 'SI101', 'Algoritma dan Pemrograman', 124, 40),
(6, '20242', 'SI102', 'Struktur Data', 125, 30),
(7, '20251', 'SI201', 'Basis Data', 126, 40),
(8, '20252', 'SI202', 'Pemrograman Web', 127, 28),
(9, '20261', 'SI245', 'Sains Data', 126, 6),
(10, '20281', 'SI101', 'Algoritma', 131, 30),
(11, '20241', 'SI106', 'Algoritma dan Pemrograman', 144, 40),
(12, '20252', 'SI102', 'HM', 152, 19),
(13, '20241', 'SI103', 'jhgjgkj', 151, 50),
(14, '20282', 'SI103', 'yaaa', 146, 20);

-- --------------------------------------------------------

--
-- Table structure for table `t_panitia`
--

CREATE TABLE `t_panitia` (
  `id_pnt` int NOT NULL,
  `jbtn_pnt` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `honor_std` int NOT NULL,
  `honor_p1` int DEFAULT NULL,
  `honor_p2` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `t_panitia`
--

INSERT INTO `t_panitia` (`id_pnt`, `jbtn_pnt`, `honor_std`, `honor_p1`, `honor_p2`) VALUES
(9, 'ketua acara', 10000, 0, 0),
(17, 'Sekretaris', 8000, 0, 0),
(18, 'Bendahara', 8000, 0, 0),
(19, 'Anggota', 5000, 0, 0),
(21, 'Ketua panitia', 750000, 150000, 100000),
(22, 'Ketchua panitia', 500000, 40000, 40000),
(24, 'Ketua Panitia2', 980000, 670000, 800000),
(25, 'Hm', 40000, 78000, 400000),
(26, 'Ya', 399990, 309000, 300000);

-- --------------------------------------------------------

--
-- Table structure for table `t_transaksi_honor_dosen`
--

CREATE TABLE `t_transaksi_honor_dosen` (
  `id_thd` int NOT NULL,
  `semester` varchar(5) COLLATE utf8mb4_general_ci NOT NULL,
  `bulan` enum('januari','februari','maret','april','mei','juni','juli','agustus','september','oktober','november','desember') COLLATE utf8mb4_general_ci NOT NULL,
  `id_jadwal` int NOT NULL,
  `jml_tm` int NOT NULL,
  `sks_tempuh` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `t_transaksi_honor_dosen`
--

INSERT INTO `t_transaksi_honor_dosen` (`id_thd`, `semester`, `bulan`, `id_jadwal`, `jml_tm`, `sks_tempuh`) VALUES
(23, '20261', 'maret', 8, 10, 11),
(24, '20262', 'januari', 2, 17, 3),
(25, '20261', 'juli', 3, 4, 5),
(26, '20251', 'agustus', 7, 14, 3),
(27, '20242', 'januari', 6, 14, 3),
(28, '20241', 'juli', 5, 50, 40),
(29, '20251', 'november', 7, 14, 3),
(30, '20241', 'desember', 5, 30, 20),
(31, '20241', 'desember', 11, 12, 12),
(32, '20241', 'desember', 13, 40, 70),
(33, '20251', 'juli', 7, 30, 20);

-- --------------------------------------------------------

--
-- Table structure for table `t_transaksi_pa_ta`
--

CREATE TABLE `t_transaksi_pa_ta` (
  `id_tpt` int NOT NULL,
  `semester` varchar(5) COLLATE utf8mb4_general_ci NOT NULL,
  `periode_wisuda` enum('januari','februari','maret','april','mei','juni','juli','agustus','september','oktober','november','desember') COLLATE utf8mb4_general_ci NOT NULL,
  `id_user` int NOT NULL,
  `id_panitia` int NOT NULL,
  `jml_mhs_prodi` int NOT NULL,
  `jml_mhs_bimbingan` int NOT NULL,
  `prodi` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `jml_pgji_1` int NOT NULL,
  `jml_pgji_2` int NOT NULL,
  `ketua_pgji` varchar(30) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `t_transaksi_pa_ta`
--

INSERT INTO `t_transaksi_pa_ta` (`id_tpt`, `semester`, `periode_wisuda`, `id_user`, `id_panitia`, `jml_mhs_prodi`, `jml_mhs_bimbingan`, `prodi`, `jml_pgji_1`, `jml_pgji_2`, `ketua_pgji`) VALUES
(6, '20251', 'maret', 99, 9, 3, 5, 'informatika', 4, 2, 'kiyak'),
(8, '20242', 'september', 3, 9, 2, 2, 'kesehatan', 2, 3, 'ya'),
(15, '20262', 'agustus', 125, 19, 3, 2, 'Teknik Mesin', 2, 3, 'Az'),
(16, '20241', 'november', 99, 19, 0, 10, '50', 2, 2, 'Dr. Ahmad, M.Kom'),
(17, '20242', 'maret', 124, 22, 7, 9, 'SI', 6, 3, 'olaf'),
(18, '20252', 'februari', 125, 18, 9, 8, 'SI', 5, 0, 'nkjkkj'),
(19, '20241', 'november', 1, 19, 0, 10, '50', 2, 2, 'Dr. Ahmad, M.Kom'),
(20, '20241', 'juli', 3, 9, 40, 30, 'TI', 30, 20, 'sskdj'),
(21, '20252', 'februari', 2, 25, 20, 40, 'MI', 30, 20, 'skjdkfkjkfrejgfik');

-- --------------------------------------------------------

--
-- Table structure for table `t_transaksi_ujian`
--

CREATE TABLE `t_transaksi_ujian` (
  `id_tu` int NOT NULL,
  `semester` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `id_panitia` int NOT NULL,
  `id_user` int NOT NULL,
  `jml_mhs_prodi` int NOT NULL,
  `jml_mhs` int NOT NULL,
  `jml_koreksi` int NOT NULL,
  `jml_matkul` int NOT NULL,
  `jml_pgws_pagi` int NOT NULL,
  `jml_pgws_sore` int NOT NULL,
  `jml_koor_pagi` int NOT NULL,
  `jml_koor_sore` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `t_transaksi_ujian`
--

INSERT INTO `t_transaksi_ujian` (`id_tu`, `semester`, `id_panitia`, `id_user`, `jml_mhs_prodi`, `jml_mhs`, `jml_koreksi`, `jml_matkul`, `jml_pgws_pagi`, `jml_pgws_sore`, `jml_koor_pagi`, `jml_koor_sore`) VALUES
(3, '20251', 9, 1, 5, 5, 5, 5, 5, 5, 5, 5),
(8, '20262', 17, 126, 2, 5, 4, 4, 3, 2, 3, 3),
(9, '20251', 17, 125, 5, 5, 5, 5, 5, 5, 5, 5),
(10, '20252', 18, 99, 3, 4, 5, 3, 3, 4, 5, 7),
(11, '20241', 18, 99, 50, 45, 45, 3, 6, 4, 2, 2),
(12, '20241', 22, 131, 12, 50, 50, 4, 2, 3, 4, 3),
(13, '20241', 18, 149, 20, 20, 20, 3, 6, 4, 2, 2),
(14, '20251', 25, 151, 20, 30, 70, 50, 50, 30, 40, 30),
(15, '20261', 9, 152, 1200, 30, 40, 50, 40, 30, 30, 20);

-- --------------------------------------------------------

--
-- Table structure for table `t_user`
--

CREATE TABLE `t_user` (
  `id_user` int NOT NULL,
  `npp_user` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `nik_user` char(16) COLLATE utf8mb4_general_ci NOT NULL,
  `npwp_user` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `norek_user` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_user` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `nohp_user` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `pw_user` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role_user` enum('koordinator','admin','staff') COLLATE utf8mb4_general_ci NOT NULL,
  `honor_persks` int NOT NULL,
  `remember_token` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `t_user`
--

INSERT INTO `t_user` (`id_user`, `npp_user`, `nik_user`, `npwp_user`, `norek_user`, `nama_user`, `nohp_user`, `pw_user`, `role_user`, `honor_persks`, `remember_token`) VALUES
(1, '0686.11.1995.071', '3374010101950001', '12.345.678.9-012.000', '1234567890', 'Dr. Andi Prasetyo, M.Kom', '081234567890', '$2y$10$RDLbekBlwDetSCFcQPruf.nJr2Z6rLDxw3kJf/cVg.ywOynlYm.ry', 'staff', 500000, NULL),
(2, '0721.12.1998.034', '3374010202980002', '23.456.789.0-123.000', '1410002345678', 'Siti Rahmawati, M.T', '081234567802', '$2y$10$/BkQAaJ1bw9lkxcDOzDd9eSa6AClBvvyR35Gs0gLNEuilpuazcSZO', 'staff', 0, NULL),
(3, '0815.10.2001.112', '3374010303010003', '34.567.890.1-234.000', '1410003456789', 'Budi Santoso, S.Kom', '081234567803', '$2y$10$ptdJFN18H5.rbrgNfL8d.uD5bZPLgimtnceE3J/H6O3lHAdP3thr6', 'staff', 0, NULL),
(99, '0686.11.1995.222', '1111111111111111', '12.335.678.9-012.134', '141000123425', 'Azkiya, S.Kom', '0882005337277', '$2y$10$8Fz.xsh5Jtv.ApBGdTm7YeeX1hF401W9mZXH49Ir6kymdelIxzxuC', 'koordinator', 0, NULL),
(124, '1145.02.1988.090', '3273011212880001', '67.890.123.4-567.000', '1410009876543', 'Rina Wijaya S.T', '081345678910', '$2y$10$XPWJjFx7puJEG85CsB1TY.90fGPvgclIF2R8Lkgz42EMLBNJ/juPm', 'staff', 0, NULL),
(125, '1256.04.1992.012', '3171021505920005', '78.901.234.5-678.000', '1410008765432', 'Ahmad Fauzi M.Pd', '081345678911', '$2y$10$vL4ebftOJv7iqUZQ5Z.kF.G4SAdGoYGhYiSK0R8/Cbm6yn8MWplQ2', 'staff', 0, NULL),
(126, '1367.06.1996.034', '3578032010960002', '89.012.345.6-789.000', '1410007654321', 'Linda Permata M.Ak', '081345678912', '$2y$10$ux60z8e1zLvhBIjxzS8yDONOsxK/C9Hq.5VesaLTcY.vwKBuybcGK', 'staff', 0, NULL),
(127, '1478.08.1980.056', '3374042508800008', '90.123.456.7-890.000', '1410006543210', 'Dr. Hendra Kusuma', '081345678913', '$2y$10$YbyG/uaUxmOnz/FijzBne.Xz86/3KJToWgaAMQOkYQNl7qPhOvQ36', 'staff', 0, NULL),
(128, '1589.10.1994.078', '5171053012940003', '01.234.567.8-901.000', '1410005432109', 'Maya Sartika S.Psi', '081345678914', '$2y$10$d2cVNvFLxu7mPqaVlKqW0uuP9UP9orv1EFEq9oVisaYRVEFZVrIMu', 'staff', 0, NULL),
(131, '0686.11.1995.072', '3374010101950001', '12.345.678.9-012.000', '1234567890', 'Dr. abc, M.Kom', '081234567890', '$2y$10$ZEO14KTp9UchxMLZQQObiOOAzKTyZboQDnJtvjINMo2uwNHW45ME2', 'staff', 500000, NULL),
(132, '3678.65.5668.985', '1234567890112345', '13.654.534.9-056.000', '1345678654324', 'Dr. Andi Prasetyo, M.Kom', '081234567890', '$2y$10$pDRDIjFgchYYlw1zjZYnEO/WVlwW7OUBP4HtANZCREgirQBB/iaw2', 'staff', 5000, NULL),
(134, '2001.01.1990.001', '3374010101900001', '11.111.111.1-111.000', '1410000000001', 'Admin 2', '081900000001', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'admin', 50, NULL),
(135, '2001.01.1990.002', '3374010101900002', '11.111.111.1-112.000', '1410000000002', 'Admin 3', '081900000002', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'admin', 50, NULL),
(136, '2001.01.1990.003', '3374010101900003', '11.111.111.1-113.000', '1410000000003', 'Admin 4', '081900000003', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'admin', 50, NULL),
(137, '2001.01.1990.004', '3374010101900004', '11.111.111.1-114.000', '1410000000004', 'Admin 5', '081900000004', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'admin', 50, NULL),
(138, '2001.01.1990.005', '3374010101900005', '11.111.111.1-115.000', '1410000000005', 'Admin 6', '081900000005', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'admin', 50, NULL),
(139, '2001.01.1990.006', '3374010101900006', '11.111.111.1-116.000', '1410000000006', 'Admin 7', '081900000006', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'admin', 50, NULL),
(140, '2001.01.1990.007', '3374010101900007', '11.111.111.1-117.000', '1410000000007', 'Admin 8', '081900000007', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'admin', 50, NULL),
(141, '2001.01.1990.008', '3374010101900008', '11.111.111.1-118.000', '1410000000008', 'Admin 9', '081900000008', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'admin', 50, NULL),
(142, '2001.01.1990.009', '3374010101900009', '11.111.111.1-119.000', '1410000000009', 'Admin 10', '081900000009', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'admin', 60, NULL),
(143, '2001.01.1990.010', '3374010101900010', '11.111.111.1-120.000', '1410000000010', 'Admin 11', '081900000010', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'admin', 50, NULL),
(144, '2001.01.1990.011', '3374010101900011', '11.111.111.1-121.000', '1410000000011', 'Admin 12', '081900000011', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'admin', 50, NULL),
(145, '2001.01.1990.012', '3374010101900012', '11.111.111.1-122.000', '1410000000012', 'Admin 13', '081900000012', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'admin', 50, NULL),
(146, '2001.01.1990.013', '3374010101900013', '11.111.111.1-123.000', '1410000000013', 'Admin 14', '081900000013', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'admin', 50, NULL),
(147, '2001.01.1990.014', '3374010101900014', '11.111.111.1-124.000', '1410000000014', 'Admin 15', '081900000014', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'admin', 50, NULL),
(148, '2001.01.1990.015', '3374010101900015', '11.111.111.1-125.000', '1410000000015', 'Admin 16', '081900000015', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'admin', 50, NULL),
(149, '2001.01.1990.016', '3374010101900016', '11.111.111.1-126.000', '1410000000016', 'Admin 17', '081900000016', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'admin', 50, NULL),
(150, '2001.01.1990.017', '3374010101900017', '11.111.111.1-127.000', '1410000000017', 'Admin 18', '081900000017', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'admin', 50, NULL),
(151, '2001.01.1990.018', '3374010101900018', '11.111.111.1-128.000', '1410000000018', 'Admin 19', '081900000018', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'admin', 50, NULL),
(152, '2001.01.1990.019', '3374010101900019', '11.111.111.1-129.000', '1410000000019', 'Admin 20', '081900000019', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'admin', 50, NULL),
(153, '3001.01.1991.001', '3374010101910001', '21.111.111.1-111.000', '1420000000001', 'Koordinator 2', '082100000001', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'koordinator', 0, NULL),
(154, '3001.01.1991.002', '3374010101910002', '21.111.111.1-112.000', '1420000000002', 'Koordinator 3', '082100000002', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'koordinator', 0, NULL),
(155, '3001.01.1991.003', '3374010101910003', '21.111.111.1-113.000', '1420000000003', 'Koordinator 4', '082100000003', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'koordinator', 0, NULL),
(156, '3001.01.1991.004', '3374010101910004', '21.111.111.1-114.000', '1420000000004', 'Koordinator 5', '082100000004', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'koordinator', 0, NULL),
(157, '3001.01.1991.005', '3374010101910005', '21.111.111.1-115.000', '1420000000005', 'Koordinator 6', '082100000005', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'koordinator', 0, NULL),
(158, '3001.01.1991.006', '3374010101910006', '21.111.111.1-116.000', '1420000000006', 'Koordinator 7', '082100000006', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'koordinator', 0, NULL),
(159, '3001.01.1991.007', '3374010101910007', '21.111.111.1-117.000', '1420000000007', 'Koordinator 8', '082100000007', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'koordinator', 0, NULL),
(161, '3001.01.1991.009', '3374010101910009', '21.111.111.1-119.000', '1420000000009', 'Koordinator 10', '082100000009', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'koordinator', 0, NULL),
(162, '3001.01.1991.010', '3374010101910010', '21.111.111.1-120.000', '1420000000010', 'Koordinator 11', '082100000010', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'koordinator', 0, NULL),
(163, '3001.01.1991.011', '3374010101910011', '21.111.111.1-121.000', '1420000000011', 'Koordinator 12', '082100000011', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'koordinator', 0, NULL),
(164, '3001.01.1991.012', '3374010101910012', '21.111.111.1-122.000', '1420000000012', 'Koordinator 13', '082100000012', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'koordinator', 0, NULL),
(165, '3001.01.1991.013', '3374010101910013', '21.111.111.1-123.000', '1420000000013', 'Koordinator 14', '082100000013', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'koordinator', 0, NULL),
(166, '3001.01.1991.014', '3374010101910014', '21.111.111.1-124.000', '1420000000014', 'Koordinator 15', '082100000014', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'koordinator', 0, NULL),
(167, '3001.01.1991.015', '3374010101910015', '21.111.111.1-125.000', '1420000000015', 'Koordinator 16', '082100000015', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'koordinator', 0, NULL),
(168, '3001.01.1991.016', '3374010101910016', '21.111.111.1-126.000', '1420000000016', 'Koordinator 17', '082100000016', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'koordinator', 0, NULL),
(169, '3001.01.1991.017', '3374010101910017', '21.111.111.1-127.000', '1420000000017', 'Koordinator 18', '082100000017', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'koordinator', 0, NULL),
(170, '3001.01.1991.018', '3374010101910018', '21.111.111.1-128.000', '1420000000018', 'Koordinator 19', '082100000018', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'koordinator', 0, NULL),
(171, '3001.01.1991.019', '3374010101910019', '21.111.111.1-129.000', '1420000000019', 'Koordinator 20', '082100000019', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'koordinator', 0, NULL),
(172, '4001.01.1992.001', '3374010101920001', '31.111.111.1-111.000', '1430000000001', 'Staff 10', '083100000001', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'staff', 0, NULL),
(173, '4001.01.1992.002', '3374010101920002', '31.111.111.1-112.000', '1430000000002', 'Staff 11', '083100000002', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'staff', 0, NULL),
(174, '4001.01.1992.003', '3374010101920003', '31.111.111.1-113.000', '1430000000003', 'Staff 12', '083100000003', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'staff', 0, NULL),
(175, '4001.01.1992.004', '3374010101920004', '31.111.111.1-114.000', '1430000000004', 'Staff 13', '083100000004', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'staff', 0, NULL),
(176, '4001.01.1992.005', '3374010101920005', '31.111.111.1-115.000', '1430000000005', 'Staff 14', '083100000005', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'staff', 0, NULL),
(177, '4001.01.1992.006', '3374010101920006', '31.111.111.1-116.000', '1430000000006', 'Staff 15', '083100000006', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'staff', 0, NULL),
(178, '4001.01.1992.007', '3374010101920007', '31.111.111.1-117.000', '1430000000007', 'Staff 16', '083100000007', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'staff', 0, NULL),
(179, '4001.01.1992.008', '3374010101920008', '31.111.111.1-118.000', '1430000000008', 'Staff 17', '083100000008', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'staff', 0, NULL),
(180, '4001.01.1992.009', '3374010101920009', '31.111.111.1-119.000', '1430000000009', 'Staff 18', '083100000009', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'staff', 0, NULL),
(181, '4001.01.1992.010', '3374010101920010', '31.111.111.1-120.000', '1430000000010', 'Staff 19', '083100000010', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'staff', 0, NULL),
(182, '4001.01.1992.011', '3374010101920011', '31.111.111.1-121.000', '1430000000011', 'Staff 20', '083100000011', '$2y$10$7EqJtq98hPqEX7fNZaFWoO.H2p7k7QJ1e9FvZ0xYyXWfYz8VvZ1yW', 'staff', 0, NULL),
(183, '0686.11.1995.332', '3374010101950001', '12.222.678.9-012.000', '1410001234567', 'hefkjhkfjh', '081234567801', 'aefdd8cd640f18d3fd71b04182d56d05', 'koordinator', 40, NULL),
(185, '0686.11.1995.078', '3374010101950001', '12.345.678.9-012.000', '1234567890', 'Dr.Succy, M.Kom', '081234567890', '$2y$10$ZTI2x2eDlAqIfIDUyY7hUuxqRp9gVF6wX6NPlIN1pSSyZ9HK7xVRe', 'staff', 900000, NULL),
(186, '9047.45.4985.985', '4536746894948374', '12.345.546.8-738.000', '1410001234567', 'Dr. hmzz', '081234567890', '$2y$10$RUqWH7aXRi.HYyOXg9GEq..gv.cx9mjyqr5VBz8Bo.8IglR7Ljzcm', 'koordinator', 400000, NULL),
(187, '9227.45.4985.987', '1234567891123456', '12.345.546.8-738.222', '1410001234567', 'dr. sucik', '081234567890', '$2y$10$RPuBdeAy5aiwxtT3uNtxfuQKfCkxVwUeFR0JoOpEBU19CNcaO5Cc.', 'koordinator', 900000, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `t_jadwal`
--
ALTER TABLE `t_jadwal`
  ADD PRIMARY KEY (`id_jdwl`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `t_panitia`
--
ALTER TABLE `t_panitia`
  ADD PRIMARY KEY (`id_pnt`) USING BTREE;

--
-- Indexes for table `t_transaksi_honor_dosen`
--
ALTER TABLE `t_transaksi_honor_dosen`
  ADD PRIMARY KEY (`id_thd`),
  ADD KEY `id_jadwal` (`id_jadwal`);

--
-- Indexes for table `t_transaksi_pa_ta`
--
ALTER TABLE `t_transaksi_pa_ta`
  ADD PRIMARY KEY (`id_tpt`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_panitia` (`id_panitia`);

--
-- Indexes for table `t_transaksi_ujian`
--
ALTER TABLE `t_transaksi_ujian`
  ADD PRIMARY KEY (`id_tu`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_panitia` (`id_panitia`);

--
-- Indexes for table `t_user`
--
ALTER TABLE `t_user`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `t_jadwal`
--
ALTER TABLE `t_jadwal`
  MODIFY `id_jdwl` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `t_panitia`
--
ALTER TABLE `t_panitia`
  MODIFY `id_pnt` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `t_transaksi_honor_dosen`
--
ALTER TABLE `t_transaksi_honor_dosen`
  MODIFY `id_thd` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `t_transaksi_pa_ta`
--
ALTER TABLE `t_transaksi_pa_ta`
  MODIFY `id_tpt` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `t_transaksi_ujian`
--
ALTER TABLE `t_transaksi_ujian`
  MODIFY `id_tu` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `t_user`
--
ALTER TABLE `t_user`
  MODIFY `id_user` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=191;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `t_jadwal`
--
ALTER TABLE `t_jadwal`
  ADD CONSTRAINT `t_jadwal_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `t_user` (`id_user`);

--
-- Constraints for table `t_transaksi_honor_dosen`
--
ALTER TABLE `t_transaksi_honor_dosen`
  ADD CONSTRAINT `t_transaksi_honor_dosen_ibfk_1` FOREIGN KEY (`id_jadwal`) REFERENCES `t_jadwal` (`id_jdwl`);

--
-- Constraints for table `t_transaksi_pa_ta`
--
ALTER TABLE `t_transaksi_pa_ta`
  ADD CONSTRAINT `t_transaksi_pa_ta_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `t_user` (`id_user`),
  ADD CONSTRAINT `t_transaksi_pa_ta_ibfk_3` FOREIGN KEY (`id_panitia`) REFERENCES `t_panitia` (`id_pnt`);

--
-- Constraints for table `t_transaksi_ujian`
--
ALTER TABLE `t_transaksi_ujian`
  ADD CONSTRAINT `t_transaksi_ujian_ibfk_3` FOREIGN KEY (`id_user`) REFERENCES `t_user` (`id_user`),
  ADD CONSTRAINT `t_transaksi_ujian_ibfk_4` FOREIGN KEY (`id_panitia`) REFERENCES `t_panitia` (`id_pnt`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
