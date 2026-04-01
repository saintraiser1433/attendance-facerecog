-- ============================================================================
-- SIMPLE FIX - Just copy and paste this entire file into phpMyAdmin SQL tab
-- ============================================================================

USE cuteko;

-- Create year_levels table
CREATE TABLE IF NOT EXISTS year_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year_level_code VARCHAR(20) UNIQUE NOT NULL,
    year_level_name VARCHAR(100) NOT NULL,
    description TEXT,
    order_number INT NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add default year levels
INSERT IGNORE INTO year_levels (year_level_code, year_level_name, description, order_number) VALUES
('YR1', 'Year 1', 'First Year Students', 1),
('YR2', 'Year 2', 'Second Year Students', 2),
('YR3', 'Year 3', 'Third Year Students', 3),
('YR4', 'Year 4', 'Fourth Year Students', 4),
('YR5', 'Year 5', 'Fifth Year Students', 5);

-- Add columns to students table
ALTER TABLE students ADD COLUMN IF NOT EXISTS year_level_id INT NULL;
ALTER TABLE students ADD COLUMN IF NOT EXISTS section VARCHAR(50) NULL;
ALTER TABLE students ADD COLUMN IF NOT EXISTS student_id_image VARCHAR(255) NULL;
ALTER TABLE students ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) NULL;

-- Add foreign key (ignore if exists)
ALTER TABLE students ADD CONSTRAINT fk_students_year_level 
FOREIGN KEY (year_level_id) REFERENCES year_levels(id) ON DELETE SET NULL;

-- Add indexes
CREATE INDEX IF NOT EXISTS idx_year_level ON students(year_level_id);
CREATE INDEX IF NOT EXISTS idx_status ON students(status);

-- Done!
SELECT 'SUCCESS! Database updated. Refresh your browser now.' AS Message;
