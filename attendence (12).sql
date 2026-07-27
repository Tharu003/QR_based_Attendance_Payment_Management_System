-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 27, 2026 at 02:13 PM
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
-- Database: `attendence`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Present',
  `class_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time` time DEFAULT curtime()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `status`, `class_id`, `date`, `time`) VALUES
(200, 6, 'Present', 30, '2026-05-01', '08:00:00'),
(201, 7, 'Present', 30, '2026-05-01', '08:05:00'),
(202, 8, 'Absent', 30, '2026-05-01', '08:10:00'),
(203, 6, 'Present', 43, '2026-05-02', '09:00:00'),
(204, 7, 'Absent', 43, '2026-05-02', '09:05:00'),
(205, 8, 'Present', 43, '2026-05-02', '09:10:00'),
(209, 10, 'Present', 33, '2026-05-04', '11:00:00'),
(210, 10, 'Present', 30, '2026-05-04', '11:10:00'),
(211, 10, 'Present', 43, '2026-05-04', '11:20:00'),
(212, 6, 'Present', 30, '2026-05-12', '08:00:00'),
(213, 7, 'Present', 30, '2026-05-12', '08:05:00'),
(214, 8, 'Absent', 30, '2026-05-12', '08:10:00'),
(215, 9, 'Present', 30, '2026-05-12', '08:15:00'),
(216, 10, 'Present', 30, '2026-05-12', '08:20:00'),
(218, 12, 'Present', 30, '2026-05-12', '08:30:00'),
(220, 14, 'Present', 30, '2026-05-12', '08:40:00'),
(221, 15, 'Present', 30, '2026-05-12', '08:45:00'),
(222, 11, 'Present', 30, '2026-06-09', '19:41:12'),
(223, 7, 'Present', 30, '2026-06-09', '19:41:16'),
(224, 12, 'Present', 30, '2026-06-09', '19:41:17'),
(225, 6, 'Present', 30, '2026-06-09', '19:41:18'),
(226, 8, 'Present', 30, '2026-06-09', '19:41:19'),
(227, 6, 'Present', 48, '2026-06-17', '21:19:59'),
(228, 10, 'Present', 48, '2026-06-18', '15:48:29'),
(229, 12, 'Present', 30, '2026-05-23', '21:53:06'),
(230, 7, 'Present', 30, '2026-05-23', '21:53:12'),
(231, 8, 'Present', 30, '2026-05-23', '21:53:17'),
(233, 14, 'Present', 30, '2026-05-23', '21:53:26'),
(234, 12, 'Present', 30, '2026-05-15', '21:53:54'),
(235, 7, 'Present', 30, '2026-05-15', '21:54:00'),
(236, 8, 'Present', 30, '2026-05-15', '21:54:10'),
(238, 14, 'Present', 30, '2026-05-15', '21:54:18'),
(239, 15, 'Present', 30, '2026-05-15', '21:54:22'),
(240, 12, 'Present', 30, '2026-05-10', '21:54:39'),
(241, 7, 'Present', 30, '2026-05-10', '21:54:42'),
(242, 8, 'Present', 30, '2026-05-10', '21:54:46'),
(244, 14, 'Present', 30, '2026-05-10', '21:54:53'),
(245, 15, 'Present', 30, '2026-05-10', '21:54:56'),
(246, 8, 'Present', 30, '2026-06-02', '05:06:25'),
(247, 8, 'Present', 30, '2026-06-15', '05:06:35'),
(248, 8, 'Present', 43, '2026-06-01', '05:06:54'),
(249, 8, 'Present', 48, '2026-06-01', '05:07:15'),
(250, 8, 'Present', 48, '2026-06-10', '05:07:25'),
(251, 8, 'Present', 48, '2026-06-18', '05:07:34'),
(253, 12, 'Present', 30, '2026-06-24', '11:33:46'),
(254, 11, 'Present', 30, '2026-06-24', '11:35:38'),
(255, 6, 'Present', 30, '2026-05-24', '11:38:57'),
(256, 6, 'Present', 30, '2026-05-10', '11:39:09'),
(257, 6, 'Present', 48, '2026-06-24', '11:40:50'),
(258, 6, 'Present', 48, '2026-06-28', '21:21:16'),
(259, 6, 'Present', 30, '2026-07-08', '12:05:44'),
(260, 6, 'Present', 30, '2026-01-08', '13:54:48'),
(261, 6, 'Present', 29, '2026-06-04', '17:42:39'),
(262, 6, 'Present', 29, '2026-06-11', '17:42:51'),
(263, 6, 'Present', 29, '2026-07-06', '17:50:06'),
(264, 7, 'Present', 29, '2026-07-06', '17:50:13'),
(265, 11, 'Present', 29, '2026-07-03', '18:44:12'),
(266, 6, 'Present', 29, '2026-07-13', '13:52:27'),
(267, 7, 'Present', 29, '2026-07-13', '13:52:52');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `monthly_fee` decimal(10,2) DEFAULT 1000.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `subject`, `teacher_id`, `monthly_fee`) VALUES
(29, 'Grade 6 ගණිතය', 2, 1000.00),
(30, 'Grade 7 ගණිතය', 2, 1000.00),
(31, 'Grade 8 ගණිතය', 2, 1000.00),
(32, 'Grade 9 ගණිතය', 2, 1000.00),
(33, 'Grade 6 විද්‍යාව', 1, 1000.00),
(34, 'Grade 7 විද්‍යාව', 1, 1000.00),
(35, 'Grade 8 විද්‍යාව', 1, 1000.00),
(36, 'Grade 9 විද්‍යාව', 1, 1000.00),
(37, 'Grade 10 විද්‍යාව', 1, 1000.00),
(38, 'Grade 6 සිංහල', 3, 1000.00),
(39, 'Grade 7 සිංහල', 3, 1000.00),
(40, 'Grade 8 සිංහල', 3, 1000.00),
(41, 'Grade 9 සිංහල', 3, 1000.00),
(42, 'Grade 10 සිංහල', 3, 1000.00),
(43, 'Grade 11 සිංහල', 3, 1000.00),
(44, 'Grade 6 ඉංග්‍රීසි', 6, 1000.00),
(45, 'Grade 7 ඉංග්‍රීසි', 6, 1000.00),
(46, 'Grade 8 ඉංග්‍රීසි', 6, 1000.00),
(47, 'Grade 9 ඉංග්‍රීසි', 6, 1000.00),
(48, 'Grade 10 ඉංග්‍රීසි', 6, 1000.00),
(49, 'Grade 11 ඉංග්‍රීසි', 6, 1000.00),
(50, 'Grade 1 නර්තනය', 11, 1000.00),
(51, 'Grade 2 නර්තනය', 11, 1000.00),
(52, 'Grade 3 නර්තනය', 11, 1000.00),
(53, 'Grade 4 නර්තනය', 11, 1000.00),
(54, 'Grade 5 නර්තනය', 11, 1000.00),
(55, 'Grade 6 නර්තනය', 11, 1000.00),
(56, 'Grade 7 නර්තනය', 11, 1000.00),
(57, 'Grade 8 නර්තනය', 11, 1000.00),
(58, 'Grade 9 නර්තනය', 11, 1000.00),
(59, 'Grade 6 තොරතුරු තාක්ෂණය', 5, 1000.00),
(60, 'Grade 7 තොරතුරු තාක්ෂණය', 5, 1000.00),
(61, 'Grade 8 තොරතුරු තාක්ෂණය', 5, 1000.00),
(62, 'Grade 9 තොරතුරු තාක්ෂණය', 5, 1000.00),
(63, 'Grade 10 තොරතුරු තාක්ෂණය', 5, 1000.00),
(64, 'Grade 11 තොරතුරු තාක්ෂණය', 5, 1000.00),
(65, 'Grade 10 වාණිජ්‍යය', 7, 1000.00),
(66, 'Grade 11 වාණිජ්‍යය', 7, 1000.00);

