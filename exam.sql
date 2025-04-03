-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Mar 18, 2025 at 03:10 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

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

DROP TABLE IF EXISTS `admin`;
CREATE TABLE IF NOT EXISTS `admin` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `email`, `password`, `created_at`) VALUES
(1, 'pupccisfaculty@gmail.com', '$2y$10$jR4Qh.5YujukJwhIvGF24uOQbtdWL1fnjkoz06O3Fh32W/k9Ypn0C', '2025-03-16 13:11:55');

-- --------------------------------------------------------

--
-- Table structure for table `admin_login_attempts`
--

DROP TABLE IF EXISTS `admin_login_attempts`;
CREATE TABLE IF NOT EXISTS `admin_login_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `login_attempts` int DEFAULT '0',
  `last_attempt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin_login_attempts`
--

INSERT INTO `admin_login_attempts` (`id`, `email`, `login_attempts`, `last_attempt`) VALUES
(1, 'pupccisfaculty@gmail.com', 0, '2025-03-17 16:09:33');

-- --------------------------------------------------------

--
-- Table structure for table `admin_login_logs`
--

DROP TABLE IF EXISTS `admin_login_logs`;
CREATE TABLE IF NOT EXISTS `admin_login_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin_login_logs`
--

INSERT INTO `admin_login_logs` (`id`, `admin_id`, `email`, `ip_address`, `status`, `created_at`) VALUES
(1, 1, 'pupccisfaculty@gmail.com', '::1', 'success', '2025-03-16 13:13:05'),
(2, 1, 'pupccisfaculty@gmail.com', '::1', 'success', '2025-03-17 16:10:02');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `created_at`, `updated_at`, `status`) VALUES
(1, 'CCIS Qualifying Exam Schedule', 'Please be informed that the CCIS qualifying exam registration will be opened on March 17 - 20. Make sure to submit the necessary documents and information for the registering in qualifying exam.', '2025-03-16 10:37:24', '2025-03-16 10:37:24', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `answers`
--

DROP TABLE IF EXISTS `answers`;
CREATE TABLE IF NOT EXISTS `answers` (
  `answer_id` int NOT NULL AUTO_INCREMENT,
  `question_id` int NOT NULL,
  `answer_text` text NOT NULL,
  `is_correct` tinyint(1) DEFAULT '0',
  `position` int NOT NULL,
  PRIMARY KEY (`answer_id`),
  KEY `question_id` (`question_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `answers`
--

INSERT INTO `answers` (`answer_id`, `question_id`, `answer_text`, `is_correct`, `position`) VALUES
(1, 1, 'dfas', 1, 0),
(2, 1, 'asdfasdf', 0, 1),
(3, 2, 'True', 1, 0),
(4, 2, 'False', 0, 1),
(5, 3, 'True', 1, 0),
(6, 3, 'False', 0, 1),
(7, 4, 'fadsfsd', 1, 0),
(8, 4, 'fadssad', 0, 1),
(9, 4, 'fsdaas', 0, 2),
(10, 4, 'asfdasdfasd', 0, 3),
(11, 9, 'True', 1, 0),
(12, 9, 'False', 0, 1),
(13, 14, 'True', 1, 0),
(14, 14, 'False', 0, 1),
(15, 15, 'fadsfsd', 1, 0),
(16, 15, 'fadssad', 0, 1),
(17, 15, 'fsdaas', 0, 2),
(18, 15, 'asfdasdfasd', 0, 3),
(19, 17, 'True', 1, 0),
(20, 17, 'False', 0, 1),
(27, 21, 'True', 1, 0),
(28, 21, 'False', 0, 1),
(29, 23, '4', 1, 0),
(30, 23, '3', 0, 1),
(31, 23, '5', 0, 2),
(32, 23, '6', 0, 3),
(33, 24, 'xvzxv', 1, 0),
(34, 24, 'xvzxvxz', 0, 1),
(35, 24, 'vzxvzxvz', 0, 2);

-- --------------------------------------------------------

--
-- Table structure for table `coded_courses`
--

