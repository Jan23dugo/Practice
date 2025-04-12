-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 13, 2025 at 12:02 AM
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
-- Database: `exam`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `email`, `password`, `created_at`) VALUES
(1, 'pupccisfaculty@gmail.com', '$2y$10$jR4Qh.5YujukJwhIvGF24uOQbtdWL1fnjkoz06O3Fh32W/k9Ypn0C', '2025-03-16 13:11:55');

-- --------------------------------------------------------

--
-- Table structure for table `admin_login_attempts`
--

CREATE TABLE `admin_login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `last_attempt` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_login_attempts`
--

INSERT INTO `admin_login_attempts` (`id`, `email`, `login_attempts`, `last_attempt`) VALUES
(1, 'pupccisfaculty@gmail.com', 0, '2025-04-11 14:56:51');

-- --------------------------------------------------------

--
-- Table structure for table `admin_login_logs`
--

CREATE TABLE `admin_login_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_login_logs`
--

INSERT INTO `admin_login_logs` (`id`, `admin_id`, `email`, `ip_address`, `status`, `created_at`) VALUES
(1, 1, 'pupccisfaculty@gmail.com', '::1', 'success', '2025-03-16 13:13:05'),
(2, 1, 'pupccisfaculty@gmail.com', '::1', 'success', '2025-03-17 16:10:02'),
(3, 1, 'pupccisfaculty@gmail.com', '::1', 'success', '2025-03-20 01:58:06'),
(4, 1, 'pupccisfaculty@gmail.com', '::1', 'success', '2025-03-20 06:32:31'),
(5, 1, 'pupccisfaculty@gmail.com', '::1', 'success', '2025-03-30 02:22:54'),
(6, 1, 'pupccisfaculty@gmail.com', '::1', 'success', '2025-04-01 06:56:46'),
(7, 1, 'pupccisfaculty@gmail.com', '::1', 'success', '2025-04-04 05:50:48'),
(8, 1, 'pupccisfaculty@gmail.com', '::1', 'success', '2025-04-11 14:57:38');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `created_at`, `updated_at`, `status`) VALUES
(1, 'CCIS Qualifying Exam Schedule', 'Please be informed that the CCIS qualifying exam registration will be opened on March 17 - 20. Make sure to submit the necessary documents and information for the registering in qualifying exam.', '2025-03-16 10:37:24', '2025-03-16 10:37:24', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `answers`
--

CREATE TABLE `answers` (
  `answer_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_text` text NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `position` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `answers`
--

INSERT INTO `answers` (`answer_id`, `question_id`, `answer_text`, `is_correct`, `position`) VALUES
(130, 53, 'fsdfsadfa', 1, 0),
(131, 53, 'fbfbbgsdfg', 0, 1),
(132, 53, 'dsfgsdfgdfg', 0, 2),
(133, 53, 'gdsfgsdgsdfg', 0, 3),
(134, 57, 'True', 0, 0),
(135, 57, 'False', 1, 1),
(136, 59, 'True', 1, 0),
(137, 59, 'False', 0, 1),
(139, 60, '4', 1, 0),
(140, 60, '3', 0, 1),
(141, 60, '5', 0, 2),
(142, 60, '6', 0, 3),
(146, 62, 'True', 1, 0),
(147, 62, 'False', 0, 1),
(148, 63, '4', 1, 0),
(149, 63, '3', 0, 1),
(150, 63, '5', 0, 2),
(151, 63, '6', 0, 3),
(156, 68, 'Central Processing Unit', 1, 0),
(157, 68, 'Computer Processing Unit', 0, 1),
(158, 68, 'Central Peripheral Unit', 0, 2),
(159, 68, 'Control Processing Unit', 0, 3),
(160, 69, 'ROM', 0, 0),
(161, 69, 'Hard Drive', 0, 1),
(162, 69, 'RAM', 1, 2),
(163, 69, 'SSD', 0, 3),
(164, 70, 'Manage hardware and software resources', 1, 0),
(165, 70, 'Create documents and spreadsheets', 0, 1),
(166, 70, 'Connect to the internet', 0, 2),
(167, 70, 'Store data permanently', 0, 3),
(168, 71, 'HyperText Transfer Protocol', 1, 0),
(169, 71, 'HyperText Transmission Process', 0, 1),
(170, 71, 'High Transfer Technology Protocol', 0, 2),
(171, 71, 'Hyperlink Text Technology Protocol', 0, 3),
(172, 72, 'Windows 11', 0, 0),
(173, 72, 'macOS', 0, 1),
(174, 72, 'Linux', 1, 2),
(175, 72, 'Chrome OS', 0, 3),
(176, 73, 'Store data securely', 0, 0),
(177, 73, 'Prevent unauthorized access', 1, 1),
(178, 73, 'Speed up internet connections', 0, 2),
(179, 73, 'Boost Wi-Fi signals', 0, 3),
(180, 74, 'A method to terminate a program', 0, 0),
(181, 74, 'A function that runs only once', 0, 1),
(182, 74, 'A structure that repeats a set of instructions', 1, 2),
(183, 74, 'A syntax error', 0, 3),
(184, 75, 'MySQL', 1, 0),
(185, 75, 'Python', 0, 1),
(186, 75, 'JavaScript', 0, 2),
(187, 75, 'HTML', 0, 3),
(188, 76, 'Internet Provider', 0, 0),
(189, 76, 'Internet Protocol', 1, 1),
(190, 76, 'Internal Processing', 0, 2),
(191, 76, 'Interconnected Program', 0, 3),
(192, 77, 'C++', 0, 0),
(193, 77, 'Java', 0, 1),
(194, 77, 'HTML', 1, 2),
(195, 77, 'Assembly', 0, 3),
(196, 78, 'True', 1, 0),
(197, 78, 'False', 0, 1),
(198, 79, 'True', 0, 0),
(199, 79, 'False', 1, 1),
(200, 80, 'True', 1, 0),
(201, 80, 'False', 0, 1),
(202, 81, 'True', 0, 0),
(203, 81, 'False', 1, 1),
(204, 82, 'True', 1, 0),
(205, 82, 'False', 0, 1),
(206, 83, 'True', 0, 0),
(207, 83, 'False', 1, 1),
(208, 84, 'True', 1, 0),
(209, 84, 'False', 0, 1),
(210, 85, 'True', 0, 0),
(211, 85, 'False', 1, 1),
(212, 86, 'True', 1, 0),
(213, 86, 'False', 0, 1),
(214, 87, 'True', 1, 0),
(215, 87, 'False', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `coded_courses`
--

CREATE TABLE `coded_courses` (
  `course_id` int(11) NOT NULL,
  `subject_code` varchar(100) NOT NULL,
  `subject_description` varchar(255) NOT NULL,
  `program` varchar(50) DEFAULT NULL,
  `units` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `coded_courses`
--

INSERT INTO `coded_courses` (`course_id`, `subject_code`, `subject_description`, `program`, `units`) VALUES
(25, 'ACCO 20213', 'Accounting Principles', 'BSIT', 3.00),
(26, 'COMP 20033', 'Computer Programming 2', 'BSIT', 3.00),
(27, 'COMP 20013', 'Introduction to Computing', 'BSIT', 3.00),
(28, 'COMP 20023', 'Computer Programming 1', 'BSIT', 3.00),
(29, 'CWTS 10013', 'Civic Welfare Training Service 1', 'BSIT', 3.00),
(30, 'GEED 10053', 'Mathematics in the Modern World', 'BSIT', 3.00),
(31, 'GEED 10063', 'Purposive Communication', 'BSIT', 3.00),
(32, 'GEED 10103', 'Filipinolohiya at Pambansang Kaunlaran', 'BSIT', 3.00),
(33, 'PHED 10012', 'Physical Fitness and Self-Testing Activities', 'BSIT', 2.00),
(34, 'COMP 20043', 'Discrete Structures 1', 'BSIT', 3.00),
(35, 'CWTS 10023', 'Civic Welfare Training Service 2', 'BSIT', 3.00),
(36, 'GEED 10033', 'Readings in Philippine History', 'BSIT', 3.00),
(37, 'GEED 10113', 'Pagsasalin sa Kontekstong Filipino', 'BSIT', 3.00),
(38, 'GEED 20023', 'Politics, Governance and Citizenship', 'BSIT', 3.00),
(39, 'PHED 10022', 'Rhythmic Activities', 'BSIT', 2.00),
(40, 'GEED 005', 'Purposive Communication', 'BSCS', 3.00),
(41, 'GEED 004', 'Mathematics in the Modern World', 'BSCS', 3.00),
(42, 'GEED 032', 'Filipinolohiya at Pambansang Kaunlaran', 'BSCS', 3.00),
(43, 'GEED 020', 'Politics, Governance and Citizenship', 'BSCS', 3.00),
(44, 'COMP 001', 'Introduction to Computing', 'BSCS', 3.00),
(45, 'COMP 002', 'Computer Programming 1', 'BSCS', 3.00),
(46, 'PATHFit 1', 'Physical Activities Towards Health and Fitness 1', 'BSCS', 2.00),
(47, 'NSTP 001', 'National Service Training Program 1', 'BSCS', 3.00),
(48, 'GEED 007', 'Science, Technology and Society', 'BSCS', 3.00),
(49, 'GEED 033', 'Pagsasalin sa Kontekstong Filipino', 'BSCS', 3.00),
(50, 'GEED 012', 'Intelektwaslisasyon ng Filipino sa ibat ibang Larangan', 'BSCS', 3.00),
(51, 'MATH 017', 'Differential Calculus for Computer Science Students', 'BSCS', 3.00),
(52, 'GEED 001', 'Understanding the Self', 'BSCS', 3.00),
(53, 'PATHFit 2', 'Physical Activities Towards Health and Fitness 2', 'BSCS', 2.00),
(54, 'NSTP 002', 'National Service Training Program 2', 'BSCS', 3.00);

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `exam_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `exam_type` enum('tech','non-tech') NOT NULL,
  `is_scheduled` tinyint(1) DEFAULT 0,
  `scheduled_date` date DEFAULT NULL,
  `scheduled_time` time DEFAULT NULL,
  `duration` int(11) DEFAULT 60 COMMENT 'Duration in minutes',
  `cover_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `randomize_questions` tinyint(1) DEFAULT 0,
  `randomize_choices` tinyint(1) DEFAULT 0,
  `passing_score_type` enum('percentage','points') DEFAULT NULL,
  `passing_score` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`exam_id`, `title`, `description`, `exam_type`, `is_scheduled`, `scheduled_date`, `scheduled_time`, `duration`, `cover_image`, `created_at`, `updated_at`, `randomize_questions`, `randomize_choices`, `passing_score_type`, `passing_score`) VALUES
(21, 'This is the final test', 'No more bugs', 'non-tech', 1, '2025-04-04', '23:42:00', 60, 'assets/images/default-exam-cover.jpg', '2025-04-02 06:19:49', '2025-04-03 03:47:12', 1, 1, 'percentage', 70),
(23, 'Programming ', 'dddddd', 'non-tech', 1, '2025-04-04', '08:40:00', 60, '0', '2025-04-03 05:16:09', '2025-04-03 11:39:49', 0, 0, 'percentage', 70),
(24, 'Tech Exam', 'Carefully answer the questions', 'non-tech', 1, '2025-04-30', '09:30:00', 60, 'uploads/covers/1743727309_images (2).jpg', '2025-04-03 23:41:56', '2025-04-11 13:38:55', 1, 1, 'percentage', 70),
(28, 'Untitled Quiz', '', 'tech', 0, NULL, NULL, 60, '0', '2025-04-11 11:28:49', '2025-04-11 11:28:49', 0, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `exam_assignments`
--

CREATE TABLE `exam_assignments` (
  `assignment_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `assigned_date` datetime DEFAULT current_timestamp(),
  `completion_status` enum('pending','completed','expired') DEFAULT 'pending',
  `is_released` tinyint(1) DEFAULT 0,
  `completion_time` datetime DEFAULT NULL,
  `passed` tinyint(1) DEFAULT NULL,
  `final_score` decimal(5,2) DEFAULT NULL,
  `auto_submitted` tinyint(1) DEFAULT 0,
  `auto_submission_reason` varchar(255) DEFAULT NULL,
  `answered_questions` int(11) DEFAULT 0,
  `total_questions` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exam_assignments`
--

INSERT INTO `exam_assignments` (`assignment_id`, `exam_id`, `student_id`, `assigned_date`, `completion_status`, `is_released`, `completion_time`, `passed`, `final_score`, `auto_submitted`, `auto_submission_reason`, `answered_questions`, `total_questions`) VALUES
(54, 23, 79, '2025-04-03 19:39:49', 'completed', 0, '2025-04-03 19:41:12', 1, 100.00, 0, NULL, 1, 1),
(55, 24, 79, '2025-04-04 07:47:58', 'completed', 0, '2025-04-04 08:56:21', 0, 9.09, 0, NULL, 1, 21),
(56, 24, 80, '2025-04-11 21:38:55', 'pending', 0, NULL, NULL, NULL, 0, NULL, 0, 0),
(57, 24, 81, '2025-04-11 21:38:55', 'pending', 0, NULL, NULL, NULL, 0, NULL, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `exam_results`
--

CREATE TABLE `exam_results` (
  `result_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `score` int(11) DEFAULT NULL,
  `total_questions` int(11) DEFAULT NULL,
  `completion_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `matched_courses`
--

CREATE TABLE `matched_courses` (
  `matched_id` int(11) NOT NULL,
  `subject_code` varchar(100) NOT NULL,
  `original_code` varchar(20) DEFAULT NULL,
  `subject_description` varchar(255) NOT NULL,
  `units` decimal(5,2) NOT NULL,
  `grade` decimal(4,2) DEFAULT NULL,
  `student_id` int(11) NOT NULL,
  `matched_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `programming_questions`
--

CREATE TABLE `programming_questions` (
  `programming_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `starter_code` text DEFAULT NULL,
  `language` varchar(50) DEFAULT 'python'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `programming_questions`
--

INSERT INTO `programming_questions` (`programming_id`, `question_id`, `starter_code`, `language`) VALUES
(17, 66, 'sdafsdf', 'python'),
(18, 67, 'def is_prime(n: int) -> str:\r\n    # Complete the code below\r\n    # The function should return \"Prime\" if the number is prime, \r\n    # and \"Not Prime\" if the number is not prime.\r\n    pass', 'python');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `question_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple-choice','true-false','programming') NOT NULL,
  `points` int(11) DEFAULT 1,
  `position` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`question_id`, `exam_id`, `question_text`, `question_type`, `points`, `position`, `created_at`, `updated_at`) VALUES
(53, 21, '<p><strong>dadasfsdadfsf</strong></p>', 'multiple-choice', 1, 0, '2025-04-02 17:58:05', '2025-04-02 19:26:16'),
(57, 21, '<p><strong>fadsfasdfasd</strong></p>', 'true-false', 1, 0, '2025-04-02 19:19:37', '2025-04-02 19:30:37'),
(59, 21, 'The sky is blue.', 'true-false', 1, 2, '2025-04-02 20:28:38', '2025-04-02 20:28:38'),
(60, 21, 'What is 2+2?', 'multiple-choice', 1, 3, '2025-04-02 20:28:44', '2025-04-02 20:28:44'),
(62, 21, 'The sky is blue.', 'true-false', 2, 5, '2025-04-02 20:28:59', '2025-04-02 20:28:59'),
(63, 21, 'What is 2+2?', 'multiple-choice', 2, 6, '2025-04-02 20:28:59', '2025-04-02 20:28:59'),
(66, 23, '<p>dfsfasfdadf</p>', 'programming', 1, 0, '2025-04-03 19:38:53', '2025-04-03 19:38:53'),
(67, 24, '<p>Write a function to check whether a number is prime or not.<br><br>A prime number is a natural number greater than 1 that cannot be formed by multiplying two smaller natural numbers. Write a function is_prime(n: int) -&gt; str that checks if a number n is prime and returns \"Prime\" if it is, and \"Not Prime\" if it isn\'t.</p>', 'programming', 2, 0, '2025-04-04 07:46:25', '2025-04-04 07:46:25'),
(68, 24, '<p>What does CPU stand for?</p>', 'multiple-choice', 1, 0, '2025-04-04 08:30:42', '2025-04-04 08:30:42'),
(69, 24, '<p>Which of the following is a type of volatile memory?</p>', 'multiple-choice', 1, 0, '2025-04-04 08:31:38', '2025-04-04 08:31:38'),
(70, 24, '<p>What is the main function of an operating system?</p>', 'multiple-choice', 1, 0, '2025-04-04 08:32:44', '2025-04-04 08:32:44'),
(71, 24, '<p>What does HTTP stand for?</p>', 'multiple-choice', 1, 0, '2025-04-04 08:33:33', '2025-04-04 08:33:33'),
(72, 24, '<p>Which of the following is an example of an open-source operating system?</p>', 'multiple-choice', 1, 0, '2025-04-04 08:34:23', '2025-04-04 08:34:23'),
(73, 24, '<p>What is the primary purpose of a firewall in a network?</p>', 'multiple-choice', 1, 0, '2025-04-04 08:35:10', '2025-04-04 08:35:10'),
(74, 24, '<p>In programming, what does \"loop\" refer to?</p>', 'multiple-choice', 1, 0, '2025-04-04 08:35:51', '2025-04-04 08:35:51'),
(75, 24, '<p>Which of the following is a relational database management system?</p>', 'multiple-choice', 1, 0, '2025-04-04 08:36:38', '2025-04-04 08:36:38'),
(76, 24, '<p>What does IP in \"IP Address\" stand for?</p>', 'multiple-choice', 1, 0, '2025-04-04 08:37:14', '2025-04-04 08:37:14'),
(77, 24, '<p>Which of these programming languages is primarily used for web development?</p>', 'multiple-choice', 1, 0, '2025-04-04 08:38:03', '2025-04-04 08:38:03'),
(78, 24, '<p>The BIOS is responsible for loading the operating system when a computer starts.</p>', 'true-false', 1, 0, '2025-04-04 08:38:20', '2025-04-04 08:38:20'),
(79, 24, '<p>Cloud computing eliminates the need for all physical storage devices.</p>', 'true-false', 1, 0, '2025-04-04 08:38:34', '2025-04-04 08:38:34'),
(80, 24, '<p>A VPN (Virtual Private Network) encrypts internet traffic to provide security and privacy.</p>', 'true-false', 1, 0, '2025-04-04 08:38:48', '2025-04-04 08:38:48'),
(81, 24, '<p>IPv6 addresses are shorter than IPv4 addresses.</p>', 'true-false', 1, 0, '2025-04-04 08:39:07', '2025-04-04 08:39:07'),
(82, 24, '<p>Machine learning is a subset of artificial intelligence.</p>', 'true-false', 1, 0, '2025-04-04 08:39:23', '2025-04-04 08:39:23'),
(83, 24, '<p>The Internet and the World Wide Web (WWW) are the same thing.</p>', 'true-false', 1, 0, '2025-04-04 08:39:33', '2025-04-04 08:39:33'),
(84, 24, '<p>SQL is used to manage and query databases.</p>', 'true-false', 1, 0, '2025-04-04 08:39:51', '2025-04-04 08:39:51'),
(85, 24, '<p>JavaScript can only be used for front-end development.</p>', 'true-false', 1, 0, '2025-04-04 08:40:07', '2025-04-04 08:40:07'),
(86, 24, '<p>A Trojan horse is a type of malware that disguises itself as a legitimate program.</p>', 'true-false', 1, 0, '2025-04-04 08:40:22', '2025-04-04 08:40:22'),
(87, 24, '<p>Binary code is composed of only two digits: 0 and 1.</p>', 'true-false', 1, 0, '2025-04-04 08:40:35', '2025-04-04 08:40:35');

-- --------------------------------------------------------

--
-- Table structure for table `question_bank`
--

CREATE TABLE `question_bank` (
  `question_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple-choice','true-false','programming') NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `points` int(11) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `question_bank`
--

INSERT INTO `question_bank` (`question_id`, `question_text`, `question_type`, `category`, `points`, `created_at`, `updated_at`) VALUES
(11, 'What is 2+2?', 'multiple-choice', 'Math', 1, '2025-03-16 00:25:04', '2025-03-16 00:25:04'),
(12, 'The sky is blue.', 'true-false', 'General', 1, '2025-03-16 00:25:05', '2025-03-16 00:25:05'),
(13, 'Write a function that returns the sum of two numbers.', 'programming', 'Programming', 2, '2025-03-16 00:25:05', '2025-03-16 00:25:05');

-- --------------------------------------------------------

--
-- Table structure for table `question_bank_answers`
--

CREATE TABLE `question_bank_answers` (
  `answer_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_text` text NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `position` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `question_bank_answers`
--

INSERT INTO `question_bank_answers` (`answer_id`, `question_id`, `answer_text`, `is_correct`, `position`) VALUES
(17, 11, '4', 1, 0),
(18, 11, '3', 0, 1),
(19, 11, '5', 0, 2),
(20, 11, '6', 0, 3),
(21, 12, 'True', 1, 0),
(22, 12, 'False', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `question_bank_programming`
--

CREATE TABLE `question_bank_programming` (
  `programming_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `starter_code` text DEFAULT NULL,
  `language` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `question_bank_programming`
--

INSERT INTO `question_bank_programming` (`programming_id`, `question_id`, `starter_code`, `language`) VALUES
(6, 13, 'def add(a, b):\n    pass', 'python');

-- --------------------------------------------------------

--
-- Table structure for table `question_bank_test_cases`
--

CREATE TABLE `question_bank_test_cases` (
  `test_case_id` int(11) NOT NULL,
  `programming_id` int(11) NOT NULL,
  `input` text DEFAULT NULL,
  `expected_output` text NOT NULL,
  `is_hidden` tinyint(1) DEFAULT 0,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `question_bank_test_cases`
--

INSERT INTO `question_bank_test_cases` (`test_case_id`, `programming_id`, `input`, `expected_output`, `is_hidden`, `description`) VALUES
(9, 6, ' 5, 3', '8', 0, ''),
(10, 6, '10, 20', '30', 1, 'Test with larger numbers ');

-- --------------------------------------------------------

--
-- Table structure for table `register_studentsqe`
--

CREATE TABLE `register_studentsqe` (
  `student_id` int(11) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `contact_number` varchar(11) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `student_type` varchar(100) DEFAULT NULL,
  `previous_school` varchar(255) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `previous_program` varchar(255) DEFAULT NULL,
  `desired_program` varchar(255) DEFAULT NULL,
  `tor` varchar(255) DEFAULT NULL,
  `school_id` varchar(255) DEFAULT NULL,
  `reference_id` varchar(255) DEFAULT NULL,
  `is_tech` tinyint(1) DEFAULT NULL,
  `registration_date` timestamp NULL DEFAULT current_timestamp(),
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `stud_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `register_studentsqe`
--

INSERT INTO `register_studentsqe` (`student_id`, `last_name`, `first_name`, `middle_name`, `gender`, `dob`, `email`, `contact_number`, `street`, `student_type`, `previous_school`, `year_level`, `previous_program`, `desired_program`, `tor`, `school_id`, `reference_id`, `is_tech`, `registration_date`, `status`, `stud_id`) VALUES
(79, 'Dugo', 'Janlloyd', 'Yamoyam', 'Male', '2025-03-20', 'jdugo23@gmail.com', '09667311122', 'C Raymundo Ave', 'transferee', 'University of Perpetual', '1', 'Bachelor of Science in Physics (BSP)', 'BSIT', 'uploads/tor/Screenshot 2024-11-08 194747.png', 'uploads/school_id/449292499_481139171214456_1108316609890498134_n.png', 'CCIS-2025-64341', 0, '2025-03-11 14:02:42', 'accepted', 1),
(80, 'fff', 'fvvvv', 'a', 'Male', '2025-04-30', 'janlloydydugo@iskolarngbayan.pup.edu.ph', '09667311956', 'c raymundo', 'transferee', 'Polytechnic University of the Philippines', '1', 'Bachelor in Advertising and Public Relation (BAPR)', 'BSIT', 'uploads/tor/Screenshot 2024-10-18 080627.png', 'uploads/school_id/449292499_481139171214456_1108316609890498134_n.png', 'CCIS-2025-50935', 0, '2025-04-04 05:49:14', 'accepted', 3),
(81, 'lesly', 'dugo', 'C.', 'Female', '2025-04-01', 'janlloyddugo3@gmail.com', '09667311956', 'c raymundo', 'transferee', 'Technological University of the Philippines', '1', 'Bachelor of Science in Entrepreneurship (BSEntrep)', 'BSIT', 'uploads/tor/Screenshot 2024-11-10 192747.png', 'uploads/school_id/449467909_1205163000647861_8408110911620157242_n.png', 'CCIS-2025-36864', 0, '2025-04-04 06:14:22', 'pending', 4);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `stud_id` int(11) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_picture` varchar(255) DEFAULT NULL,
  `contact_number` varchar(11) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`stud_id`, `firstname`, `lastname`, `email`, `password`, `created_at`, `profile_picture`, `contact_number`, `address`, `date_of_birth`, `gender`) VALUES
(1, 'Janlloyd', 'Dugo', 'jdugo23@gmail.com', '$2y$10$hk6iwl9PFJVdozSiQNOBLuWsgkNQnjDuvgx0dMVag38BFjN0fI8Fu', '2025-03-08 23:48:15', NULL, NULL, NULL, NULL, NULL),
(2, 'Janlloyd', 'Dugong', 'janlloyddugo101@gmail.com', '$2y$10$74HkKhTuM/tAo9cUnv6CG.t4QwARhnkYuJevV.Dm580WHU.N2pxoS', '2025-03-09 07:12:58', NULL, NULL, NULL, NULL, NULL),
(3, 'Jan', 'Dugs', 'janlloydydugo@iskolarngbayan.pup.edu.ph', '$2y$10$xB4C/y0/sTs095cHn7pud.2k0..cfq1rN3h17xl29ZuVIv5mYfn/.', '2025-04-04 05:47:50', NULL, NULL, NULL, NULL, NULL),
(4, 'lloyd ', 'dugo', 'janlloyddugo3@gmail.com', '$2y$10$WPkERYgswQSkc9cSk97vPO0OXHIj2fVyAMABneFYljZQ2soLOAw..', '2025-04-04 06:05:08', NULL, NULL, NULL, NULL, NULL),
(5, 'jani', 'dugi', 'janlloyddugo11@gmail.com', '$2y$10$d4lhTMG6VVjp6iNX1nwNAOpbSeE.qjLWXFtWsecGDIY/GvfILUrKe', '2025-04-04 07:41:03', NULL, NULL, NULL, NULL, NULL),
(6, 'gan', 'llody', 'janlloyddugo2@gmail.com', '$2y$10$GSQ64peIt.8N1/U4XJmb5ujrf4oJ8SLl9m5QvXFXviKmkLT4xle5y', '2025-04-04 11:35:05', NULL, NULL, NULL, NULL, NULL),
(7, 'Melody', 'Tapay', 'melody.tapay04@gmail.com', '$2y$10$X061CZo44qNEZ74M2HY1xO2byfjLkYUoYRF5LLzX3gueMwj33Phwq', '2025-04-11 14:54:35', 'uploads/profile_pictures/profile_7.png', NULL, NULL, NULL, NULL),
(8, 'Alyanna', 'Soliman', 'melodyvtapay@gmail.com', '$2y$10$MRCsK1/IdiDyeT/k6hzj0enKiVNchdAOPDL7L2S9FQKRH3wADnnOK', '2025-04-12 21:40:48', NULL, '09686196577', 'Tagapo, CSRL', '2003-04-21', 'Female');

-- --------------------------------------------------------

--
-- Table structure for table `student_answers`
--

CREATE TABLE `student_answers` (
  `answer_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `programming_id` int(11) DEFAULT NULL,
  `programming_answer` text DEFAULT NULL,
  `answer_id_selected` int(11) DEFAULT NULL,
  `question_type` enum('multiple-choice','true-false','programming') NOT NULL,
  `submission_time` datetime DEFAULT current_timestamp(),
  `is_correct` tinyint(1) DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_answers`
--

INSERT INTO `student_answers` (`answer_id`, `student_id`, `exam_id`, `question_id`, `programming_id`, `programming_answer`, `answer_id_selected`, `question_type`, `submission_time`, `is_correct`, `score`) VALUES
(130, 79, 21, 53, NULL, NULL, NULL, 'multiple-choice', '2025-04-03 11:48:14', 1, 1.00),
(135, 79, 21, 57, NULL, NULL, NULL, 'true-false', '2025-04-03 11:48:14', 1, 1.00),
(136, 79, 21, 59, NULL, NULL, NULL, 'true-false', '2025-04-03 11:48:14', 1, 1.00),
(139, 79, 21, 60, NULL, NULL, NULL, 'multiple-choice', '2025-04-03 11:48:14', 1, 1.00),
(146, 79, 21, 62, NULL, NULL, NULL, 'true-false', '2025-04-03 11:48:14', 1, 2.00),
(148, 79, 21, 63, NULL, NULL, NULL, 'multiple-choice', '2025-04-03 11:48:14', 1, 2.00),
(149, 79, 23, 66, 17, 'import sys\r\n\r\n# Read all input lines (including hidden test cases)\r\nlines = sys.stdin.readlines()\r\n\r\n# Iterate over the test cases\r\nfor i, line in enumerate(lines, start=1):\r\n    # Split and convert the input numbers to integers\r\n    a, b = map(int, line.split())\r\n    \r\n    # Print the result\r\n    print(a + b)', NULL, 'programming', '2025-04-03 19:41:12', NULL, 1.00),
(150, 79, 24, 67, 18, 'def is_prime(n: int) -&gt; str:\n    # Complete the code below\n    # The function should return &quot;Prime&quot; if the number is prime, \n    # and &quot;Not Prime&quot; if the number is not prime.\n    pass', NULL, 'programming', '2025-04-04 08:56:21', NULL, 2.00);

-- --------------------------------------------------------

--
-- Table structure for table `test_cases`
--

CREATE TABLE `test_cases` (
  `test_case_id` int(11) NOT NULL,
  `programming_id` int(11) NOT NULL,
  `input` text DEFAULT NULL,
  `expected_output` text DEFAULT NULL,
  `is_hidden` tinyint(1) DEFAULT 0,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `test_cases`
--

INSERT INTO `test_cases` (`test_case_id`, `programming_id`, `input`, `expected_output`, `is_hidden`, `description`) VALUES
(42, 17, '1 2', '3', 1, ''),
(43, 17, '2 3', '5', 0, 'fdgasfds'),
(44, 18, '5', '\"Prime\" ', 1, ''),
(45, 18, '4', '\"Not Prime\" ', 0, ''),
(46, 18, '7 ', '\"Prime\" ', 0, '');

-- --------------------------------------------------------

--
-- Table structure for table `university_grading_systems`
--

CREATE TABLE `university_grading_systems` (
  `grading_id` int(11) NOT NULL,
  `university_name` varchar(255) NOT NULL,
  `min_percentage` decimal(5,2) NOT NULL,
  `max_percentage` decimal(5,2) NOT NULL,
  `grade_value` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `university_grading_systems`
--

INSERT INTO `university_grading_systems` (`grading_id`, `university_name`, `min_percentage`, `max_percentage`, `grade_value`) VALUES
(1, 'Polytechnic University of the Philippines', 97.00, 100.00, '1.00'),
(2, 'Polytechnic University of the Philippines', 94.00, 96.00, '1.25'),
(3, 'Polytechnic University of the Philippines', 91.00, 93.00, '1.50'),
(4, 'Polytechnic University of the Philippines', 88.00, 90.00, '1.75'),
(5, 'Polytechnic University of the Philippines', 85.00, 87.00, '2.00'),
(6, 'Polytechnic University of the Philippines', 82.00, 84.00, '2.25'),
(7, 'Polytechnic University of the Philippines', 79.00, 81.00, '2.50'),
(8, 'Polytechnic University of the Philippines', 76.00, 78.00, '2.75'),
(9, 'Polytechnic University of the Philippines', 75.00, 75.00, '3.00'),
(10, 'Polytechnic University of the Philippines', 65.00, 74.00, '4.00'),
(11, 'AMA University', 96.00, 100.00, 'A+'),
(12, 'AMA University', 91.00, 95.00, 'A'),
(13, 'AMA University', 86.00, 90.00, 'A-'),
(14, 'AMA University', 81.00, 85.00, 'B+'),
(15, 'AMA University', 75.00, 80.00, 'B'),
(16, 'AMA University', 69.00, 74.00, 'B-'),
(17, 'AMA University', 63.00, 68.00, 'C+'),
(18, 'AMA University', 57.00, 62.00, 'C'),
(19, 'AMA University', 50.00, 56.00, 'C-'),
(20, 'Technological University of the Philippines', 99.00, 100.00, '1.00'),
(21, 'Technological University of the Philippines', 96.00, 98.00, '1.25'),
(22, 'Technological University of the Philippines', 93.00, 95.00, '1.50'),
(23, 'Technological University of the Philippines', 90.00, 92.00, '1.75'),
(24, 'Technological University of the Philippines', 87.00, 89.00, '2.00'),
(25, 'Technological University of the Philippines', 84.00, 86.00, '2.25'),
(26, 'Technological University of the Philippines', 81.00, 83.00, '2.50'),
(27, 'Technological University of the Philippines', 78.00, 80.00, '2.75'),
(28, 'Technological University of the Philippines', 75.00, 77.00, '3.00'),
(29, 'University of Perpetual', 99.00, 100.00, '1.00'),
(30, 'University of Perpetual', 96.00, 98.00, '1.25'),
(31, 'University of Perpetual', 93.00, 95.00, '1.5'),
(32, 'University of Perpetual', 90.00, 92.00, '1.75'),
(33, 'University of Perpetual', 87.00, 89.00, '2.0'),
(34, 'University of Perpetual', 84.00, 86.00, '2.25'),
(35, 'University of Perpetual', 81.00, 83.00, '2.5'),
(36, 'University of Perpetual', 78.00, 80.00, '2.75'),
(37, 'University of Perpetual', 75.00, 77.00, '3.00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `admin_login_attempts`
--
ALTER TABLE `admin_login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `admin_login_logs`
--
ALTER TABLE `admin_login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `answers`
--
ALTER TABLE `answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `coded_courses`
--
ALTER TABLE `coded_courses`
  ADD PRIMARY KEY (`course_id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`exam_id`);

--
-- Indexes for table `exam_assignments`
--
ALTER TABLE `exam_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD PRIMARY KEY (`result_id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `matched_courses`
--
ALTER TABLE `matched_courses`
  ADD PRIMARY KEY (`matched_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `programming_questions`
--
ALTER TABLE `programming_questions`
  ADD PRIMARY KEY (`programming_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `question_bank`
--
ALTER TABLE `question_bank`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `idx_question_type` (`question_type`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `question_bank_answers`
--
ALTER TABLE `question_bank_answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `question_bank_programming`
--
ALTER TABLE `question_bank_programming`
  ADD PRIMARY KEY (`programming_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `question_bank_test_cases`
--
ALTER TABLE `question_bank_test_cases`
  ADD PRIMARY KEY (`test_case_id`),
  ADD KEY `programming_id` (`programming_id`);

--
-- Indexes for table `register_studentsqe`
--
ALTER TABLE `register_studentsqe`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `reference_id` (`reference_id`),
  ADD KEY `stud_id` (`stud_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`stud_id`),
  ADD UNIQUE KEY `unq_email` (`email`);

--
-- Indexes for table `student_answers`
--
ALTER TABLE `student_answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD KEY `idx_student_exam` (`student_id`,`exam_id`),
  ADD KEY `idx_question` (`question_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `test_cases`
--
ALTER TABLE `test_cases`
  ADD PRIMARY KEY (`test_case_id`),
  ADD KEY `programming_id` (`programming_id`);

--
-- Indexes for table `university_grading_systems`
--
ALTER TABLE `university_grading_systems`
  ADD PRIMARY KEY (`grading_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_login_attempts`
--
ALTER TABLE `admin_login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `admin_login_logs`
--
ALTER TABLE `admin_login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `answers`
--
ALTER TABLE `answers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=216;

--
-- AUTO_INCREMENT for table `coded_courses`
--
ALTER TABLE `coded_courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `exam_assignments`
--
ALTER TABLE `exam_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `exam_results`
--
ALTER TABLE `exam_results`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `matched_courses`
--
ALTER TABLE `matched_courses`
  MODIFY `matched_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=148;

--
-- AUTO_INCREMENT for table `programming_questions`
--
ALTER TABLE `programming_questions`
  MODIFY `programming_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `question_bank`
--
ALTER TABLE `question_bank`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `question_bank_answers`
--
ALTER TABLE `question_bank_answers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `question_bank_programming`
--
ALTER TABLE `question_bank_programming`
  MODIFY `programming_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `question_bank_test_cases`
--
ALTER TABLE `question_bank_test_cases`
  MODIFY `test_case_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `register_studentsqe`
--
ALTER TABLE `register_studentsqe`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `stud_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `student_answers`
--
ALTER TABLE `student_answers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=151;

--
-- AUTO_INCREMENT for table `test_cases`
--
ALTER TABLE `test_cases`
  MODIFY `test_case_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `university_grading_systems`
--
ALTER TABLE `university_grading_systems`
  MODIFY `grading_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_login_logs`
--
ALTER TABLE `admin_login_logs`
  ADD CONSTRAINT `admin_login_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`);

--
-- Constraints for table `answers`
--
ALTER TABLE `answers`
  ADD CONSTRAINT `answers_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_assignments`
--
ALTER TABLE `exam_assignments`
  ADD CONSTRAINT `exam_assignments_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`),
  ADD CONSTRAINT `exam_assignments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `register_studentsqe` (`student_id`);

--
-- Constraints for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD CONSTRAINT `exam_results_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`),
  ADD CONSTRAINT `exam_results_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`stud_id`);

--
-- Constraints for table `matched_courses`
--
ALTER TABLE `matched_courses`
  ADD CONSTRAINT `matched_courses_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `register_studentsqe` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `programming_questions`
--
ALTER TABLE `programming_questions`
  ADD CONSTRAINT `programming_questions_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`) ON DELETE CASCADE;

--
-- Constraints for table `question_bank_answers`
--
ALTER TABLE `question_bank_answers`
  ADD CONSTRAINT `qb_answers_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `question_bank` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `question_bank_programming`
--
ALTER TABLE `question_bank_programming`
  ADD CONSTRAINT `qb_programming_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `question_bank` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `question_bank_test_cases`
--
ALTER TABLE `question_bank_test_cases`
  ADD CONSTRAINT `qb_test_cases_ibfk_1` FOREIGN KEY (`programming_id`) REFERENCES `question_bank_programming` (`programming_id`) ON DELETE CASCADE;

--
-- Constraints for table `register_studentsqe`
--
ALTER TABLE `register_studentsqe`
  ADD CONSTRAINT `register_studentsqe_ibfk_1` FOREIGN KEY (`stud_id`) REFERENCES `students` (`stud_id`);

--
-- Constraints for table `student_answers`
--
ALTER TABLE `student_answers`
  ADD CONSTRAINT `student_answers_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `register_studentsqe` (`student_id`),
  ADD CONSTRAINT `student_answers_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`),
  ADD CONSTRAINT `student_answers_ibfk_3` FOREIGN KEY (`question_id`) REFERENCES `questions` (`question_id`);

--
-- Constraints for table `test_cases`
--
ALTER TABLE `test_cases`
  ADD CONSTRAINT `test_cases_ibfk_1` FOREIGN KEY (`programming_id`) REFERENCES `programming_questions` (`programming_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
