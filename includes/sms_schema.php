<?php

/**
 * Create SMS-related tables/columns if missing (safe to call on each request).
 */
function sms_ensure_schema(mysqli $conn): void
{
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS sms_settings (
        id INT NOT NULL PRIMARY KEY DEFAULT 1,
        gateway_url VARCHAR(512) NOT NULL DEFAULT 'https://api.sms-gate.app/3rdparty/v1',
        api_username VARCHAR(255) NOT NULL DEFAULT '',
        api_password VARCHAR(255) NOT NULL DEFAULT ''
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysqli_query($conn, "INSERT IGNORE INTO sms_settings (id) VALUES (1)");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS parent_absence_sms_log (
        id INT NOT NULL AUTO_INCREMENT,
        student_id INT NOT NULL,
        streak_end_date DATE NOT NULL,
        phone_used VARCHAR(24) NOT NULL,
        message_preview VARCHAR(500) DEFAULT NULL,
        success TINYINT(1) NOT NULL DEFAULT 1,
        error_message TEXT,
        sent_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_student_streak (student_id, streak_end_date),
        KEY idx_sent (sent_at),
        KEY idx_student (student_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $check = mysqli_query($conn, "SHOW COLUMNS FROM students LIKE 'emergency_contact_name'");
    if ($check && mysqli_num_rows($check) === 0) {
        mysqli_query($conn, "ALTER TABLE students ADD COLUMN emergency_contact_name VARCHAR(120) NULL AFTER phone");
    }
    if ($check) {
        mysqli_free_result($check);
    }
    $check2 = mysqli_query($conn, "SHOW COLUMNS FROM students LIKE 'emergency_contact_phone'");
    if ($check2 && mysqli_num_rows($check2) === 0) {
        mysqli_query($conn, "ALTER TABLE students ADD COLUMN emergency_contact_phone VARCHAR(24) NULL AFTER emergency_contact_name");
    }
    if ($check2) {
        mysqli_free_result($check2);
    }

    try {
        mysqli_query($conn, "ALTER TABLE attendance_alerts 
            MODIFY COLUMN alert_type ENUM(
                'Frequent Absence','Late Pattern','Perfect Attendance','Improvement','Consecutive Absence'
            ) NOT NULL");
    } catch (Throwable $e) {
        // Table missing or already migrated
    }
}
