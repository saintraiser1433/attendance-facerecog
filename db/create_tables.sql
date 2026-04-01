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
    enrollment_date DATE NOT NULL,
    status ENUM('Active', 'Inactive', 'Graduated') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
    
    IF NEW.student_id IS NULL OR NEW.student_id = '' THEN
        SET year_prefix = YEAR(CURDATE());
        SELECT COALESCE(MAX(CAST(SUBSTRING(student_id, 6) AS UNSIGNED)), 0) + 1
        INTO next_num
        FROM students
        WHERE student_id LIKE CONCAT(year_prefix, '-%');
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
    
    IF NEW.tutor_id IS NULL OR NEW.tutor_id = '' THEN
        SET year_prefix = YEAR(CURDATE());
        SELECT COALESCE(MAX(CAST(SUBSTRING(tutor_id, 6) AS UNSIGNED)), 0) + 1
        INTO next_num
        FROM tutors
        WHERE tutor_id LIKE CONCAT(year_prefix, '-%');
        SET NEW.tutor_id = CONCAT(year_prefix, '-', LPAD(next_num, 2, '0'));
    END IF;
END$$
DELIMITER ;

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
    FOREIGN KEY (tutor_id) REFERENCES users(id) ON DELETE CASCADE,
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

-- Table for fingerprint templates
CREATE TABLE IF NOT EXISTS fingerprint_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    fingerprint_template LONGTEXT NOT NULL,
    fingerprint_image LONGBLOB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_fingerprint (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for reports
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_type ENUM('attendance', 'tutor_matching', 'staff_attendance') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    report_data LONGTEXT NOT NULL,
    format ENUM('pdf', 'excel', 'csv') NOT NULL,
    generated_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