-- --------------------------------------------------------

--
-- Table structure for table `class_grades`
--

CREATE TABLE `class_grades` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `grade` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_grades`
--

INSERT INTO `class_grades` (`id`, `class_id`, `grade`) VALUES
(67, 29, 'Grade 6'),
(68, 30, 'Grade 7'),
(69, 31, 'Grade 8'),
(70, 32, 'Grade 9'),
(71, 33, 'Grade 6'),
(72, 34, 'Grade 7'),
(73, 35, 'Grade 8'),
(74, 36, 'Grade 9'),
(75, 37, 'Grade 10'),
(76, 38, 'Grade 6'),
(77, 39, 'Grade 7'),
(78, 40, 'Grade 8'),
(79, 41, 'Grade 9'),
(80, 42, 'Grade 10'),
(81, 43, 'Grade 11'),
(82, 44, 'Grade 6'),
(83, 45, 'Grade 7'),
(84, 46, 'Grade 8'),
(85, 47, 'Grade 9'),
(86, 48, 'Grade 10'),
(87, 49, 'Grade 11'),
(88, 50, 'Grade 1'),
(89, 51, 'Grade 2'),
(90, 52, 'Grade 3'),
(91, 53, 'Grade 4'),
(92, 54, 'Grade 5'),
(93, 55, 'Grade 6'),
(94, 56, 'Grade 7'),
(95, 57, 'Grade 8'),
(96, 58, 'Grade 9'),
(97, 59, 'Grade 6'),
(98, 60, 'Grade 7'),
(99, 61, 'Grade 8'),
(100, 62, 'Grade 9'),
(101, 63, 'Grade 10'),
(102, 64, 'Grade 11'),
(103, 65, 'Grade 10'),
(104, 66, 'Grade 11');

-- --------------------------------------------------------

--
-- Table structure for table `class_materials`
--

CREATE TABLE `class_materials` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `grade` varchar(50) DEFAULT NULL,
  `week_no` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `type` enum('note','video') NOT NULL,
  `file_url` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `material_type` enum('tute','quiz','video','assignment') DEFAULT 'tute',
  `generated_by` varchar(50) DEFAULT 'manual'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_materials`
