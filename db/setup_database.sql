-- Complete Database Setup for Attendance System
-- Database: cuteko

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS cuteko;
USE cuteko;

-- Table for users (admin and user roles)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('user','admin') NOT NULL,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    student_id VARCHAR(50),
    teacher_id VARCHAR(50),
    department VARCHAR(100),
    year_level VARCHAR(50),
    section VARCHAR(50),
    profile_picture VARCHAR(255),
    attendance_pin VARCHAR(6),
    qr_code TEXT,
    status ENUM('Active','Inactive','Suspended') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_student_id (student_id),
    UNIQUE KEY unique_teacher_id (teacher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default users only if they don't exist (preserves existing admin)
-- This will NOT delete or overwrite existing users
INSERT IGNORE INTO users (id, role, username, password, name) VALUES
(1, 'admin', 'elias', '81dc9bdb52d04dc20036dbd8313ed055', 'Elias Abdurrahman'),
(2, 'user', 'john', 'e2fc714c4727ee9395f324cd2e7f331f', 'John Doe');

-- Table for year levels
CREATE TABLE IF NOT EXISTS year_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year_level_code VARCHAR(20) UNIQUE NOT NULL,
    year_level_name VARCHAR(100) NOT NULL,
    description TEXT,
    order_number INT NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_order (order_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default year levels (safe - won't duplicate)
INSERT IGNORE INTO year_levels (year_level_code, year_level_name, description, order_number) VALUES
('YR1', 'Year 1', 'First Year Students', 1),
('YR2', 'Year 2', 'Second Year Students', 2),
('YR3', 'Year 3', 'Third Year Students', 3),
('YR4', 'Year 4', 'Fourth Year Students', 4),
('YR5', 'Year 5', 'Fifth Year Students (Graduate Level)', 5);

-- Table for students
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    date_of_birth DATE,
    address TEXT,
    year_level_id INT,
    section VARCHAR(50),
    student_id_image VARCHAR(255),
    profile_picture VARCHAR(255),
    enrollment_date DATE NOT NULL,
    status ENUM('Active', 'Inactive', 'Graduated') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (year_level_id) REFERENCES year_levels(id) ON DELETE SET NULL,
    INDEX idx_year_level (year_level_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Trigger to auto-generate student_id based on year (format: YYYY-NN)
DROP TRIGGER IF EXISTS before_insert_student;
DELIMITER $$
CREATE TRIGGER before_insert_student
BEFORE INSERT ON students
FOR EACH ROW
BEGIN
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
END$$
DELIMITER ;

-- Table for tutors
CREATE TABLE IF NOT EXISTS tutors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tutor_id VARCHAR(50) UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    specialization VARCHAR(255),
    qualification VARCHAR(255),
    experience_years INT DEFAULT 0,
    hourly_rate DECIMAL(10, 2),
    address TEXT,
    profile_picture VARCHAR(255),
    status ENUM('Active', 'Inactive', 'On Leave') DEFAULT 'Active',
    hire_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Trigger to auto-generate tutor_id based on year (format: YYYY-NN)
DROP TRIGGER IF EXISTS before_insert_tutor;
DELIMITER $$
CREATE TRIGGER before_insert_tutor
BEFORE INSERT ON tutors
FOR EACH ROW
BEGIN
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
END$$
DELIMITER ;

-- Table for tutor attendance
CREATE TABLE IF NOT EXISTS tutor_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tutor_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    check_in_time DATETIME,
    check_out_time DATETIME,
    status ENUM('Present', 'Absent', 'Late', 'Excused') NOT NULL,
    is_biometric_verified BOOLEAN DEFAULT FALSE,
    fingerprint_match_score DECIMAL(5,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tutor_id) REFERENCES tutors(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tutor_date (tutor_id, attendance_date),
    INDEX idx_tutor_date (tutor_id, attendance_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for tutor-student matching
CREATE TABLE IF NOT EXISTS tutor_student_matching (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tutor_id INT NOT NULL,
    student_id INT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    status ENUM('Active', 'Inactive', 'Completed') DEFAULT 'Active',
    start_date DATE NOT NULL,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tutor_id) REFERENCES tutors(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tutor_student (tutor_id, student_id, subject)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for staff attendance
CREATE TABLE IF NOT EXISTS staff_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    check_in_time TIME,
    check_out_time TIME,
    status ENUM('Present', 'Absent', 'Late', 'On Leave') NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_staff_date (staff_id, attendance_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for student attendance with digital timestamps
CREATE TABLE IF NOT EXISTS student_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    check_in_time DATETIME,
    check_out_time DATETIME,
    status ENUM('Present', 'Absent', 'Late', 'Excused') NOT NULL,
    is_biometric_verified BOOLEAN DEFAULT FALSE,
    fingerprint_match_score DECIMAL(5,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_date (student_id, attendance_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for fingerprint templates
CREATE TABLE IF NOT EXISTS fingerprint_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('student', 'staff', 'tutor') NOT NULL,
    fingerprint_template LONGTEXT NOT NULL,
    fingerprint_image LONGBLOB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_fingerprint (user_id, user_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for reports
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_type ENUM('attendance', 'tutor_matching', 'staff_attendance', 'student_performance') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    report_data LONGTEXT NOT NULL,
    format ENUM('pdf', 'excel', 'csv') NOT NULL,
    generated_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample tutors (tutor_id will be auto-generated as YYYY-01, YYYY-02, etc.)
INSERT INTO tutors (first_name, last_name, email, phone, specialization, qualification, experience_years, hourly_rate, status, hire_date) VALUES
('Sarah', 'Johnson', 'sarah.johnson@example.com', '555-0101', 'Mathematics', 'PhD in Mathematics', 8, 45.00, 'Active', '2020-01-15'),
('Michael', 'Chen', 'michael.chen@example.com', '555-0102', 'Physics', 'Masters in Physics', 5, 40.00, 'Active', '2021-03-20'),
('Emily', 'Williams', 'emily.williams@example.com', '555-0103', 'English Literature', 'PhD in English', 10, 50.00, 'Active', '2019-09-01')
ON DUPLICATE KEY UPDATE email=email;

-- Insert sample students (student_id will be auto-generated as YYYY-01, YYYY-02, etc.)
INSERT INTO students (first_name, last_name, email, phone, enrollment_date, status) VALUES
('Alice', 'Brown', 'alice.brown@example.com', '555-0201', '2023-09-01', 'Active'),
('Bob', 'Davis', 'bob.davis@example.com', '555-0202', '2023-09-01', 'Active'),
('Charlie', 'Wilson', 'charlie.wilson@example.com', '555-0203', '2023-09-01', 'Active')
ON DUPLICATE KEY UPDATE email=email;

-- Table for tutor-student matching suggestions (AI-based)
CREATE TABLE IF NOT EXISTS tutor_matching_suggestions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    tutor_id INT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    match_score DECIMAL(5,2) NOT NULL,
    reason TEXT,
    status ENUM('Pending', 'Accepted', 'Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (tutor_id) REFERENCES tutors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for attendance notifications and alerts
CREATE TABLE IF NOT EXISTS attendance_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    alert_type ENUM('Frequent Absence', 'Late Pattern', 'Perfect Attendance', 'Improvement') NOT NULL,
    absence_count INT DEFAULT 0,
    alert_message TEXT NOT NULL,
    severity ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    is_read BOOLEAN DEFAULT FALSE,
    notified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_student_alert (student_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for system notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    notification_type ENUM('Attendance Alert', 'Tutor Match', 'Report Generated', 'System') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    priority ENUM('Low', 'Normal', 'High', 'Urgent') DEFAULT 'Normal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for biometric authentication logs
CREATE TABLE IF NOT EXISTS biometric_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('student', 'staff', 'tutor') NOT NULL,
    action_type ENUM('Check-In', 'Check-Out', 'Enrollment', 'Verification Failed') NOT NULL,
    fingerprint_match_score DECIMAL(5,2),
    success BOOLEAN NOT NULL,
    ip_address VARCHAR(45),
    device_info TEXT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_timestamp (user_id, timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for encrypted data keys (for data encryption)
CREATE TABLE IF NOT EXISTS encryption_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) UNIQUE NOT NULL,
    encrypted_key TEXT NOT NULL,
    algorithm VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for announcements
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    posted_by INT NOT NULL,
    target_audience ENUM('All', 'Students', 'Teachers', 'Admins') DEFAULT 'All',
    priority ENUM('Low', 'Normal', 'High', 'Urgent') DEFAULT 'Normal',
    is_pinned BOOLEAN DEFAULT FALSE,
    attachment VARCHAR(255),
    expiry_date DATE,
    status ENUM('Active', 'Archived') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_target_status (target_audience, status),
    INDEX idx_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for attendance sessions (for teachers to create attendance sessions)
CREATE TABLE IF NOT EXISTS attendance_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    session_name VARCHAR(255) NOT NULL,
    session_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    department VARCHAR(100),
    year_level VARCHAR(50),
    section VARCHAR(50),
    attendance_method ENUM('Biometric', 'PIN', 'QR Code', 'Manual') DEFAULT 'Manual',
    qr_code TEXT,
    session_code VARCHAR(10),
    status ENUM('Scheduled', 'Active', 'Closed', 'Cancelled') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_teacher_date (teacher_id, session_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for user attendance (unified for all users)
CREATE TABLE IF NOT EXISTS user_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id INT,
    attendance_date DATE NOT NULL,
    check_in_time DATETIME,
    check_out_time DATETIME,
    status ENUM('Present', 'Absent', 'Late', 'Excused') NOT NULL,
    attendance_method ENUM('Biometric', 'PIN', 'QR Code', 'Manual') DEFAULT 'Manual',
    is_verified BOOLEAN DEFAULT FALSE,
    verification_score DECIMAL(5,2),
    notes TEXT,
    marked_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES attendance_sessions(id) ON DELETE SET NULL,
    FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_session (user_id, session_id),
    INDEX idx_user_date (user_id, attendance_date),
    INDEX idx_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample tutor-student matching
INSERT INTO tutor_student_matching (tutor_id, student_id, subject, status, start_date) VALUES
(1, 1, 'Calculus', 'Active', '2024-01-10'),
(2, 2, 'Quantum Physics', 'Active', '2024-01-15'),
(3, 3, 'Creative Writing', 'Active', '2024-01-20')
ON DUPLICATE KEY UPDATE subject=subject;
