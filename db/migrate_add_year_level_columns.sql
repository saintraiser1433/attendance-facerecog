-- Migration Script: Add Year Level Support to Students Table
-- Date: October 13, 2025
-- Purpose: Add year_level_id, section, and image columns to students table

USE cuteko;

-- Check if year_levels table exists, if not create it
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

-- Insert default year levels if they don't exist
INSERT IGNORE INTO year_levels (year_level_code, year_level_name, description, order_number) VALUES
('YR1', 'Year 1', 'First Year Students', 1),
('YR2', 'Year 2', 'Second Year Students', 2),
('YR3', 'Year 3', 'Third Year Students', 3),
('YR4', 'Year 4', 'Fourth Year Students', 4),
('YR5', 'Year 5', 'Fifth Year Students (Graduate Level)', 5);

-- Add year_level_id column if it doesn't exist
SET @dbname = DATABASE();
SET @tablename = 'students';
SET @columnname = 'year_level_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' INT NULL AFTER address')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add section column if it doesn't exist
SET @columnname = 'section';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' VARCHAR(50) NULL AFTER year_level_id')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add student_id_image column if it doesn't exist
SET @columnname = 'student_id_image';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' VARCHAR(255) NULL AFTER section')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add profile_picture column if it doesn't exist
SET @columnname = 'profile_picture';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' VARCHAR(255) NULL AFTER student_id_image')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add foreign key constraint if it doesn't exist
SET @fk_name = 'fk_students_year_level';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE
      CONSTRAINT_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND CONSTRAINT_NAME = @fk_name
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD CONSTRAINT ', @fk_name, ' FOREIGN KEY (year_level_id) REFERENCES year_levels(id) ON DELETE SET NULL')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_year_level ON students(year_level_id);
CREATE INDEX IF NOT EXISTS idx_status ON students(status);

-- Display success message
SELECT 'Migration completed successfully!' as Status;
SELECT 'Year level columns added to students table' as Message;

-- Show the updated table structure
DESCRIBE students;
