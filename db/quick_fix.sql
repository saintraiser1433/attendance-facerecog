-- Quick Fix SQL Script
-- Run this in phpMyAdmin or MySQL command line
-- This adds the missing columns to the students table

USE cuteko;

-- Step 1: Create year_levels table if it doesn't exist
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

-- Step 2: Insert default year levels
INSERT IGNORE INTO year_levels (year_level_code, year_level_name, description, order_number) VALUES
('YR1', 'Year 1', 'First Year Students', 1),
('YR2', 'Year 2', 'Second Year Students', 2),
('YR3', 'Year 3', 'Third Year Students', 3),
('YR4', 'Year 4', 'Fourth Year Students', 4),
('YR5', 'Year 5', 'Fifth Year Students (Graduate Level)', 5);

-- Step 3: Add columns to students table (only if they don't exist)
-- Check and add year_level_id
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'cuteko' 
AND TABLE_NAME = 'students' 
AND COLUMN_NAME = 'year_level_id';

SET @query = IF(@col_exists = 0, 
    'ALTER TABLE students ADD COLUMN year_level_id INT NULL AFTER address', 
    'SELECT "year_level_id already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add section
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'cuteko' 
AND TABLE_NAME = 'students' 
AND COLUMN_NAME = 'section';

SET @query = IF(@col_exists = 0, 
    'ALTER TABLE students ADD COLUMN section VARCHAR(50) NULL AFTER year_level_id', 
    'SELECT "section already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add student_id_image
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'cuteko' 
AND TABLE_NAME = 'students' 
AND COLUMN_NAME = 'student_id_image';

SET @query = IF(@col_exists = 0, 
    'ALTER TABLE students ADD COLUMN student_id_image VARCHAR(255) NULL AFTER section', 
    'SELECT "student_id_image already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add profile_picture
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'cuteko' 
AND TABLE_NAME = 'students' 
AND COLUMN_NAME = 'profile_picture';

SET @query = IF(@col_exists = 0, 
    'ALTER TABLE students ADD COLUMN profile_picture VARCHAR(255) NULL AFTER student_id_image', 
    'SELECT "profile_picture already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 4: Add foreign key constraint (if it doesn't exist)
SELECT COUNT(*) INTO @fk_exists
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = 'cuteko'
AND TABLE_NAME = 'students'
AND CONSTRAINT_NAME = 'fk_students_year_level'
AND CONSTRAINT_TYPE = 'FOREIGN KEY';

SET @query = IF(@fk_exists = 0,
    'ALTER TABLE students ADD CONSTRAINT fk_students_year_level FOREIGN KEY (year_level_id) REFERENCES year_levels(id) ON DELETE SET NULL',
    'SELECT "Foreign key already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 5: Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_year_level ON students(year_level_id);
CREATE INDEX IF NOT EXISTS idx_status ON students(status);

-- Step 6: Show results
SELECT 'Database updated successfully!' AS Status;
DESCRIBE students;