--

INSERT INTO `class_materials` (`id`, `class_id`, `grade`, `week_no`, `title`, `type`, `file_url`, `video_url`, `created_at`, `material_type`, `generated_by`) VALUES
(13, 29, 'Grade 6', 1, 'maerxf', 'note', 'uploads/1783498865_1777953643_Grade 5 all parisaraya pdf_removed.pdf', '', '2026-07-08 02:51:05', 'tute', 'manual'),
(14, 38, 'Grade 6', 1, 'guththila', 'note', 'uploads/1783689943_Threads.pdf', '', '2026-07-10 13:25:43', 'tute', 'manual');

-- --------------------------------------------------------

--
-- Table structure for table `class_timetable`
--

CREATE TABLE `class_timetable` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `grade` varchar(50) NOT NULL,
  `day_of_week` varchar(20) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `hall_name` varchar(100) DEFAULT NULL,
  `zoom_link` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_timetable`
--

INSERT INTO `class_timetable` (`id`, `class_id`, `grade`, `day_of_week`, `start_time`, `end_time`, `hall_name`, `zoom_link`, `created_at`) VALUES
(7, 29, 'Grade 6', 'Monday', '08:00:00', '10:00:00', '1', '', '2026-07-10 11:18:49'),
(8, 48, 'Grade 10', 'Monday', '19:00:00', '21:00:00', '1', '', '2026-07-10 11:19:09'),
(9, 31, 'Grade 8', 'Friday', '19:00:00', '21:00:00', '2', '', '2026-07-10 11:20:39'),
(10, 44, 'Grade 6', 'Tuesday', '12:00:00', '14:00:00', '1', '', '2026-07-21 05:29:28');

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `grade` varchar(50) NOT NULL,
  `exam_title` varchar(100) NOT NULL,
  `exam_type` enum('physical','online') DEFAULT 'physical',
  `exam_location_or_link` text DEFAULT NULL,
  `exam_date` datetime NOT NULL,
  `duration_minutes` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`id`, `class_id`, `grade`, `exam_title`, `exam_type`, `exam_location_or_link`, `exam_date`, `duration_minutes`) VALUES
(1, 44, 'Grade 6', 'Term test 1', 'physical', '2', '2026-06-19 15:30:00', 120),
(2, 29, 'Grade 6', 'Term test 2', 'physical', '2', '2026-06-26 17:30:00', 120),
(3, 29, 'Grade 6', 'Term test 3', 'physical', '2', '2026-07-24 15:00:00', 120);

-- --------------------------------------------------------

--
-- Table structure for table `exam_results`
--

CREATE TABLE `exam_results` (
  `result_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `marks_obtained` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_results`
--

INSERT INTO `exam_results` (`result_id`, `exam_id`, `student_id`, `marks_obtained`) VALUES
(1, 2, 6, 80.00),
(2, 2, 7, 75.00),
(3, 2, 8, 90.00),
(4, 2, 10, 60.00),
(5, 2, 11, 50.00),
(6, 2, 12, 35.00),
(7, 2, 13, 67.00),
(8, 2, 14, 92.00),
(9, 2, 15, 55.00);

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `expense_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `fee_transfers`
-- (See below for the actual view)
--
CREATE TABLE `fee_transfers` (
`id` int(11)
,`student_id` int(11)
,`class_id` int(11)
,`from_month` varchar(20)
,`target_month` varchar(20)
,`amount` decimal(10,2)
,`reason` text
,`status` enum('Pending','Approved','Rejected')
,`created_by` int(11)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `grade` varchar(50) NOT NULL,
  `notice_date` date NOT NULL DEFAULT curdate(),
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `notice_type` enum('class_cancelled','tute_uploaded','exam_notice','holiday','general') DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notices`
--

INSERT INTO `notices` (`id`, `class_id`, `grade`, `notice_date`, `title`, `content`, `notice_type`, `created_at`) VALUES
(16, 29, 'Grade 6', '2026-06-12', 'Maths new tute uploaded', '2026 grade 6 maths new tute- Trigonametri ', 'tute_uploaded', '2026-06-12 07:12:17'),
(17, 29, 'Grade 6', '2026-07-24', 'Maths 2nd Term Exam', 'Today  you have a maths 2nd term test exam in hall no 2 at 3.00 PM. all the student should participate it.', 'exam_notice', '2026-07-24 02:57:03'),
(18, 29, '', '2026-07-24', '📝 නව විභාග දැනුම්දීමයි (Grade 6): Term test 3', 'ඔබේ Grade 6 පන්තිය සඳහා අලුතින් විභාග කාලසටහනක් එකතු කර ඇත.\n\n📅 දිනය සහ වේලාව: 2026-07-24T15:00\n⏳ කාලය: 120 Minutes\n🧭 විභාග ක්‍රමය: Physical (පන්තියේදී)\n🏛️ විභාග ශාලාව: 2\n\n💬 කරුණාකර නියමිත වේලාවට විභාගය සඳහා සූදානම් වන්න.', 'exam_notice', '2026-07-24 03:02:52');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `month` varchar(20) NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `paid_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `student_id`, `class_id`, `month`, `amount`, `paid_date`) VALUES
(1, 6, 10, 'March', 1000.00, '2026-03-01'),
(14, 7, 10, 'May', 1000.00, '2026-05-02'),
(48, 6, 11, 'May', 1000.00, '2026-05-01'),
(49, 7, 11, 'May', 1000.00, '2026-05-01'),
(50, 8, 11, 'May', 1000.00, '2026-05-01'),
(52, 10, 11, 'May', 1000.00, '2026-05-01'),
(58, 6, 11, 'June', 1000.00, '2026-06-01'),
(59, 7, 11, 'June', 1000.00, '2026-06-01'),
(60, 8, 11, 'June', 1000.00, '2026-06-01'),
(62, 10, 11, 'June', 1000.00, '2026-06-01'),
(68, 8, 11, 'July', 1000.00, '2026-05-20'),
(69, 8, 11, 'March', 1000.00, '2026-05-20'),
(70, 8, 11, 'April', 1000.00, '2026-05-20'),
(72, 8, 10, 'March', 1000.00, '2026-05-27'),
(73, 8, 10, 'April', 1000.00, '2026-06-09'),
(74, 6, 11, 'April', 1000.00, '2026-06-17'),
(75, 8, 10, 'May', 1000.00, '2026-06-17'),
(76, 8, 10, 'June', 1000.00, '2026-06-23'),
(77, 12, 10, 'May', 1000.00, '2026-06-23'),
(78, 12, 10, 'June', 1000.00, '2026-06-23'),
(79, 7, 10, 'March', 1000.00, '2026-06-23'),
(80, 7, 10, 'April', 1000.00, '2026-06-23'),
(81, 7, 10, 'June', 1000.00, '2026-06-23'),
(83, 6, 29, 'March', 1000.00, '2026-07-10'),
(84, 6, 29, 'June', 500.00, '2026-07-10'),
(85, 6, 29, 'April', 0.00, '2026-07-10'),
(86, 6, 33, 'April', 0.00, '2026-07-10'),
(87, 6, 38, 'April', 0.00, '2026-07-10'),
(88, 6, 44, 'April', 0.00, '2026-07-10'),
(89, 6, 29, 'May', 1000.00, '2026-07-10'),
(90, 8, 29, 'June', 1000.00, '2026-07-10'),
(91, 8, 38, 'June', 1000.00, '2026-07-10'),
(92, 8, 29, 'March', 1000.00, '2026-07-10'),
(93, 8, 33, 'March', 1000.00, '2026-07-10'),
(94, 8, 38, 'March', 1000.00, '2026-07-10'),
(95, 8, 29, 'April', 1000.00, '2026-07-10'),
(96, 8, 33, 'April', 1000.00, '2026-07-10'),
(97, 8, 38, 'April', 1000.00, '2026-07-10'),
(98, 8, 29, 'May', 1000.00, '2026-07-10'),
(99, 8, 33, 'May', 1000.00, '2026-07-10'),
(100, 8, 38, 'May', 1000.00, '2026-07-10'),
(101, 8, 33, 'June', 1000.00, '2026-07-10'),
(102, 10, 31, 'April', 1000.00, '2026-07-10'),
(103, 10, 35, 'April', 1000.00, '2026-07-10'),
(104, 10, 40, 'April', 1000.00, '2026-07-10'),
(105, 10, 31, 'May', 1000.00, '2026-07-10'),
(106, 10, 35, 'May', 1000.00, '2026-07-10'),
(107, 10, 40, 'May', 1000.00, '2026-07-10'),
(108, 10, 31, 'June', 1000.00, '2026-07-10'),
(109, 10, 35, 'June', 1000.00, '2026-07-10'),
(110, 10, 40, 'June', 1000.00, '2026-07-10'),
(111, 6, 29, 'July', 1000.00, '2026-07-14'),
(112, 7, 29, 'May', 1000.00, '2026-07-15'),
(113, 8, 38, 'February', 1000.00, '2026-07-15'),
(114, 6, 29, 'August', 1000.00, '2026-07-15'),
(117, 6, 44, 'July', 1000.00, '2026-07-21'),
(118, 6, 38, 'July', 1000.00, '2026-07-24'),
(119, 6, 33, 'July', 1000.00, '2026-07-24');