DROP TABLE IF EXISTS `coded_courses`;
CREATE TABLE IF NOT EXISTS `coded_courses` (
  `course_id` int NOT NULL AUTO_INCREMENT,
  `subject_code` varchar(100) NOT NULL,
  `subject_description` varchar(255) NOT NULL,
  `units` decimal(5,2) NOT NULL,
  PRIMARY KEY (`course_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `coded_courses`
--

INSERT INTO `coded_courses` (`course_id`, `subject_code`, `subject_description`, `units`) VALUES
(1, 'COMP 20033', 'Computer Programming 2', 3.00),
(2, 'COMP 20013', 'Introduction to Computing', 3.00),
(3, 'COMP 20023', 'Computer Programming 1', 3.00),
(4, 'CWTS 10013', 'Civic Welfare Training Service 1', 3.00),
(5, 'GEED 10053', 'Mathematics in the Modern World', 3.00),
(6, 'GEED 10063', 'Purposive Communication', 3.00),
(7, 'GEED 10103', 'Filipinolohiya at Pambansang Kaunlaran', 3.00),
(8, 'PHED 10012', 'Physical Fitness and Self-Testing Activities', 2.00),
(9, 'COMP 20043', 'Discrete Structures 1', 3.00),
(10, 'CWTS 10023', 'Civic Welfare Training Service 2', 3.00),
(11, 'GEED 10033', 'Readings in Philippine History', 3.00),
(12, 'GEED 10113', 'Pagsasalin sa Kontekstong Filipino', 3.00),
(13, 'GEED 20023', 'Politics, Governance and Citizenship', 3.00),
(14, 'PHED 10022', 'Rhythmic Activities', 2.00),
(15, 'COMP 20013', 'Introduction to Computing', 3.00),
(16, 'COMP 20023', 'Computer Programming 1', 3.00),
(17, 'PHED 20023', 'Physical Education 1', 2.00),
(18, 'NSTP 20023', 'National Service Training Program 1', 3.00),
(19, 'GEED 10083', 'Science, Technology and Society', 3.00),
(20, 'GEED 10113', 'Intelektwaslisasyon ng Filipino sa ibat ibang Larangan', 3.00),
(21, 'MATH 20333', 'Differential Calculus', 3.00),
(22, 'GEED 10023', 'Understanding the Self', 3.00),
(23, 'PHED 10022', 'Physical Education', 2.00),
(24, 'NSTP 10023', 'National Service Training Program 2', 3.00);

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

DROP TABLE IF EXISTS `exams`;
CREATE TABLE IF NOT EXISTS `exams` (
  `exam_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `exam_type` enum('tech','non-tech') NOT NULL,
  `is_scheduled` tinyint(1) DEFAULT '0',
  `scheduled_date` date DEFAULT NULL,
  `scheduled_time` time DEFAULT NULL,
  `duration` int DEFAULT '60' COMMENT 'Duration in minutes',
  `cover_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `randomize_questions` tinyint(1) DEFAULT '0',
  `randomize_choices` tinyint(1) DEFAULT '0',
  `passing_score_type` enum('percentage','points') DEFAULT NULL,
  `passing_score` int DEFAULT NULL,
  PRIMARY KEY (`exam_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`exam_id`, `title`, `description`, `exam_type`, `is_scheduled`, `scheduled_date`, `scheduled_time`, `duration`, `cover_image`, `created_at`, `updated_at`, `randomize_questions`, `randomize_choices`, `passing_score_type`, `passing_score`) VALUES
(2, 'dfasdf', 'sdfsdafaf', 'tech', 0, NULL, NULL, 60, '/uploads/exam_covers/67d693bdd19f2_DALL·E 2025-03-13 13.13.36 - Pixel art stone tiles for a 2D game, designed to be seamless and loopable. The stones vary in size and shape, with subtle shading and texture to add d.webp', '2025-03-13 09:23:53', '2025-03-16 09:02:53', 1, 1, '', 70),
(3, 'fdssdfsdbc', 'dsfasfdds', 'non-tech', 0, NULL, NULL, 60, NULL, '2025-03-13 09:45:36', '2025-03-16 07:37:25', 1, 1, 'percentage', 70),
(4, 'Practice Question ', '', 'non-tech', 0, NULL, NULL, 60, '/uploads/exam_covers/67d89085a872e_449467909_1205163000647861_8408110911620157242_n.png', '2025-03-15 09:54:04', '2025-03-17 21:14:20', 1, 1, 'percentage', 2),
(5, 'aafd', 'gsdfgds', 'tech', 1, '2025-03-24', '08:28:00', 60, 'uploads/covers/1742248711_449292499_481139171214456_1108316609890498134_n.png', '2025-03-17 21:16:53', '2025-03-17 22:36:52', 1, 1, 'percentage', 64);

-- --------------------------------------------------------

--
-- Table structure for table `exam_assignments`
--

DROP TABLE IF EXISTS `exam_assignments`;
CREATE TABLE IF NOT EXISTS `exam_assignments` (
  `assignment_id` int NOT NULL AUTO_INCREMENT,
  `exam_id` int NOT NULL,
  `student_id` int NOT NULL,
  `assigned_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `completion_status` enum('pending','completed','expired') DEFAULT 'pending',
  `score` int DEFAULT NULL,
  PRIMARY KEY (`assignment_id`),
  KEY `exam_id` (`exam_id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_results`
--

DROP TABLE IF EXISTS `exam_results`;
CREATE TABLE IF NOT EXISTS `exam_results` (
  `result_id` int NOT NULL AUTO_INCREMENT,
  `exam_id` int NOT NULL,
  `student_id` int NOT NULL,
  `score` int DEFAULT NULL,
  `total_questions` int DEFAULT NULL,
  `completion_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`result_id`),
  KEY `exam_id` (`exam_id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `matched_courses`
--

DROP TABLE IF EXISTS `matched_courses`;
CREATE TABLE IF NOT EXISTS `matched_courses` (
  `matched_id` int NOT NULL AUTO_INCREMENT,
  `subject_code` varchar(100) NOT NULL,
  `original_code` varchar(20) DEFAULT NULL,
  `subject_description` varchar(255) NOT NULL,
  `units` decimal(5,2) NOT NULL,
  `grade` decimal(4,2) DEFAULT NULL,
  `student_id` int NOT NULL,
  `matched_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`matched_id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=140 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `matched_courses`
--

INSERT INTO `matched_courses` (`matched_id`, `subject_code`, `original_code`, `subject_description`, `units`, `grade`, `student_id`, `matched_at`) VALUES
(135, 'GEED 10023', 'FCL 1101', 'Understanding the Self', 3.00, 1.25, 79, '2025-03-11 14:02:47'),
(136, 'GEED 10063', 'FIL 1000', 'Purposive Communication', 3.00, 1.00, 79, '2025-03-11 14:02:47'),
(137, 'GEED 10053', 'GEC 1000', 'Mathematics in the Modern World', 3.00, 1.00, 79, '2025-03-11 14:02:47'),
(138, 'NSTP 20023', 'GEC 4000', 'National Service Training Program 1', 3.00, 1.25, 79, '2025-03-11 14:02:47'),
(139, 'PHED 10022', 'GEE', 'Rhythmic Activities', 2.00, 1.00, 79, '2025-03-11 14:02:47');

-- --------------------------------------------------------

--
-- Table structure for table `programming_questions`
--

DROP TABLE IF EXISTS `programming_questions`;
CREATE TABLE IF NOT EXISTS `programming_questions` (
  `programming_id` int NOT NULL AUTO_INCREMENT,
  `question_id` int NOT NULL,
  `starter_code` text,
  `language` varchar(50) DEFAULT 'python',
  PRIMARY KEY (`programming_id`),
  KEY `question_id` (`question_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `programming_questions`
--

INSERT INTO `programming_questions` (`programming_id`, `question_id`, `starter_code`, `language`) VALUES
(4, 13, 'sdfsadfdafadf', 'python'),
(5, 16, 'sdfsadfdafadf', 'python'),
(6, 22, 'def add(a, b):\n    pass', 'python');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

DROP TABLE IF EXISTS `questions`;
CREATE TABLE IF NOT EXISTS `questions` (
  `question_id` int NOT NULL AUTO_INCREMENT,
  `exam_id` int NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple-choice','true-false','programming') NOT NULL,
  `points` int DEFAULT '1',
  `position` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`question_id`),
  KEY `exam_id` (`exam_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`question_id`, `exam_id`, `question_text`, `question_type`, `points`, `position`, `created_at`, `updated_at`) VALUES
(1, 2, 'fsefsa', 'multiple-choice', 1, 0, '2025-03-13 17:23:53', '2025-03-13 17:23:53'),
(2, 3, '<p>fdsfsdfsd</p>', 'true-false', 1, 0, '2025-03-13 17:45:36', '2025-03-13 17:45:36'),
(3, 2, '<p>gdfgsg</p>', 'true-false', 1, 1, '2025-03-13 17:45:59', '2025-03-13 17:45:59'),
(4, 4, 'fdasfsad', 'multiple-choice', 1, 0, '2025-03-15 18:11:58', '2025-03-15 18:11:58'),
(9, 4, '<p>fdsafasf</p>', 'true-false', 1, 0, '2025-03-15 18:54:17', '2025-03-15 18:54:17'),
(13, 4, '<p>fdsafadsf</p>', 'programming', 1, 0, '2025-03-15 19:30:00', '2025-03-15 19:30:00'),
(14, 4, '<p>fdsafasf</p>', 'true-false', 1, 1, '2025-03-15 21:15:45', '2025-03-15 21:15:45'),
(15, 4, 'fdasfsad', 'multiple-choice', 1, 2, '2025-03-15 21:15:53', '2025-03-15 21:15:53'),
(16, 4, '<p>fdsafadsf</p>', 'programming', 1, 3, '2025-03-15 21:16:01', '2025-03-15 21:16:01'),
(17, 4, 'The sky is blue.', 'true-false', 1, 4, '2025-03-16 01:04:25', '2025-03-16 01:04:25'),
(21, 4, 'The sky is blue.', 'true-false', 2, 5, '2025-03-16 09:32:29', '2025-03-16 09:32:29'),
(22, 4, 'Write a function that returns the sum of two numbers.', 'programming', 2, 6, '2025-03-16 09:32:29', '2025-03-16 09:32:29'),
(23, 4, 'What is 2+2?', 'multiple-choice', 2, 7, '2025-03-16 09:32:29', '2025-03-16 09:32:29'),
(24, 5, 'vxvxzv', 'multiple-choice', 1, 0, '2025-03-18 05:17:01', '2025-03-18 05:17:01');

-- --------------------------------------------------------

--
-- Table structure for table `question_bank`
--

DROP TABLE IF EXISTS `question_bank`;
CREATE TABLE IF NOT EXISTS `question_bank` (
  `question_id` int NOT NULL AUTO_INCREMENT,
  `question_text` text NOT NULL,
  `question_type` enum('multiple-choice','true-false','programming') NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `points` int DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`question_id`),
  KEY `idx_question_type` (`question_type`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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

DROP TABLE IF EXISTS `question_bank_answers`;
CREATE TABLE IF NOT EXISTS `question_bank_answers` (
  `answer_id` int NOT NULL AUTO_INCREMENT,
  `question_id` int NOT NULL,
  `answer_text` text NOT NULL,
  `is_correct` tinyint(1) DEFAULT '0',
  `position` int NOT NULL,
  PRIMARY KEY (`answer_id`),
  KEY `question_id` (`question_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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

DROP TABLE IF EXISTS `question_bank_programming`;
CREATE TABLE IF NOT EXISTS `question_bank_programming` (
  `programming_id` int NOT NULL AUTO_INCREMENT,
  `question_id` int NOT NULL,
  `starter_code` text,
  `language` varchar(50) NOT NULL,
  PRIMARY KEY (`programming_id`),
  KEY `question_id` (`question_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `question_bank_programming`
--

INSERT INTO `question_bank_programming` (`programming_id`, `question_id`, `starter_code`, `language`) VALUES
(6, 13, 'def add(a, b):\n    pass', 'python');

-- --------------------------------------------------------

--
-- Table structure for table `question_bank_test_cases`
--

DROP TABLE IF EXISTS `question_bank_test_cases`;
CREATE TABLE IF NOT EXISTS `question_bank_test_cases` (
  `test_case_id` int NOT NULL AUTO_INCREMENT,
  `programming_id` int NOT NULL,
  `input` text,
  `expected_output` text NOT NULL,
  `is_hidden` tinyint(1) DEFAULT '0',
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`test_case_id`),
  KEY `programming_id` (`programming_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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

DROP TABLE IF EXISTS `register_studentsqe`;
CREATE TABLE IF NOT EXISTS `register_studentsqe` (
  `student_id` int NOT NULL AUTO_INCREMENT,
  `last_name` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
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
  `registration_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `reference_id` (`reference_id`)
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `register_studentsqe`
--

INSERT INTO `register_studentsqe` (`student_id`, `last_name`, `first_name`, `middle_name`, `gender`, `dob`, `email`, `contact_number`, `street`, `student_type`, `previous_school`, `year_level`, `previous_program`, `desired_program`, `tor`, `school_id`, `reference_id`, `is_tech`, `registration_date`, `status`) VALUES
(79, 'Dugo', 'Janlloyd', 'Yamoyam', 'Male', '2025-03-20', 'jdugo23@gmail.com', '09667311122', 'C Raymundo Ave', 'transferee', 'University of Perpetual', '1', 'Bachelor of Science in Physics (BSP)', 'BSIT', 'uploads/tor/Screenshot 2024-11-08 194747.png', 'uploads/school_id/449292499_481139171214456_1108316609890498134_n.png', 'CCIS-2025-64341', 0, '2025-03-11 14:02:42', 'accepted');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
CREATE TABLE IF NOT EXISTS `students` (
  `stud_id` int NOT NULL AUTO_INCREMENT,
  `firstname` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `lastname` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`stud_id`),
  UNIQUE KEY `unq_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`stud_id`, `firstname`, `lastname`, `email`, `password`, `created_at`) VALUES
(1, 'Janlloyd', 'Dugo', 'jdugo23@gmail.com', '$2y$10$hk6iwl9PFJVdozSiQNOBLuWsgkNQnjDuvgx0dMVag38BFjN0fI8Fu', '2025-03-08 23:48:15'),
(2, 'Janlloyd', 'Dugong', 'janlloyddugo101@gmail.com', '$2y$10$74HkKhTuM/tAo9cUnv6CG.t4QwARhnkYuJevV.Dm580WHU.N2pxoS', '2025-03-09 07:12:58');

-- --------------------------------------------------------

--
-- Table structure for table `test_cases`
--

DROP TABLE IF EXISTS `test_cases`;
CREATE TABLE IF NOT EXISTS `test_cases` (
  `test_case_id` int NOT NULL AUTO_INCREMENT,
  `programming_id` int NOT NULL,
  `input` text,
  `expected_output` text,
  `is_hidden` tinyint(1) DEFAULT '0',
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`test_case_id`),
  KEY `programming_id` (`programming_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `test_cases`
--

INSERT INTO `test_cases` (`test_case_id`, `programming_id`, `input`, `expected_output`, `is_hidden`, `description`) VALUES
(7, 4, '1 2', '3', 0, NULL),
(8, 5, '1 2', '3', 0, NULL),
(9, 6, ' 5, 3', '8', 0, ''),
(10, 6, '10, 20', '30', 1, 'Test with larger numbers ');

-- --------------------------------------------------------

--
-- Table structure for table `university_grading_systems`
--

DROP TABLE IF EXISTS `university_grading_systems`;
CREATE TABLE IF NOT EXISTS `university_grading_systems` (
  `grading_id` int NOT NULL AUTO_INCREMENT,
  `university_name` varchar(255) NOT NULL,
  `min_percentage` decimal(5,2) NOT NULL,
  `max_percentage` decimal(5,2) NOT NULL,
  `grade_value` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`grading_id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
-- Constraints for table `test_cases`
--
ALTER TABLE `test_cases`
  ADD CONSTRAINT `test_cases_ibfk_1` FOREIGN KEY (`programming_id`) REFERENCES `programming_questions` (`programming_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
