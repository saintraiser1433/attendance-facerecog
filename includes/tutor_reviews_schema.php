<?php

/**
 * Create tutor reviews table if missing (safe to call per request).
 */
function tutor_reviews_ensure_schema(mysqli $conn): void
{
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tutor_reviews (
        id INT NOT NULL AUTO_INCREMENT,
        matching_id INT NOT NULL,
        tutor_id INT NOT NULL,
        student_id INT NOT NULL,
        rating TINYINT NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_matching (matching_id),
        KEY idx_tutor (tutor_id),
        KEY idx_student (student_id),
        CONSTRAINT tutor_reviews_matching_fk FOREIGN KEY (matching_id) REFERENCES tutor_student_matching (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

