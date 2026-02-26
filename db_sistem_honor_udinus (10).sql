-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 29, 2026 at 02:28 AM
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
(1, '20242', 'SI101', 'Algoritma pemrograman', 98, 20),
(2, '20242', 'SI101', 'Algoritma pemrograman', 1, 3),
(3, '20241', 'TI201', 'algoritma', 98, 4);

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
(6, 'Kethcua Panitia', 400000, 70000, 300000);

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
(1, '20242', 'februari', 1, 14, 3),
(2, '20241', 'mei', 1, 8, 2),
(3, '20242', 'maret', 1, 12, 3),
(4, '20242', 'april', 1, 16, 3),
(5, '20242', 'september', 1, 5, 6),
(6, '20242', 'mei', 3, 3, 4),
(7, '20241', 'januari', 1, 4, 3),
(8, '20241', 'februari', 1, 4, 3);

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
  `prodi` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `jml_pgji_1` int NOT NULL,
  `jml_pgji_2` int NOT NULL,
  `ketua_pgji` varchar(30) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `t_transaksi_pa_ta`
--

INSERT INTO `t_transaksi_pa_ta` (`id_tpt`, `semester`, `periode_wisuda`, `id_user`, `id_panitia`, `jml_mhs_prodi`, `jml_mhs_bimbingan`, `prodi`, `jml_pgji_1`, `jml_pgji_2`, `ketua_pgji`) VALUES
(1, '20252', 'februari', 98, 6, 3, 5, 'TI', 3, 5, 'Suci');

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
(2, '20242', 6, 98, 5, 19, 4, 6, 2, 3, 3, 6);

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
  `pw_user` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `role_user` enum('koordinator','admin','staff') COLLATE utf8mb4_general_ci NOT NULL,
  `honor_persks` int NOT NULL,
  `remember_token` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `t_user`
--

INSERT INTO `t_user` (`id_user`, `npp_user`, `nik_user`, `npwp_user`, `norek_user`, `nama_user`, `nohp_user`, `pw_user`, `role_user`, `honor_persks`, `remember_token`) VALUES
(1, '0686.11.1995.071', '3374010101950001', '12.345.678.9-012.000', '1410001234567', 'Dr. Andi Prasetyo, M.Kom', '081234567801', 'f006bbb5314696993ac4b77c9eabc7e1', 'admin', 0, NULL),
(2, '0721.12.1998.034', '3374010202980002', '23.456.789.0-123.000', '1410002345678', 'Siti Rahmawati, M.T', '081234567802', '740b977c51fa5bfd17f8f23f809ee6d5', 'koordinator', 0, NULL),
(3, '0815.10.2001.112', '3374010303010003', '34.567.890.1-234.000', ' 1410003456789', ' Budi Santoso, S.Kom ', ' 081234567803', 'd9dcc4af188a7a70fbd1d0294223cb06', 'staff', 0, 'c013f0511c3f7952c73d1f52da5fde7b7980a1edfe274c16e266f2c4298a16c2'),
(98, '0987.98.0986.098', '2274230101850001', '14.325.478.9-013.000', '1210601234568', 'Prof Drs Suci M.Kom', '081264587802', 'e476ce6844ec97c5f7cf20672e6404b8', 'admin', 0, NULL);

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
  MODIFY `id_jdwl` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `t_panitia`
--
ALTER TABLE `t_panitia`
  MODIFY `id_pnt` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `t_transaksi_honor_dosen`
--
ALTER TABLE `t_transaksi_honor_dosen`
  MODIFY `id_thd` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `t_transaksi_pa_ta`
--
ALTER TABLE `t_transaksi_pa_ta`
  MODIFY `id_tpt` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `t_transaksi_ujian`
--
ALTER TABLE `t_transaksi_ujian`
  MODIFY `id_tu` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `t_user`
--
ALTER TABLE `t_user`
  MODIFY `id_user` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

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
