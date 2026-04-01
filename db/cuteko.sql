-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 05, 2025 at 05:42 AM
-- Server version: 9.1.0
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cuteko`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `posted_by` int NOT NULL,
  `target_audience` enum('All','Students','Teachers','Admins') DEFAULT 'All',
  `priority` enum('Low','Normal','High','Urgent') DEFAULT 'Normal',
  `is_pinned` tinyint(1) DEFAULT '0',
  `attachment` varchar(255) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('Active','Archived') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `posted_by` (`posted_by`),
  KEY `idx_target_status` (`target_audience`,`status`),
  KEY `idx_created` (`created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_alerts`
--

DROP TABLE IF EXISTS `attendance_alerts`;
CREATE TABLE IF NOT EXISTS `attendance_alerts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `alert_type` enum('Frequent Absence','Late Pattern','Perfect Attendance','Improvement') NOT NULL,
  `absence_count` int DEFAULT '0',
  `alert_message` text NOT NULL,
  `severity` enum('Low','Medium','High','Critical') DEFAULT 'Medium',
  `is_read` tinyint(1) DEFAULT '0',
  `notified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_student_alert` (`student_id`,`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_sessions`
--

DROP TABLE IF EXISTS `attendance_sessions`;
CREATE TABLE IF NOT EXISTS `attendance_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int NOT NULL,
  `subject` varchar(100) NOT NULL,
  `session_name` varchar(255) NOT NULL,
  `session_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `attendance_method` enum('Biometric','PIN','QR Code','Manual') DEFAULT 'Manual',
  `qr_code` text,
  `session_code` varchar(10) DEFAULT NULL,
  `status` enum('Scheduled','Active','Closed','Cancelled') DEFAULT 'Scheduled',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_teacher_date` (`teacher_id`,`session_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `biometric_logs`
--

DROP TABLE IF EXISTS `biometric_logs`;
CREATE TABLE IF NOT EXISTS `biometric_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `user_type` enum('student','staff','tutor') NOT NULL,
  `action_type` enum('Check-In','Check-Out','Enrollment','Verification Failed') NOT NULL,
  `fingerprint_match_score` decimal(5,2) DEFAULT NULL,
  `success` tinyint(1) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `device_info` text,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_timestamp` (`user_id`,`timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `biometric_logs`
--

INSERT INTO `biometric_logs` (`id`, `user_id`, `user_type`, `action_type`, `fingerprint_match_score`, `success`, `ip_address`, `device_info`, `timestamp`) VALUES
(1, 4, 'student', 'Enrollment', NULL, 1, NULL, NULL, '2025-11-03 11:50:45');

-- --------------------------------------------------------

--
-- Table structure for table `encryption_keys`
--

DROP TABLE IF EXISTS `encryption_keys`;
CREATE TABLE IF NOT EXISTS `encryption_keys` (
  `id` int NOT NULL AUTO_INCREMENT,
  `key_name` varchar(100) NOT NULL,
  `encrypted_key` text NOT NULL,
  `algorithm` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_name` (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fingerprint_templates`
--

DROP TABLE IF EXISTS `fingerprint_templates`;
CREATE TABLE IF NOT EXISTS `fingerprint_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `user_type` enum('student','staff','tutor') NOT NULL,
  `fingerprint_template` longtext NOT NULL,
  `fingerprint_image` longblob,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_fingerprint` (`user_id`,`user_type`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `fingerprint_templates`
--

INSERT INTO `fingerprint_templates` (`id`, `user_id`, `user_type`, `fingerprint_template`, `fingerprint_image`, `created_at`, `updated_at`) VALUES
(1, 4, 'student', 'FingerprintTemplateData:SimulatedDataForStudentID6913', NULL, '2025-11-03 03:50:45', '2025-11-03 03:50:45');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `notification_type` enum('Attendance Alert','Tutor Match','Report Generated','System') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `priority` enum('Low','Normal','High','Urgent') DEFAULT 'Normal',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_read` (`user_id`,`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
CREATE TABLE IF NOT EXISTS `reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `report_type` enum('attendance','tutor_matching','staff_attendance','student_performance') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `report_data` longtext NOT NULL,
  `format` enum('pdf','excel','csv') NOT NULL,
  `generated_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `generated_by` (`generated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `report_type`, `title`, `description`, `report_data`, `format`, `generated_by`, `created_at`) VALUES
(1, 'attendance', 'Student Attendance Report - Oct 30, 2025', 'Report generated for period: 2025-10-01 to 2025-10-30', 'Report data for attendance from 2025-10-01 to 2025-10-30', 'excel', 1, '2025-10-30 15:19:32'),
(2, 'attendance', 'Student Attendance Report - Oct 31, 2025', 'Report generated for period: 2025-10-01 to 2025-10-31', 'Report data for attendance from 2025-10-01 to 2025-10-31', 'excel', 1, '2025-10-31 01:55:16'),
(3, 'attendance', 'Student Attendance Report - Oct 31, 2025', 'Report generated for period: 2025-10-01 to 2025-10-31', 'Report data for attendance from 2025-10-01 to 2025-10-31', 'pdf', 1, '2025-10-31 01:56:45'),
(4, 'attendance', 'Student Attendance Report - Oct 31, 2025', 'Report generated for period: 2025-10-01 to 2025-10-31', 'Report data for attendance from 2025-10-01 to 2025-10-31', 'excel', 1, '2025-10-31 01:57:01');

-- --------------------------------------------------------

--
-- Table structure for table `staff_attendance`
--

DROP TABLE IF EXISTS `staff_attendance`;
CREATE TABLE IF NOT EXISTS `staff_attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `attendance_date` date NOT NULL,
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `status` enum('Present','Absent','Late','On Leave') NOT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_staff_date` (`staff_id`,`attendance_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
CREATE TABLE IF NOT EXISTS `students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` text,
  `year_level_id` int DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `student_id_image` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `enrollment_date` date NOT NULL,
  `status` enum('Active','Inactive','Graduated') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_year_level` (`year_level_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_id`, `first_name`, `last_name`, `email`, `phone`, `date_of_birth`, `address`, `year_level_id`, `section`, `student_id_image`, `profile_picture`, `enrollment_date`, `status`, `created_at`, `updated_at`) VALUES
(1, '2025-01', 'Alice', 'Brown', 'alice.brown@example.com', '555-0201', NULL, NULL, NULL, NULL, NULL, NULL, '2023-09-01', 'Active', '2025-10-29 05:55:03', '2025-10-29 05:55:03'),
(2, '2025-02', 'Bob', 'Davis', 'bob.davis@example.com', '555-0202', NULL, NULL, NULL, NULL, NULL, NULL, '2023-09-01', 'Active', '2025-10-29 05:55:03', '2025-10-29 05:55:03'),
(3, '2025-03', 'Charlie', 'Wilson', 'charlie.wilson@example.com', '555-0203', NULL, NULL, NULL, NULL, NULL, NULL, '2023-09-01', 'Active', '2025-10-29 05:55:03', '2025-10-29 05:55:03'),
(4, '2025-04', 'Kristian', 'Factor', 'krist@gmail.com', '0911122987', '2007-12-02', 'Magsaysay', 4, 'A', NULL, NULL, '2025-10-30', 'Active', '2025-10-30 11:53:09', '2025-10-30 11:53:09');

--
-- Triggers `students`
--
DROP TRIGGER IF EXISTS `before_insert_student`;
DELIMITER $$
CREATE TRIGGER `before_insert_student` BEFORE INSERT ON `students` FOR EACH ROW BEGIN
    DECLARE next_num INT;
    DECLARE year_prefix VARCHAR(4);
    
    -- Only generate if student_id is NULL or empty
    IF NEW.student_id IS NULL OR NEW.student_id = '' THEN
        SET year_prefix = YEAR(CURDATE());
        
        -- Get the next number for this year
        SELECT COALESCE(MAX(CAST(SUBSTRING(student_id, 6) AS UNSIGNED)), 0) + 1
        INTO next_num
        FROM students
        WHERE student_id LIKE CONCAT(year_prefix, '-%');
        
        -- Generate the new student_id (format: YYYY-NN)
        SET NEW.student_id = CONCAT(year_prefix, '-', LPAD(next_num, 2, '0'));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `student_attendance`
--

DROP TABLE IF EXISTS `student_attendance`;
CREATE TABLE IF NOT EXISTS `student_attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `attendance_date` date NOT NULL,
  `check_in_time` datetime DEFAULT NULL,
  `check_out_time` datetime DEFAULT NULL,
  `status` enum('Present','Absent','Late','Excused') NOT NULL,
  `is_biometric_verified` tinyint(1) DEFAULT '0',
  `fingerprint_match_score` decimal(5,2) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_date` (`student_id`,`attendance_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tutors`
--

DROP TABLE IF EXISTS `tutors`;
CREATE TABLE IF NOT EXISTS `tutors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tutor_id` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `experience_years` int DEFAULT '0',
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `address` text,
  `profile_picture` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive','On Leave') DEFAULT 'Active',
  `hire_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `tutor_id` (`tutor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tutors`
--

INSERT INTO `tutors` (`id`, `tutor_id`, `first_name`, `last_name`, `email`, `phone`, `specialization`, `qualification`, `experience_years`, `hourly_rate`, `address`, `profile_picture`, `status`, `hire_date`, `created_at`, `updated_at`) VALUES
(1, '2025-01', 'Sarah', 'Johnson', 'sarah.johnson@example.com', '555-0101', 'Mathematics', 'PhD in Mathematics', 8, 45.00, NULL, NULL, 'Active', '2020-01-15', '2025-10-29 05:55:03', '2025-10-29 05:55:03'),
(2, '2025-02', 'Michael', 'Chen', 'michael.chen@example.com', '555-0102', 'Physics', 'Masters in Physics', 5, 40.00, NULL, NULL, 'Active', '2021-03-20', '2025-10-29 05:55:03', '2025-10-29 05:55:03'),
(3, '2025-03', 'Emily', 'Williams', 'emily.williams@example.com', '555-0103', 'English Literature', 'PhD in English', 10, 50.00, NULL, NULL, 'Active', '2019-09-01', '2025-10-29 05:55:03', '2025-10-29 05:55:03'),
(4, '2025-04', 'James', 'Bond', 'james@gmail.com', '09357335755', 'Ethics', 'Bachelor', 2, 5.00, '', NULL, 'Active', '2025-10-27', '2025-10-29 06:09:18', '2025-10-29 06:09:18'),
(19, '2025-05', 'test', 'test', 'test@mail.com', '123123', 'math', 'super master', 50, 0.00, 'test', NULL, 'Active', '2025-11-03', '2025-11-03 05:40:21', '2025-11-03 05:40:21'),
(20, '2025-06', 'test1', 'test1', 'test!@mail.com', '12312312', 'math', 'super master', 1, 0.00, '0', NULL, 'Active', '2025-11-03', '2025-11-03 05:46:47', '2025-11-03 05:46:47'),
(21, '2025-07', 'Rafael', 'Factor', 'rafaelfactor3@gmail.com', '09394115446', 'English', 'Bachelor', 1, 0.00, '66', NULL, 'Active', '2025-11-03', '2025-11-03 05:48:32', '2025-11-03 05:48:32'),
(22, '2025-08', 'Krist', 'Factor', 'krist@gmail.com', '911122987', 'Chemistry', 'Master', 2, 0.00, '0', NULL, 'Active', '2025-11-04', '2025-11-04 01:27:14', '2025-11-04 01:27:14');

--
-- Triggers `tutors`
--
DROP TRIGGER IF EXISTS `before_insert_tutor`;
DELIMITER $$
CREATE TRIGGER `before_insert_tutor` BEFORE INSERT ON `tutors` FOR EACH ROW BEGIN
    DECLARE next_num INT;
    DECLARE year_prefix VARCHAR(4);
    
    -- Only generate if tutor_id is NULL or empty
    IF NEW.tutor_id IS NULL OR NEW.tutor_id = '' THEN
        SET year_prefix = YEAR(CURDATE());
        
        -- Get the next number for this year
        SELECT COALESCE(MAX(CAST(SUBSTRING(tutor_id, 6) AS UNSIGNED)), 0) + 1
        INTO next_num
        FROM tutors
        WHERE tutor_id LIKE CONCAT(year_prefix, '-%');
        
        -- Generate the new tutor_id (format: YYYY-NN)
        SET NEW.tutor_id = CONCAT(year_prefix, '-', LPAD(next_num, 2, '0'));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `tutor_attendance`
--

DROP TABLE IF EXISTS `tutor_attendance`;
CREATE TABLE IF NOT EXISTS `tutor_attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tutor_id` int NOT NULL,
  `attendance_date` date NOT NULL,
  `check_in_time` datetime DEFAULT NULL,
  `check_out_time` datetime DEFAULT NULL,
  `status` enum('Present','Absent','Late','Excused') NOT NULL,
  `is_biometric_verified` tinyint(1) DEFAULT '0',
  `fingerprint_match_score` decimal(5,2) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tutor_date` (`tutor_id`,`attendance_date`),
  KEY `idx_tutor_date` (`tutor_id`,`attendance_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tutor_matching_suggestions`
--

DROP TABLE IF EXISTS `tutor_matching_suggestions`;
CREATE TABLE IF NOT EXISTS `tutor_matching_suggestions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `tutor_id` int NOT NULL,
  `subject` varchar(100) NOT NULL,
  `match_score` decimal(5,2) NOT NULL,
  `reason` text,
  `status` enum('Pending','Accepted','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `tutor_id` (`tutor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tutor_student_matching`
--

DROP TABLE IF EXISTS `tutor_student_matching`;
CREATE TABLE IF NOT EXISTS `tutor_student_matching` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tutor_id` int NOT NULL,
  `student_id` int NOT NULL,
  `subject` varchar(100) NOT NULL,
  `status` enum('Active','Inactive','Completed') DEFAULT 'Active',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tutor_student` (`tutor_id`,`student_id`,`subject`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tutor_student_matching`
--

INSERT INTO `tutor_student_matching` (`id`, `tutor_id`, `student_id`, `subject`, `status`, `start_date`, `end_date`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Calculus', 'Active', '2024-01-10', NULL, '2025-10-29 05:55:03', '2025-10-29 05:55:03'),
(2, 2, 2, 'Quantum Physics', 'Active', '2024-01-15', NULL, '2025-10-29 05:55:03', '2025-10-29 05:55:03'),
(3, 3, 3, 'Creative Writing', 'Active', '2024-01-20', NULL, '2025-10-29 05:55:03', '2025-10-29 05:55:03'),
(4, 4, 4, 'Ethics', 'Active', '2025-10-30', NULL, '2025-10-30 11:53:09', '2025-10-30 11:53:09');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role` enum('user','admin') NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `teacher_id` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `attendance_pin` varchar(6) DEFAULT NULL,
  `qr_code` text,
  `status` enum('Active','Inactive','Suspended') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `unique_student_id` (`student_id`),
  UNIQUE KEY `unique_teacher_id` (`teacher_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `username`, `password`, `name`, `email`, `phone`, `student_id`, `teacher_id`, `department`, `year_level`, `section`, `profile_picture`, `attendance_pin`, `qr_code`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'elias', '$2y$10$SVjikDTx8IdCf9Dsy5cgcOCBs1xY/6PkBMjNmxmEjPqc342fyuYcm', 'Elias Abdurrahman', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', '2025-10-29 05:55:03', '2025-10-29 07:09:13'),
(2, 'user', 'john', '$2y$10$USMRtfv7YLNamxqwoD3lEuLeMdmxEOiAL74Mrge5C30IJZGQN/yaa', 'John Doe', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', '2025-10-29 05:55:03', '2025-10-30 11:37:48'),
(16, 'user', 'rafael', '$2y$10$7Log4dnHP1OEWmQ31brSROq0j07OhScuo19akGaR.79AXrN0eJvlO', 'Rafael Factor', NULL, NULL, NULL, '2025-07', NULL, NULL, NULL, NULL, NULL, NULL, 'Active', '2025-11-03 05:48:32', '2025-11-03 05:48:32'),
(17, 'user', 'krist', '$2y$10$qP9xEwJR64NdhyhC00S8NuX1ONHGUzMG6mOUW4HtifEho2hfI9y3q', 'Krist Factor', NULL, NULL, NULL, '2025-08', NULL, NULL, NULL, NULL, NULL, NULL, 'Active', '2025-11-04 01:27:14', '2025-11-04 01:27:14');

-- --------------------------------------------------------

--
-- Table structure for table `user_attendance`
--

DROP TABLE IF EXISTS `user_attendance`;
CREATE TABLE IF NOT EXISTS `user_attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `session_id` int DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `check_in_time` datetime DEFAULT NULL,
  `check_out_time` datetime DEFAULT NULL,
  `status` enum('Present','Absent','Late','Excused') NOT NULL,
  `attendance_method` enum('Biometric','PIN','QR Code','Manual') DEFAULT 'Manual',
  `is_verified` tinyint(1) DEFAULT '0',
  `verification_score` decimal(5,2) DEFAULT NULL,
  `notes` text,
  `marked_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_session` (`user_id`,`session_id`),
  KEY `marked_by` (`marked_by`),
  KEY `idx_user_date` (`user_id`,`attendance_date`),
  KEY `idx_session` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `year_levels`
--

DROP TABLE IF EXISTS `year_levels`;
CREATE TABLE IF NOT EXISTS `year_levels` (
  `id` int NOT NULL AUTO_INCREMENT,
  `year_level_code` varchar(20) NOT NULL,
  `year_level_name` varchar(100) NOT NULL,
  `description` text,
  `order_number` int NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `year_level_code` (`year_level_code`),
  KEY `idx_status` (`status`),
  KEY `idx_order` (`order_number`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `year_levels`
--

INSERT INTO `year_levels` (`id`, `year_level_code`, `year_level_name`, `description`, `order_number`, `status`, `created_at`, `updated_at`) VALUES
(1, 'G1', 'Grade 1', 'Grade 1Students', 1, 'Active', '2025-10-29 05:55:03', '2025-10-30 11:54:35'),
(2, 'G2', 'Grade 2', 'Grade 2 Students', 2, 'Active', '2025-10-29 05:55:03', '2025-10-30 11:54:57'),
(3, 'G3', 'Grade 3', 'Grade 3 Students', 3, 'Active', '2025-10-29 05:55:03', '2025-10-30 11:55:30'),
(4, 'G4', 'Grade 4', 'Grade 4 Students', 4, 'Active', '2025-10-29 05:55:03', '2025-10-30 11:55:53'),
(5, 'G5', 'Grade 5', 'Grade 5 students', 5, 'Active', '2025-10-29 05:55:03', '2025-10-30 11:56:49'),
(6, 'G6', 'Grade 6', 'Grade 6 Students(Graduating Elementary)', 6, 'Active', '2025-10-30 11:57:37', '2025-10-30 11:57:37');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_alerts`
--
ALTER TABLE `attendance_alerts`
  ADD CONSTRAINT `attendance_alerts_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  ADD CONSTRAINT `attendance_sessions_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_attendance`
--
ALTER TABLE `staff_attendance`
  ADD CONSTRAINT `staff_attendance_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`year_level_id`) REFERENCES `year_levels` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_attendance`
--
ALTER TABLE `student_attendance`
  ADD CONSTRAINT `student_attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tutor_attendance`
--
ALTER TABLE `tutor_attendance`
  ADD CONSTRAINT `tutor_attendance_ibfk_1` FOREIGN KEY (`tutor_id`) REFERENCES `tutors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tutor_matching_suggestions`
--
ALTER TABLE `tutor_matching_suggestions`
  ADD CONSTRAINT `tutor_matching_suggestions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tutor_matching_suggestions_ibfk_2` FOREIGN KEY (`tutor_id`) REFERENCES `tutors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tutor_student_matching`
--
ALTER TABLE `tutor_student_matching`
  ADD CONSTRAINT `tutor_student_matching_ibfk_1` FOREIGN KEY (`tutor_id`) REFERENCES `tutors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tutor_student_matching_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_attendance`
--
ALTER TABLE `user_attendance`
  ADD CONSTRAINT `user_attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_attendance_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `attendance_sessions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_attendance_ibfk_3` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
