-- Add profile_picture column to tutors table
-- Run this if you already have the tutors table without profile_picture column

USE cuteko;

-- Check and add profile_picture column to tutors
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'cuteko' 
AND TABLE_NAME = 'tutors' 
AND COLUMN_NAME = 'profile_picture';

SET @query = IF(@col_exists = 0, 
    'ALTER TABLE tutors ADD COLUMN profile_picture VARCHAR(255) NULL AFTER address', 
    'SELECT "profile_picture column already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Tutor profile_picture column added successfully!' AS Status;
