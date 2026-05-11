-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 18, 2023 at 12:19 AM
-- Server version: 10.5.19-MariaDB-1:10.5.19+maria~ubu2004
-- PHP Version: 8.2.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `SistemParkir_db`
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
  `type` varchar(100) NOT NULL,
  `status` varchar(100) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `platenum` varchar(100) NOT NULL,
  `sticker` varchar(100) NOT NULL,
  `stickerno` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `owner`
--

INSERT INTO `owner` (`id`, `name`, `phone`, `idnumber`, `type`, `status`, `brand`, `platenum`, `sticker`, `stickerno`) VALUES
(2, 'MUHAMMAD ZAIM IRFAN BIN MOHD ZAMRI', '0147668227', '2020452222', 'Motosikal', 'Pelajar', 'LAIN-LAIN', 'JRL 7134', 'ADA', 'jv9808989'),
(3, 'MUHAMMAD AKIF IRFAN BIN MD SADON', '0197673917', '2020611506', 'Kereta', 'Pelajar', 'HONDA', 'JRU 3045', 'ADA', ''),
(4, 'IQMALIAH REZANA BINTI HAFIZ', '0135787916', '2020854318', 'Kereta', 'Pelajar', 'TOYOTA', 'VIP 3451', 'ADA', ''),
(7, 'ZARINA BINTI ABDOL WAHAP', '0127466612', '209380', 'KERETA', 'Staf', 'HONDA', 'JGR3818', 'ADA', 'JA0077'),
(8, 'MOHD FAROK BIN MUSTAJAB', '0177150632', '199021', 'MOTOSIKAL', 'Staf', 'YAMAHA', 'JDU1780', 'ADA', 'JA1554'),
(9, 'RINA BINTI SAMAD ROSDI', '0127490885', '239897', 'KERETA', 'Staf', 'HONDA', 'JKF6604', 'ADA', 'JA0011'),
(10, 'MOHD SAIFUL NIZAM BIN SARIDIN', '0137580343', '262466', 'MOTOSIKAL', 'Staf', 'YAMAHA', 'BPQ9342', 'ADA', 'JA1558'),
(11, 'NUR SYAFIKA BINTI MENHAD', '01118774704', '318611', 'KERETA', 'Staf', 'PERODUA', 'JQS9107', 'ADA', 'JA0007'),
(12, 'ZARINA BINTI ABDOL WAHAP', '0127466612', '209380', 'KERETA', 'Staf', 'PERODUA', 'JUR422', 'ADA', 'JA0022'),
(13, 'MOHD SAIFUL NIZAM BIN SARIDIN', '0137580343', '262466', 'KERETA', 'Staf', 'LAIN-LAIN', 'JVN637', 'ADA', 'JA0229'),
(14, 'BASYIRAH BINTI YUSOF', '0125925675', '223573', 'KERETA', 'Staf', 'PERODUA', 'WB5958K', 'ADA', 'JA0001'),
(15, 'MOHD KHIRUL IZAM BIN TUSIRAN', '0127476441', '230155', 'KERETA', 'Staf', 'PROTON', 'JQT4427', 'ADA', 'JA0002'),
(16, 'MOHAMAD ISAHRUDDIN BIN AMIRUDDIN', '0197166427', '227414', 'MOTOSIKAL', 'Staf', 'YAMAHA', 'JRC5474', 'ADA', 'JA1612'),
(17, 'MOHD SAIFUL NIZAM BIN SARIDIN', '0137580343', '262466', 'KERETA', 'Staf', 'PERODUA', 'JVB6729', 'ADA', 'JA0229'),
(18, 'MOHD KHIRUL IZAM BIN TUSIRAN', '0127476441', '230155', 'MOTOSIKAL', 'Staf', 'YAMAHA', 'JRC4427', 'ADA', 'JA1548'),
(19, 'RAFIUDDIN BIN MOHD YUSOF', '0132388381', '296432', 'MOTOSIKAL', 'Staf', 'YAMAHA', 'RAQ907', 'ADA', 'JA1625'),
(20, 'ZURAIDAH BINTI SUMERY', '0197317943', '263012', 'KERETA', 'Staf', 'TOYOTA', 'WRL8915', 'ADA', 'JA0470'),
(21, 'JUHARIE BIN JEMAIN', '0177017504', '184502', 'KERETA', 'Staf', 'LAIN-LAIN', 'WCD3430', 'ADA', 'JA0499'),
(22, 'RAFIUDDIN BIN MOHD YUSOF', '0132388381', '296432', 'KERETA', 'Staf', 'PROTON', 'NDG4761', 'ADA', 'JA0520'),
(23, 'JUHARIE BIN JEMAIN', '0177017504', '184502', 'KERETA', 'Staf', 'PERODUA', 'NAM4883', 'ADA', 'JA0502'),
(24, 'ZURAIDAH BINTI SUMERY', '0197317943', '263012', 'KERETA', 'Staf', 'LAIN-LAIN', 'WVM9669', 'ADA', 'JA0469'),
(25, 'MUHAMAD SUHAIMI BIN SULAIMAN', '0107702959', '337537', 'KERETA', 'Staf', 'PERODUA', 'JRY9768', 'ADA', 'JA0514'),
(26, 'MIMI HASLIAH BINTI MOHD SHAHARI', '0127375091', '159650', 'KERETA', 'Staf', 'PERODUA', 'WTX4390', 'ADA', 'JA0417'),
(27, 'MOHAMMAD ASHRAF BIN ABU BAKAR', '01110868159', '228471', 'KERETA', 'Staf', 'PERODUA', 'JKF3824', 'ADA', 'JA0039'),
(28, 'MUHAMAD SUHAIMI BIN SULAIMAN', '0107702959', '337537', 'MOTOSIKAL', 'Staf', 'YAMAHA', 'VFM8449', 'ADA', 'JA1619'),
(29, 'MOHD FAIEZAL BIN BHARUM', '0193264663', '295323', 'KERETA', 'Staf', 'PROTON', 'NDS9552', 'ADA', 'JA0488'),
(30, 'MOHAMMAD ASHRAF BIN ABU BAKAR', '01110868159', '228471', 'KERETA', 'Staf', 'NISSAN', 'DDU5554', 'ADA', 'JA0040'),
(31, 'IRWAN BIN ISMAIL', '0193567744', '277134', 'KERETA', 'Staf', 'HONDA', 'DBY1616', 'ADA', 'JA0290'),
(32, 'SYED MOHD NAJIB BIN SYED ISMAIL', '01110890001', '165495', 'KERETA', 'Staf', 'PERODUA', 'JMX6690', 'ADA', 'JA0090'),
(33, 'NORHISHAM BIN AMBI', '0197586589', '120605', 'KERETA', 'Staf', 'PROTON', 'PMR1233', 'ADA', 'JA0296'),
(34, 'SYED MOHD NAJIB BIN SYED ISMAIL', '01110890001', '165495', 'KERETA', 'Staf', 'PERODUA', 'JSQ4427', 'ADA', 'JA0298'),
(36, 'NORSYIDAH BINTI MAHMAT SANI', '0123160746', '260468', 'KERETA', 'Staf', 'NISSAN', 'WXQ2915', 'ADA', 'JA0012'),
(37, 'MOHD YUSZA BIN MOHD YUSOF', '0197941712', '208352', 'KERETA', 'Staf', 'PERODUA', 'JFG6008', 'ADA', 'JA0015'),
(38, 'MOHD YUSZA BIN MOHD YUSOF', '0197941712', '208352', 'KERETA', 'Staf', 'PERODUA', 'TBU9932', 'ADA', 'JA0014'),
(39, 'MOHD ZAMIRUL IKMAL BIN MOHD BADRIN', '0175916195', '344025', 'MOTOSIKAL', 'Staf', 'HONDA', 'BLV4320', 'ADA', 'JA1555'),
(40, 'MOHD ZAMIRUL IKMAL BIN MOHD BADRIN', '0175916195', '344025', 'KERETA', 'Staf', 'PERODUA', 'KEP1788', 'ADA', 'JA0016'),
(41, 'MOHD ZAMIRUL IKMAL BIN MOHD BADRIN', '0175916195', '344025', 'KERETA', 'Staf', 'PERODUA', 'WLJ9718', 'ADA', 'JA0018'),
(42, 'MOHD SHAH REZA BIN UMAR', '0127554337', '212306', 'MOTOSIKAL', 'Staf', 'HONDA', 'JHY2480', 'ADA', 'JA1587'),
(43, 'MOHD SHAH REZA BIN UMAR', '0127554337', '212306', 'KERETA', 'Staf', 'NISSAN', 'JMB4324', 'ADA', 'JA0222'),
(44, 'WAN AB MUTALIB BIN MAZLAN @ ALI', '0177234069', '201786', 'MOTOSIKAL', 'Staf', 'YAMAHA', 'JUH1336', 'ADA', 'JA1600'),
(45, 'SELEMAN BIN LONG', '0137232057', '208336', 'MOTOSIKAL', 'Staf', 'HONDA', 'JHU9840', 'ADA', 'JA1588'),
(46, 'SELEMAN BIN LONG', '0137232057', '208336', 'KERETA', 'Staf', 'HONDA', 'JPE112', 'ADA', 'JA0230'),
(47, 'SELEMAN BIN LONG', '0137232057', '208336', 'KERETA', 'Staf', 'HONDA', 'JVB112', 'ADA', 'JA0231'),
(48, 'MUHAMAD YAMIN BIN ABD KARIM', '0192467682', '298388', 'KERETA', 'Staf', 'PERODUA', 'CCR6319', 'ADA', 'JA0281'),
(49, 'RABIATUL ADAWIYAH BINTI ZOLKIFLI', '0145061662', '321598', 'KERETA', 'Staf', 'PROTON', 'TAF8352', 'ADA', 'JA0023'),
(50, 'NORRITA BINTI M. RASHID', '0127853788', '120919', 'KERETA', 'Staf', 'PERODUA', 'VAG6250', 'ADA', 'JA0038'),
(51, 'INTAN MAIZURA BINTI ZULKEFLEE', '0163591250', '301262', 'KERETA', 'Staf', 'PROTON', 'FB2389', 'ADA', 'JA0028'),
(52, 'MUHAMMAD LUKMANULHAKIM BIN MOHD AMIR', '0136434849', '294353', 'KERETA', 'Staf', 'PROTON', 'JTY7959', 'ADA', 'JA0043'),
(53, 'MOHAMAD ALI BIN TALIB', '0139329897', '172255', 'KERETA', 'Staf', 'PROTON', 'JVC9897', 'ADA', 'JA0024'),
(54, 'MOHAMAD ALI BIN TALIB', '0139329897', '172255', 'KERETA', 'Staf', 'LAIN-LAIN', 'JVD9897', 'ADA', 'JA0021'),
(55, 'MOHD FAIEZAL BIN BHARUM', '0193264663', '295323', 'MOTOSIKAL', 'Staf', 'YAMAHA', 'NDK4993', 'ADA', 'JA1621'),
(56, 'IRWAN BIN ISMAIL', '0193567744', '277134', 'KERETA', 'Staf', 'PROTON', 'WCP7470', 'ADA', 'JA0291'),
(57, 'NORHISHAM BIN AMBI', '0197586589', '120605', 'KERETA', 'Staf', 'PERODUA', 'JUE1233', 'ADA', 'JA0296'),
(62, 'MUHAMMAD ZAIM IRFAN BIN MOHD ZAMRI', '0147668227', '2422', 'KERETA', 'PELAWAT', 'LAIN-LAIN', 'JRL7134', 'TIADA', 'TIADA'),
(63, 'MUHAMMAD ZAIM IRFAN BIN MOHD ZAMRI', '0147668227', '2422', 'KERETA', 'KONTRAKTOR', 'HONDA', 'JRL7134', 'TIADA', 'TIADA');

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
(6, 'zaimi3009@gmail.com', 'zaim05', 'MUHAMMAD ZAIM IRFAN BIN MOHD ZAMRI'),
(9, 'mimihasliah@uitm.edu.my', '1234', '1234');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

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
  MODIFY `userid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