-- --------------------------------------------------------

--
-- Table structure for table `payment_transfers`
--

CREATE TABLE `payment_transfers` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `from_month` varchar(20) NOT NULL,
  `to_month` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 1000.00,
  `reason` text NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Approved',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_transfers`
--

INSERT INTO `payment_transfers` (`id`, `student_id`, `class_id`, `from_month`, `to_month`, `amount`, `reason`, `status`, `created_by`, `created_at`) VALUES
(1, 7, 29, 'April', 'May', 1000.00, 'g4thyh', 'Approved', 1, '2026-07-15 05:46:26'),
(2, 7, 29, 'April', 'May', 1000.00, 'g4thyh', 'Approved', 1, '2026-07-15 05:47:47'),
(3, 7, 29, 'April', 'May', 1000.00, 'g4thyh', 'Approved', 1, '2026-07-15 05:48:16'),
(4, 8, 38, 'January', 'February', 1000.00, 'dge5t', 'Approved', 1, '2026-07-15 06:03:43'),
(5, 6, 29, 'April', 'August', 1000.00, 'ty65u7', 'Approved', 1, '2026-07-15 06:21:27');

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` int(11) NOT NULL,
  `material_id` int(11) DEFAULT NULL,
  `question` text DEFAULT NULL,
  `option_a` varchar(255) DEFAULT NULL,
  `option_b` varchar(255) DEFAULT NULL,
  `option_c` varchar(255) DEFAULT NULL,
  `option_d` varchar(255) DEFAULT NULL,
  `correct_option` char(1) DEFAULT NULL,
  `generated_by` enum('manual','ai') DEFAULT 'manual'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_questions`
