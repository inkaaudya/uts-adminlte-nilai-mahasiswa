-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 25, 2026 at 06:21 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_nilai5a`
--

-- --------------------------------------------------------

--
-- Table structure for table `nilaimhsw`
--

CREATE TABLE `nilaimhsw` (
  `nim` char(10) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `namamhsw` varchar(30) NOT NULL,
  `nilaiakhir` float NOT NULL,
  `nilaihuruf` char(1) NOT NULL,
  `predikat` varchar(20) NOT NULL,
  `created_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `nilaimhsw`
--

INSERT INTO `nilaimhsw` (`nim`, `foto`, `namamhsw`, `nilaiakhir`, `nilaihuruf`, `predikat`, `created_date`, `modified_date`) VALUES
('3202416001', '', 'Aril Apriandi Wibowo', 72.77, 'B', 'Memuaskan', '2026-10-25 18:05:04', '2026-10-25 18:05:04'),
('3202416005', 'uploads/mahasiswa/4cff620f3219d11d1c381ad26d3677ed.jpg', 'Achmad Ardian Mulyadi', 90.94, 'A', 'Sangat Memuaskan', '2026-10-12 04:43:54', '2026-10-12 04:43:54'),
('3202416006', 'uploads/mahasiswa/416508051a073df3b45ef29299292d58.jpg', 'Aditya Saputra', 73.22, 'B', 'Memuaskan', '2026-10-25 17:58:03', '2026-10-25 17:58:03'),
('3202416008', 'uploads/mahasiswa/ec7ab41ba9114cb81ea91ba945a6efc4.jpg', 'Nurlaili Surya Raja Murti', 92.75, 'A', 'Sangat Memuaskan', '2026-10-12 04:50:37', '2026-10-12 04:50:37'),
('3202416009', 'uploads/mahasiswa/00492ab3f4a5f14e694547507f36b564.jpg', 'Muhammad Aqil', 70.77, 'B', 'Memuaskan', '2026-10-25 12:52:04', '2026-10-25 12:52:04'),
('3202416010', 'uploads/mahasiswa/e8c7e304fd0c95f0ec1ed1d6d4bd70b3.jpg', 'Inka Audya Miranti', 98.42, 'A', 'Sangat Memuaskan', '2026-10-24 16:56:07', '2026-10-24 16:56:07'),
('3202416011', 'uploads/mahasiswa/08d4a463e09b73a551c8f643190eec5d.jpg', 'Muhammad Halim Arafat', 84.3, 'A', 'Sangat Memuaskan', '2026-10-24 17:14:16', '2026-10-24 17:14:16'),
('3202416012', 'uploads/mahasiswa/6762bccf2ac9bf072248ae19f17dddfd.jpg', 'Fatimah Martin', 79.15, 'B', 'Memuaskan', '2026-10-24 16:59:54', '2026-10-24 16:59:54'),
('3202416021', 'uploads/mahasiswa/fe3362c0ffa86ebf1fae0604ccdd168c.jpg', 'Nanda Oktaviano', 82.04, 'A', 'Sangat Memuaskan', '2026-10-24 17:03:58', '2026-10-24 17:03:58'),
('3202416044', 'uploads/mahasiswa/fe1eb10e5f89739e4c7fd6476f59d92a.jpg', 'Hikmal Risqi Rahman Fauzi', 78.98, 'B', 'Memuaskan', '2026-10-12 04:11:15', '2026-10-12 04:11:15'),
('3202416055', 'uploads/mahasiswa/bc555395c71f6d0781ef2f4e8e312f1c.jpg', 'Ika Arynita', 81.5, 'A', 'Sangat Memuaskan', '2026-10-25 17:57:26', '2026-10-25 17:57:26'),
('3202416068', 'uploads/mahasiswa/30a3137feaf97ca995846b8b131ad740.jpg', 'Sabrina Utami', 96.77, 'A', 'Sangat Memuaskan', '2026-10-12 04:10:26', '2026-10-12 04:10:26'),
('3202416075', 'uploads/mahasiswa/7d702c1b37126deb93d466280e4d957b.jpg', 'Rizq Ariiq Pratama', 70.5, 'B', 'Memuaskan', '2026-10-25 18:03:15', '2026-10-25 18:03:15'),
('3202416077', 'uploads/mahasiswa/7d3ae4e99ab99e62224bca1f97ed0c75.jpg', 'Rais Asa Madani', 58.5, 'C', 'Cukup', '2026-10-25 16:46:58', '2026-10-25 16:46:58'),
('3202416078', 'uploads/mahasiswa/c147a7633f74a59428e87a050937a28f.jpg', 'Gabriel Distra Kireizuka', 62.4, 'C', 'Cukup', '2026-10-25 17:58:40', '2026-10-25 17:58:40'),
('3202416085', 'uploads/mahasiswa/99a8a6bda6c739e1326c9c37bfaf1152.jpg', 'Freky Ardiansyah', 75.77, 'B', 'Memuaskan', '2026-10-25 17:40:52', '2026-10-25 17:40:52'),
('3202416091', '', 'Restu Wardhana Putra', 65.44, 'C', 'Cukup', '2026-10-25 18:06:49', '2026-10-25 18:06:49'),
('3202416098', 'uploads/mahasiswa/9d58fc1ccbdf7922eed879b718ada923.jpg', 'Monica Fatwa Mauliddina', 80.65, 'A', 'Sangat Memuaskan', '2026-10-24 16:28:19', '2026-10-24 16:28:19'),
('3202416099', 'uploads/mahasiswa/61e856d753583394f3a7e1337877d365.jpg', 'Muhammad Fathi Khadafi', 86.4, 'A', 'Sangat Memuaskan', '2026-10-25 12:53:23', '2026-10-25 12:53:23'),
('3202416455', '', 'Muhammad Nabil', 64.9, 'C', 'Cukup', '2026-10-25 18:08:38', '2026-10-25 18:08:38'),
('3202416789', '', 'Ilham Saputra', 70.44, 'B', 'Memuaskan', '2026-10-25 18:04:23', '2026-10-25 18:04:23'),
('3202416888', '', 'Nana Example Tidak Lulus', 0, 'E', 'Tidak Lulus', '2026-10-25 18:16:29', '2026-10-25 18:16:29'),
('3202416899', NULL, 'Muhammad Fikri hasani', 50.21, 'D', 'Kurang', '2026-10-25 12:50:52', '2026-10-25 12:50:52'),
('3202416987', NULL, 'Habil', 60.5, 'C', 'Cukup', '2026-10-24 16:45:44', '2026-10-24 16:45:44'),
('3202416999', '', 'Tono Example Tidak Lulus', 21.22, 'E', 'Tidak Lulus', '2026-10-25 18:09:23', '2026-10-25 18:09:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `nilaimhsw`
--
ALTER TABLE `nilaimhsw`
  ADD PRIMARY KEY (`nim`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
