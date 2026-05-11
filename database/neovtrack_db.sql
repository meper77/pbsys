-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 24, 2026 at 02:29 AM
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
-- Database: `neovtrack_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `userid` int(11) NOT NULL,
  `email` varchar(200) NOT NULL,
  `password` varchar(200) NOT NULL,
  `name` varchar(200) NOT NULL,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`userid`, `email`, `password`, `name`, `last_login`) VALUES
(1, 'admin@mail.com', '111111', 'ADMIN', NULL),
(4, 'akifirfan8@gmail.com', '111', 'MUHAMMAD AKIF IRFAN BIN MD.SADON', NULL),
(6, 'zaimi3009@gmail.com', 'zaim05', 'MUHAMMAD ZAIM IRFAN BIN MOHD ZAMRI', NULL),
(9, 'mimihasliah@uitm.edu.my', '123456', 'MIMI HASLIAH BINTI MOHD SHAHARI', NULL),
(10, 'MUHAMMAD122731@gmail.com', '27032005', 'MUHAMMAD HAKIM BIN MOHD HELMI', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `owner`
--

CREATE TABLE `owner` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `ownerEmail` varchar(255) DEFAULT NULL,
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

INSERT INTO `owner` (`id`, `name`, `ownerEmail`, `phone`, `idnumber`, `type`, `status`, `brand`, `platenum`, `sticker`, `stickerno`) VALUES
(2, 'MUHAMMAD ZAIM IRFAN BIN MOHD ZAMRI', NULL, '0147668227', '2020452222', 'KERETA', 'Pelajar', 'LAIN-LAIN', 'JRL7134', 'ADA', 'jv9808989'),
(3, 'MUHAMMAD AKIF IRFAN BIN MD SADON', NULL, '0197673917', '2020611506', 'KERETA', 'Pelajar', 'HONDA', 'JRU 3045', 'TIADA', ''),
(4, 'IQMALIAH REZANA BINTI HAFIZ', NULL, '0135787916', '2020854318', 'KERETA', 'Pelajar', 'TOYOTA', 'VIP3451', 'TIADA', ''),
(7, 'ZARINA BINTI ABDOL WAHAP', NULL, '0127466612', '209380', 'KERETA', 'Staf', 'HONDA', 'JGR3818', 'ADA', 'JA0077'),
(8, 'MOHD FAROK BIN MUSTAJAB', NULL, '0177150632', '199021', 'MOTOSIKAL', 'Staf', 'YAMAHA', 'JDU1780', 'ADA', 'JA1554'),
(9, 'RINA BINTI SAMAD ROSDI', NULL, '0127490885', '239897', 'KERETA', 'Staf', 'HONDA', 'JKF6604', 'ADA', 'JA0011'),
(10, 'MOHD SAIFUL NIZAM BIN SARIDIN', NULL, '0137580343', '262466', 'MOTOSIKAL', 'Staf', 'YAMAHA', 'BPQ9342', 'ADA', 'JA1558'),
(11, 'NUR SYAFIKA BINTI MENHAD', NULL, '01118774704', '318611', 'KERETA', 'Staf', 'PERODUA', 'JQS9107', 'ADA', 'JA0007'),
(12, 'ZARINA BINTI ABDOL WAHAP', NULL, '0127466612', '209380', 'KERETA', 'Staf', 'PERODUA', 'JUR422', 'ADA', 'JA0022'),
(13, 'MOHD SAIFUL NIZAM BIN SARIDIN', NULL, '0137580343', '262466', 'KERETA', 'Staf', 'LAIN-LAIN', 'JVN637', 'ADA', 'JA0229'),
(14, 'BASYIRAH BINTI YUSOF', NULL, '0125925675', '223573', 'KERETA', 'Staf', 'PERODUA', 'WB5958K', 'ADA', 'JA0001'),
(15, 'MOHD KHIRUL IZAM BIN TUSIRAN', NULL, '0127476441', '230155', 'KERETA', 'Staf', 'PROTON', 'JQT4427', 'ADA', 'JA0002'),
(16, 'MOHAMAD ISAHRUDDIN BIN AMIRUDDIN', NULL, '0197166427', '227414', 'MOTOSIKAL', 'Staf', 'YAMAHA', 'JRC5474', 'ADA', 'JA1612'),
(17, 'MOHD SAIFUL NIZAM BIN SARIDIN', NULL, '0137580343', '262466', 'KERETA', 'Staf', 'PERODUA', 'JVB6729', 'ADA', 'JA0229'),
(18, 'MOHD KHIRUL IZAM BIN TUSIRAN', NULL, '0127476441', '230155', 'MOTOSIKAL', 'Staf', 'YAMAHA', 'JRC4427', 'ADA', 'JA1548'),
(19, 'RAFIUDDIN BIN MOHD YUSOF', NULL, '0132388381', '296432', 'MOTOSIKAL', 'Staf', 'YAMAHA', 'RAQ907', 'ADA', 'JA1625'),
(20, 'ZURAIDAH BINTI SUMERY', NULL, '0197317943', '263012', 'KERETA', 'Staf', 'TOYOTA', 'WRL8915', 'ADA', 'JA0470'),
(21, 'JUHARIE BIN JEMAIN', NULL, '0177017504', '184502', 'KERETA', 'Staf', 'LAIN-LAIN', 'WCD3430', 'ADA', 'JA0499'),
(22, 'RAFIUDDIN BIN MOHD YUSOF', NULL, '0132388381', '296432', 'KERETA', 'Staf', 'PROTON', 'NDG4761', 'ADA', 'JA0520'),
(23, 'JUHARIE BIN JEMAIN', NULL, '0177017504', '184502', 'KERETA', 'Staf', 'PERODUA', 'NAM4883', 'ADA', 'JA0502'),
(24, 'ZURAIDAH BINTI SUMERY', NULL, '0197317943', '263012', 'KERETA', 'Staf', 'LAIN-LAIN', 'WVM9669', 'ADA', 'JA0469'),
(25, 'MUHAMAD SUHAIMI BIN SULAIMAN', NULL, '0107702959', '337537', 'KERETA', 'Staf', 'PERODUA', 'JRY9768', 'ADA', 'JA0514'),
(26, 'MIMI HASLIAH BINTI MOHD SHAHARI', 'mimihasliah@uitm.edu.my', '0127375091', '159650', 'KERETA', 'Staf', 'PERODUA', 'WTX4390', 'ADA', 'JA0417'),
(27, 'MOHAMMAD ASHRAF BIN ABU BAKAR', NULL, '01110868159', '228471', 'KERETA', 'Staf', 'PERODUA', 'JKF3824', 'ADA', 'JA0039'),
(28, 'MUHAMAD SUHAIMI BIN SULAIMAN', NULL, '0107702959', '337537', 'MOTOSIKAL', 'Staf', 'YAMAHA', 'VFM8449', 'ADA', 'JA1619'),
(29, 'MOHD FAIEZAL BIN BHARUM', NULL, '0193264663', '295323', 'KERETA', 'Staf', 'PROTON', 'NDS9552', 'ADA', 'JA0488'),
(30, 'MOHAMMAD ASHRAF BIN ABU BAKAR', NULL, '01110868159', '228471', 'KERETA', 'Staf', 'NISSAN', 'DDU5554', 'ADA', 'JA0040'),
(31, 'IRWAN BIN ISMAIL', NULL, '0193567744', '277134', 'KERETA', 'Staf', 'HONDA', 'DBY1616', 'ADA', 'JA0290'),
(32, 'SYED MOHD NAJIB BIN SYED ISMAIL', NULL, '01110890001', '165495', 'KERETA', 'Staf', 'PERODUA', 'JMX6690', 'ADA', 'JA0090'),
(33, 'NORHISHAM BIN AMBI', NULL, '0197586589', '120605', 'KERETA', 'Staf', 'PROTON', 'PMR1233', 'ADA', 'JA0296'),
(34, 'SYED MOHD NAJIB BIN SYED ISMAIL', NULL, '01110890001', '165495', 'KERETA', 'Staf', 'PERODUA', 'JSQ4427', 'ADA', 'JA0298'),
(36, 'NORSYIDAH BINTI MAHMAT SANI', NULL, '0123160746', '260468', 'KERETA', 'Staf', 'NISSAN', 'WXQ2915', 'ADA', 'JA0012'),
(37, 'MOHD YUSZA BIN MOHD YUSOF', NULL, '0197941712', '208352', 'KERETA', 'Staf', 'PERODUA', 'JFG6008', 'ADA', 'JA0015'),
(38, 'MOHD YUSZA BIN MOHD YUSOF', NULL, '0197941712', '208352', 'KERETA', 'Staf', 'PERODUA', 'TBU9932', 'ADA', 'JA0014'),
(39, 'MOHD ZAMIRUL IKMAL BIN MOHD BADRIN', NULL, '0175916195', '344025', 'MOTOSIKAL', 'Staf', 'HONDA', 'BLV4320', 'ADA', 'JA1555'),
(40, 'MOHD ZAMIRUL IKMAL BIN MOHD BADRIN', NULL, '0175916195', '344025', 'KERETA', 'Staf', 'PERODUA', 'KEP1788', 'ADA', 'JA0016'),
(41, 'MOHD ZAMIRUL IKMAL BIN MOHD BADRIN', NULL, '0175916195', '344025', 'KERETA', 'Staf', 'PERODUA', 'WLJ9718', 'ADA', 'JA0018'),
(42, 'MOHD SHAH REZA BIN UMAR', NULL, '0127554337', '212306', 'MOTOSIKAL', 'Staf', 'HONDA', 'JHY2480', 'ADA', 'JA1587'),
(43, 'MOHD SHAH REZA BIN UMAR', NULL, '0127554337', '212306', 'KERETA', 'Staf', 'NISSAN', 'JMB4324', 'ADA', 'JA0222'),
(44, 'WAN AB MUTALIB BIN MAZLAN @ ALI', NULL, '0177234069', '201786', 'MOTOSIKAL', 'Staf', 'YAMAHA', 'JUH1336', 'ADA', 'JA1600'),
(45, 'SELEMAN BIN LONG', NULL, '0137232057', '208336', 'MOTOSIKAL', 'Staf', 'HONDA', 'JHU9840', 'ADA', 'JA1588'),
(46, 'SELEMAN BIN LONG', NULL, '0137232057', '208336', 'KERETA', 'Staf', 'HONDA', 'JPE112', 'ADA', 'JA0230'),
(47, 'SELEMAN BIN LONG', NULL, '0137232057', '208336', 'KERETA', 'Staf', 'HONDA', 'JVB112', 'ADA', 'JA0231'),
(48, 'MUHAMAD YAMIN BIN ABD KARIM', NULL, '0192467682', '298388', 'KERETA', 'Staf', 'PERODUA', 'CCR6319', 'ADA', 'JA0281'),
(49, 'RABIATUL ADAWIYAH BINTI ZOLKIFLI', NULL, '0145061662', '321598', 'KERETA', 'Staf', 'PROTON', 'TAF8352', 'ADA', 'JA0023'),
(50, 'NORRITA BINTI M. RASHID', NULL, '0127853788', '120919', 'KERETA', 'Staf', 'PERODUA', 'VAG6250', 'ADA', 'JA0038'),
(51, 'INTAN MAIZURA BINTI ZULKEFLEE', NULL, '0163591250', '301262', 'KERETA', 'Staf', 'PROTON', 'FB2389', 'ADA', 'JA0028'),
(52, 'MUHAMMAD LUKMANULHAKIM BIN MOHD AMIR', NULL, '0136434849', '294353', 'KERETA', 'Staf', 'PROTON', 'JTY7959', 'ADA', 'JA0043'),
(53, 'MOHAMAD ALI BIN TALIB', NULL, '0139329897', '172255', 'KERETA', 'Staf', 'PROTON', 'JVC9897', 'ADA', 'JA0024'),
(54, 'MOHAMAD ALI BIN TALIB', NULL, '0139329897', '172255', 'KERETA', 'Staf', 'LAIN-LAIN', 'JVD9897', 'ADA', 'JA0021'),
(55, 'MOHD FAIEZAL BIN BHARUM', NULL, '0193264663', '295323', 'MOTOSIKAL', 'Staf', 'YAMAHA', 'NDK4993', 'ADA', 'JA1621'),
(56, 'IRWAN BIN ISMAIL', NULL, '0193567744', '277134', 'KERETA', 'Staf', 'PROTON', 'WCP7470', 'ADA', 'JA0291'),
(57, 'NORHISHAM BIN AMBI', NULL, '0197586589', '120605', 'KERETA', 'Staf', 'PERODUA', 'JUE1233', 'ADA', 'JA0296'),
(62, 'MUHAMMAD ZAIM IRFAN BIN MOHD ZAMRI', NULL, '0147668227', '2422', 'KERETA', 'PELAWAT', 'LAIN-LAIN', 'JRL7134', 'TIADA', 'TIADA'),
(63, 'MUHAMMAD ZAIM IRFAN BIN MOHD ZAMRI', NULL, '0147668227', '2422', 'KERETA', 'Kontraktor', 'HONDA', 'JRL7134', 'TIADA', 'TIADA'),
(65, 'MUHAMMAD IZZAT BIN ISHAK', NULL, '0142778867', '2023613046', 'MOTOSIKAL', 'Staf', '', 'VJX8027', 'TIADA', ''),
(66, 'MUHAMMAD HAKIM BIN MOHD HELMI', NULL, '0103579369', '2023247522', 'KERETA', 'Pelajar', '', 'JFQ1772', 'TIADA', ''),
(67, 'MUHAMMAD HAKIM BIN MOHD HELMI', NULL, '0103579369', '2023247522', 'KERETA', 'Staf', '', 'JJJ9999', 'TIADA', ''),
(69, 'MUHAMMAD HAKIM BIN MOHD HELMI', NULL, '0103579369', '1234', 'KERETA', 'Pelawat', '', 'ABC1234', 'TIADA', ''),
(70, 'MUHAMMAD HAKIM BIN MOHD HELMI', NULL, '0103579369', '5678', 'KERETA', 'Kontraktor', '', 'XYZ890', 'TIADA', ''),
(72, 'Siti Sarah', NULL, '0134567890', '2023001', 'MOTOSIKAL', 'Pelajar', '', 'DEF5678', 'TIADA', ''),
(73, 'John Doe', NULL, '0145678901', 'IC123456', 'VAN', 'Pelawat', '', 'GHI9012', 'TIADA', ''),
(74, 'Ahmad Kontraktor', NULL, '0156789012', 'K001', 'LORI', 'Kontraktor', '', 'JKL3456', 'TIADA', ''),
(83, 'MUHAMMAD AFIF BIN ZAIFIDDIN', NULL, '0138735705', '2022244477', 'KERETA', 'Pelajar', '', 'ABC6769', 'TIADA', ''),
(84, 'MUHAMMAD FARIZ FADHLI BIN MOHD RAZIF', NULL, '0136501848', '2023355599', 'KERETA', 'Pelajar', '', 'KID1412', 'TIADA', ''),
(85, 'IZZAT SYAFIQ ISKANDAR BIN ALI', NULL, '0138276924', '2025558765', 'MOTOSIKAL', 'Staf', '', 'CEN3074', 'TIADA', ''),
(86, 'MUHAMMAD FARIS BIN ABDULLAH', NULL, '0103335789', '202456', '4WD', 'Staf', '', 'SGD7777', 'ADA', 'JA666');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_requests`
--

