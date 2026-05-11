-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 06, 2023 at 05:32 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.1.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pbantuan_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `owner`
--

CREATE TABLE `owner` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(100) NOT NULL,
  `idnumber` varchar(100) NOT NULL,
  `status` varchar(100) NOT NULL,
  `model` varchar(100) NOT NULL,
  `platenum` varchar(100) NOT NULL,
  `sticker` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staffcar`
--

CREATE TABLE `staffcar` (
  `staffid` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `gender` varchar(12) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `staffno` varchar(20) NOT NULL,
  `model` varchar(120) NOT NULL,
  `platenum` varchar(30) NOT NULL,
  `sticker` varchar(12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `studentcar`
--

CREATE TABLE `studentcar` (
  `studentid` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `gender` varchar(12) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `matric` varchar(12) NOT NULL,
  `model` varchar(120) NOT NULL,
  `platenum` varchar(30) NOT NULL,
  `sticker` varchar(12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `studentcar`
--

INSERT INTO `studentcar` (`studentid`, `name`, `gender`, `phone`, `matric`, `model`, `platenum`, `sticker`) VALUES
(2, 'IQMALIAH REZANA BINTI HAFIZ', 'Perempuan', '0135787916', '2020854318', 'TOYOTA MARK X', 'VIP3451', 'Tiada'),
(14, 'MUHAMMAD AKIF IRFAN BIN MD.SADON', 'Lelaki', '0197673917', '2020611506', 'HONDA CIVIC 2021', 'JRU3045', 'Ada');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `userid` int(11) NOT NULL,
  `email` varchar(200) NOT NULL,
  `password` varchar(200) NOT NULL,
  `name` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`userid`, `email`, `password`, `name`) VALUES
(1, 'admin@mail.com', '111', 'ADMIN'),
(4, 'akifirfan8@gmail.com', '111', 'MUHAMMAD AKIF IRFAN BIN MD.SADON'),
(6, 'zaimi3009@gmail.com', 'zaim05', 'MUHAMMAD ZAIM IRFAN BIN MOHD ZAMRI');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `owner`
--
ALTER TABLE `owner`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staffcar`
--
ALTER TABLE `staffcar`
  ADD PRIMARY KEY (`staffid`);

--
-- Indexes for table `studentcar`
--
ALTER TABLE `studentcar`
  ADD PRIMARY KEY (`studentid`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`userid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `owner`
--
ALTER TABLE `owner`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staffcar`
--
ALTER TABLE `staffcar`
  MODIFY `staffid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `studentcar`
--
ALTER TABLE `studentcar`
  MODIFY `studentid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `userid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