--

INSERT INTO `quiz_questions` (`id`, `material_id`, `question`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`, `generated_by`) VALUES
(6, 14, 'ත්‍රෙඩ් එකක් යනු කුමක්ද?', 'ක්‍රියාවලියක් තුළ කාර්යයන් අනුක්‍රමිකව ගලා යාම.', 'වෙනම සම්පත් සමූහයක් සහිත සම්පූර්ණ ක්‍රියාවලියක්.', 'මෙහෙයුම් පද්ධතියේ ප්‍රධාන සංරචකයක්.', 'දත්ත ගබඩා කිරීම සඳහා පමණක් භාවිතා කරන ඒකකයක්.', 'A', 'ai'),
(7, 14, 'ත්‍රෙඩ් එකක ප්‍රධාන සංරචක තුන කුමක්ද?', 'ප්‍රෝග්‍රෑම් කවුන්ටරය, රෙජිස්ටර් කට්ටලය, ස්ටැක් අවකාශය.', 'CPU, මතකය, දෘඪ තැටිය.', 'කේතය, දත්ත, ගොනු.', 'ආදාන/ප්‍රතිදාන පද්ධති, ජාල කාඩ්පත, ග්‍රැෆික් කාඩ්පත.', 'A', 'ai'),
(8, 14, 'මෙහෙයුම් පද්ධතියක ත්‍රෙඩ් අවශ්‍ය වන්නේ ඇයි?', 'ත්‍රෙඩ් අතර මෙහෙයුම් පිරිවැය අඩු වීම.', 'ක්‍රියාවලියකට වඩා ත්‍රෙඩ් සෑදීම හා අවසන් කිරීම මන්දගාමී වීම.', 'ත්‍රෙඩ් වලදී සන්දර්භ මාරු කිරීම ක්‍රියාවලියකට වඩා මන්දගාමී වීම.', 'සම්පත් බෙදාගැනීම නොහැකි වීම.', 'A', 'ai'),
(9, 14, '\"සන්දර්භ මාරු කිරීම\" (Context Switching) යනු කුමක්ද?', 'කාර්යයන් එකිනෙකට ගැටීමකින් තොරව එක් කාර්යයකින් තවත් කාර්යයකට මාරු වීමට CPU අනුගමනය කරන ක්‍රියා පටිපාටියක්.', 'දත්ත සුරැකීමට භාවිතා කරන ක්‍රමයක්.', 'නව ත්‍රෙඩ් එකක් නිර්මාණය කිරීමේ ක්‍රියාවලිය.', 'මෙහෙයුම් පද්ධතියේ දෝෂයක්.', 'A', 'ai'),
(10, 14, 'බහු-ත්‍රෙඩින් (Multithreading) වල එක් වාසියක් කුමක්ද?', 'සම්පත් බෙදාගැනීම.', 'එක් ක්‍රියාවලියකට පමණක් සීමා වීම.', 'අඩු කාර්ය සාධනය.', 'ත්‍රෙඩ් අතර ඉහළ මෙහෙයුම් පිරිවැය.', 'A', 'ai');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_submissions`
--

CREATE TABLE `quiz_submissions` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `score_percentage` decimal(5,2) NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `student_name` varchar(100) DEFAULT NULL,
  `registered_grade` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `registered_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `qr_token` varchar(100) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `student_name`, `registered_grade`, `phone`, `address`, `registered_date`, `qr_token`, `photo`) VALUES
(6, 'Nishara De Silva', 'Grade 6', '098765432', 'Galle', '2026-03-04 05:11:37', 'STU_562084', 'uploads/1778909056_ChatGPT Image May 16, 2026, 10_53_52 AM.png'),
(7, 'Ninethmi', 'Grade 6', '0766801989', 'Galle', '2026-03-04 05:11:37', 'STU_139526', ''),
(8, 'Piyumi', 'Grade 6', '098765432', 'Hikkaduwa', '2026-03-04 05:24:41', 'STU_11379', ''),
(9, 'chesandu', 'Grade 4', '098765432', 'Hikkaduwa', '2026-04-20 10:40:25', 'STU_638319', 'uploads/1776685677_Gemini_Generated_Image_efsh2aefsh2aefsh.png'),
(10, 'sashika', 'Grade 8', '098765432', 'Hikkaduwaq', '2026-04-20 12:10:04', 'STU_157456', 'uploads/1776687004_IMG-20251026-WA0022.jpg'),
(11, 'Kavindu', 'Grade 6', '0771111111', 'Galle', '2026-05-19 04:44:34', 'STU_111111', ''),
(12, 'Nethmi', 'Grade 6', '0772222222', 'Matara', '2026-05-19 04:44:34', 'STU_222222', ''),
(13, 'Senuja', 'Grade 6', '0773333333', 'Colombo', '2026-05-19 04:44:34', 'STU_333333', ''),
(14, 'Tharushi', 'Grade 6', '0774444444', 'Galle', '2026-05-19 04:44:34', 'STU_444444', ''),
(15, 'Yasiru', 'Grade 6', '0775555555', 'Kalutara', '2026-05-19 04:44:34', 'STU_555555', ''),
(20, 'Parami', 'Grade 9', '0765432111', 'Hikkaduwa', '2026-06-01 11:03:46', 'STU_00020', '');

-- --------------------------------------------------------

--
-- Table structure for table `student_answers`
--

CREATE TABLE `student_answers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_option` char(1) NOT NULL,
  `submitted_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_attempts`
--

CREATE TABLE `student_attempts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `attempted_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_attempts`
--

INSERT INTO `student_attempts` (`id`, `user_id`, `material_id`, `attempted_at`) VALUES
(1, 1, 11, '2026-06-17 17:59:41'),
(2, 6, 11, '2026-06-17 18:58:10'),
(3, 6, 11, '2026-06-17 18:58:10');

-- --------------------------------------------------------

--
-- Table structure for table `student_classes`
--

CREATE TABLE `student_classes` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_classes`
--

INSERT INTO `student_classes` (`id`, `student_id`, `class_id`) VALUES
(29, 14, 29),
(33, 20, 32),
(38, 15, 29),
(39, 15, 33),
(40, 15, 38),
(41, 15, 44),
(42, 6, 29),
(43, 6, 33),
(44, 6, 38),
(45, 6, 44),
(46, 10, 31),
(47, 10, 35),
(48, 10, 40),
(49, 8, 29),
(50, 8, 33),
(51, 8, 38),
(52, 7, 29),
(53, 7, 59),
(54, 13, 29),
(55, 13, 33),
(56, 12, 29),
(57, 12, 59),
(58, 11, 29),
(59, 11, 33),
(60, 9, 53);

-- --------------------------------------------------------

--
-- Table structure for table `student_notifications`
--

CREATE TABLE `student_notifications` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_notifications`
--

INSERT INTO `student_notifications` (`id`, `student_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 6, 'New Inquiry from Student (ID: 6)', 'dggt', 0, '2026-05-26 05:37:40'),
(2, 6, 'New Inquiry from Student (ID: 6)', 'dggt', 0, '2026-05-26 05:39:49'),
(3, 6, 'New Inquiry from Student (ID: 6)', 'dggt', 0, '2026-05-26 05:41:46'),
(4, 6, 'New Inquiry from Student (ID: 6)', 'dggt', 0, '2026-05-26 05:43:46'),
(5, 6, 'New Inquiry from Student (ID: 6)', 'dggt', 0, '2026-05-26 05:44:01'),
(6, 6, 'New Inquiry from Student (ID: 6)', 'dggt', 0, '2026-05-26 05:45:17'),
(7, 6, 'New Inquiry from Student (ID: 6)', 'dggt', 0, '2026-05-26 05:45:58'),
(8, 6, 'New Inquiry from Student (ID: 6)', 'dggt', 0, '2026-05-26 05:46:10'),
(9, 6, 'New Inquiry from Student (ID: 6)', 'dggt', 0, '2026-05-26 05:46:27'),
(10, 6, 'New Inquiry from Student (ID: 6)', 'dggt', 0, '2026-05-26 05:47:26'),
(11, 6, 'New Inquiry from Student (ID: 6)', 'dggt', 0, '2026-05-26 05:47:58'),
(12, 6, 'New Inquiry from Student (ID: 6)', 'dggt', 0, '2026-05-26 05:48:59'),
(13, 6, 'New Inquiry from Student (ID: 6)', 'dggt', 0, '2026-05-26 05:49:56');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `id_num` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `subject` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `id_num`, `name`, `phone`, `email`, `subject`) VALUES
(1, '198524103658', 'Saranga Piyumal', '0712345678', 'saranga@gmail.com', ''),
(2, '199215804796', 'Ravindu Maduranga', '0723456789', 'ravindu@gmail.com', ''),
(3, '197826901435', 'Suranjith Withanage', '0754567890', 'suranjith@gmail.com', ''),
(4, '885412369V', 'Lalani Wijesinghe', '0765678901', 'lalani@gmail.com', ''),
(5, '199514702589', 'Thilina Nayanajith', '0776789012', 'thilina@gmail.com', ''),
(6, '198102503698', 'Ranil Gunaratne', '0787890123', 'ranil@gmail.com', ''),
(7, '198904501236', 'Chamin Thushara', '0708901234', 'chamin@gmail.com', ''),
(8, '916523410V', 'Udani Miss', '0719012345', 'udani@gmail.com', ''),
(9, '198615402369', 'Sumudu Priyashantha', '0720123456', 'sumudu@gmail.com', ''),
(10, '197902301458', 'Sanjeewa de Silva', '0751234567', 'sanjeewa@gmail.com', ''),
(11, '199411203654', 'Shashika Jayawardena', '0762345678', 'shashika@gmail.com', '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `name`, `role`) VALUES
(1, 'admin01', '12345', 'Admin Sir', 'admin'),
(2, 'teacher01', '12345', 'Amal Teacher', 'teacher'),
(3, 'student01', '12345', 'Kasun Perera', 'student'),
(4, 'assistant01', '12345', 'Assistant User', 'assistant'),
(5, 'superadmin01', '12345', 'Super Admin Sir', 'superadmin');

-- --------------------------------------------------------

--
-- Structure for view `fee_transfers`
--
DROP TABLE IF EXISTS `fee_transfers`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `fee_transfers`  AS SELECT `payment_transfers`.`id` AS `id`, `payment_transfers`.`student_id` AS `student_id`, `payment_transfers`.`class_id` AS `class_id`, `payment_transfers`.`from_month` AS `from_month`, `payment_transfers`.`to_month` AS `target_month`, `payment_transfers`.`amount` AS `amount`, `payment_transfers`.`reason` AS `reason`, `payment_transfers`.`status` AS `status`, `payment_transfers`.`created_by` AS `created_by`, `payment_transfers`.`created_at` AS `created_at` FROM `payment_transfers` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`,`class_id`,`date`),
  ADD KEY `fk_class` (`class_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_teacher` (`teacher_id`);

--
-- Indexes for table `class_grades`
--
ALTER TABLE `class_grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_class_grade` (`class_id`,`grade`);

--
-- Indexes for table `class_materials`
--
ALTER TABLE `class_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `class_timetable`
--
ALTER TABLE `class_timetable`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_timetable_class` (`class_id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD PRIMARY KEY (`result_id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notice_class` (`class_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_payment` (`student_id`,`class_id`,`month`);

--
-- Indexes for table `payment_transfers`
--
ALTER TABLE `payment_transfers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `material_id` (`material_id`);

--
-- Indexes for table `quiz_submissions`
--
ALTER TABLE `quiz_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `qr_code` (`qr_token`);

--
-- Indexes for table `student_answers`
--
ALTER TABLE `student_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `student_attempts`
--
ALTER TABLE `student_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_classes`
--
ALTER TABLE `student_classes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_notifications`
--
ALTER TABLE `student_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_num` (`id_num`);

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
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=268;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `class_grades`
--
ALTER TABLE `class_grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `class_materials`
--
ALTER TABLE `class_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `class_timetable`
--
ALTER TABLE `class_timetable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `exam_results`
--
ALTER TABLE `exam_results`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notices`
--
ALTER TABLE `notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;

--
-- AUTO_INCREMENT for table `payment_transfers`
--
ALTER TABLE `payment_transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `quiz_submissions`
--
ALTER TABLE `quiz_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `student_answers`
--
ALTER TABLE `student_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_attempts`
--
ALTER TABLE `student_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `student_classes`
--
ALTER TABLE `student_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `student_notifications`
--
ALTER TABLE `student_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `fk_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `fk_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `class_grades`
--
ALTER TABLE `class_grades`
  ADD CONSTRAINT `class_grades_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `class_materials`
--
ALTER TABLE `class_materials`
  ADD CONSTRAINT `class_materials_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `class_timetable`
--
ALTER TABLE `class_timetable`
  ADD CONSTRAINT `fk_timetable_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD CONSTRAINT `exam_results_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_results_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `notices`
--
ALTER TABLE `notices`
  ADD CONSTRAINT `fk_notice_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_transfers`
--
ALTER TABLE `payment_transfers`
  ADD CONSTRAINT `payment_transfers_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_transfers_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD CONSTRAINT `quiz_questions_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `class_materials` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_submissions`
--
ALTER TABLE `quiz_submissions`
  ADD CONSTRAINT `quiz_submissions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_answers`
--
ALTER TABLE `student_answers`
  ADD CONSTRAINT `student_answers_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_notifications`
--
ALTER TABLE `student_notifications`
  ADD CONSTRAINT `student_notifications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