CREATE TABLE `password_reset_requests` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `plate_number` varchar(20) DEFAULT NULL,
  `staff_id` varchar(50) DEFAULT NULL,
  `request_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `user_type` enum('user','admin') NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `status` enum('pending','processing','completed','rejected') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL
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
  `name` varchar(200) NOT NULL,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`userid`, `email`, `password`, `name`, `last_login`) VALUES
(1, 'admin@mail.com', '111', 'ADMIN', NULL),
(4, 'akifirfan8@gmail.com', '111', 'MUHAMMAD AKIF IRFAN BIN MD.SADON', NULL),
(6, 'zaimi3009@gmail.com', 'zaim05', 'MUHAMMAD ZAIM IRFAN BIN MOHD ZAMRI', NULL),
(9, 'mimihasliah@uitm.edu.my', '123456', 'MIMI HASLIAH BINTI MOHD SHAHARI', NULL),
(11, 'MUHAMMAD122731@gmail.com', '27032005', 'MUHAMMAD HAKIM BIN MOHD HELMI', '2026-02-23 15:24:29'),
(12, 'hadif@gmail.com', '1234567890', 'MUHAMMAD HADIF BIN MOHD HELMI', NULL),
(13, 'mad@gmail.com', '357012', 'MUHAMMAD BIN AMINUDDIN', NULL),
(14, 'afifzaifiddin@gmail.com', '567890', 'MUHAMMAD AFIF BIN ZAIFIDDIN', '2026-01-19 07:42:12'),
(16, 'azizanrashdan@gmail.com', 'atiqah123', 'AZIZAN RASHDAN BIN AHMAD FUAAD@ABD RAHIM', '2026-02-03 09:44:36'),
(19, 'danishhaikal@gmail.com', '123456', 'MUHAMMAD DANISH HAIKAL BIN HAKIM', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `user_type` enum('user','admin') NOT NULL,
  `login_time` datetime DEFAULT current_timestamp(),
  `last_activity` datetime DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_id`, `email`, `user_type`, `login_time`, `last_activity`, `ip_address`, `user_agent`) VALUES
(1, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'afifzaifiddin@gmail.com', 'user', '2026-01-19 07:42:12', '2026-01-19 07:42:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'),
(2, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'MUHAMMAD122731@gmail.com', 'user', '2026-01-19 09:16:17', '2026-01-19 09:16:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'),
(3, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'MUHAMMAD122731@gmail.com', 'user', '2026-01-19 09:45:00', '2026-01-19 09:45:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'),
(4, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'MUHAMMAD122731@gmail.com', 'user', '2026-01-19 09:51:56', '2026-01-19 09:51:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'),
(5, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'MUHAMMAD122731@gmail.com', 'user', '2026-01-19 10:06:17', '2026-01-19 10:06:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'),
(6, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'MUHAMMAD122731@gmail.com', 'user', '2026-01-25 17:48:47', '2026-01-25 17:48:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(7, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'MUHAMMAD122731@gmail.com', 'user', '2026-01-26 12:33:23', '2026-01-26 12:33:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(8, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'MUHAMMAD122731@gmail.com', 'user', '2026-01-26 12:35:29', '2026-01-26 12:35:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(9, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'MUHAMMAD122731@gmail.com', 'user', '2026-01-27 22:12:42', '2026-01-27 22:12:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(10, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'MUHAMMAD122731@gmail.com', 'user', '2026-01-28 16:26:14', '2026-01-28 16:26:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(11, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'MUHAMMAD122731@gmail.com', 'user', '2026-01-30 08:22:13', '2026-01-30 08:22:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(12, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'MUHAMMAD122731@gmail.com', 'user', '2026-02-01 12:31:51', '2026-02-01 12:31:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(13, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'MUHAMMAD122731@gmail.com', 'user', '2026-02-01 12:36:22', '2026-02-01 12:36:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(14, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'MUHAMMAD122731@gmail.com', 'user', '2026-02-01 12:36:38', '2026-02-01 12:36:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(15, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'MUHAMMAD122731@gmail.com', 'user', '2026-02-01 19:43:15', '2026-02-01 19:43:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(16, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'MUHAMMAD122731@gmail.com', 'user', '2026-02-01 19:43:26', '2026-02-01 19:43:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(17, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'MUHAMMAD122731@gmail.com', 'user', '2026-02-03 09:35:40', '2026-02-03 09:35:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(18, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'MUHAMMAD122731@gmail.com', 'user', '2026-02-03 09:36:20', '2026-02-03 09:36:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(19, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'azizanrashdan@gmail.com', 'user', '2026-02-03 09:44:36', '2026-02-03 09:44:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(20, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'MUHAMMAD122731@gmail.com', 'user', '2026-02-03 10:08:38', '2026-02-03 10:08:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(21, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'MUHAMMAD122731@gmail.com', 'user', '2026-02-03 22:20:34', '2026-02-03 22:20:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(22, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'abdulhakim123@gmail.com', 'user', '2026-02-03 22:22:38', '2026-02-03 22:22:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(23, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'MUHAMMAD122731@gmail.com', 'user', '2026-02-04 10:19:57', '2026-02-04 10:19:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(24, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'MUHAMMAD122731@gmail.com', 'user', '2026-02-04 10:20:28', '2026-02-04 10:20:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(25, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'MUHAMMAD122731@gmail.com', 'user', '2026-02-06 08:37:29', '2026-02-06 08:37:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(26, 0, 'k9tm4md4mv3rp9lv3ndqfdrh7k', 'MUHAMMAD122731@gmail.com', 'user', '2026-02-06 10:47:30', '2026-02-06 10:47:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(27, 0, 'p8ra9m3afrd1dda5kjjjs7kdbs', 'MUHAMMAD122731@gmail.com', 'user', '2026-02-13 09:31:02', '2026-02-13 09:31:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36'),
(28, 0, 'irj29f425tn0o2h4f1dedo5cf7', 'MUHAMMAD122731@gmail.com', 'user', '2026-02-23 09:57:50', '2026-02-23 09:57:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
(29, 0, 'irj29f425tn0o2h4f1dedo5cf7', 'MUHAMMAD122731@gmail.com', 'user', '2026-02-23 15:24:29', '2026-02-23 15:24:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`userid`);

--
-- Indexes for table `owner`
--
ALTER TABLE `owner`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_user_type` (`user_type`);

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
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_last_activity` (`last_activity`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_user_type` (`user_type`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `userid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `owner`
--
ALTER TABLE `owner`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
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
  MODIFY `userid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
