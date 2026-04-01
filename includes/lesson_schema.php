<?php
function lessons_ensure_schema(mysqli $conn): void
{
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lessons (
        id INT NOT NULL AUTO_INCREMENT,
        lesson_name VARCHAR(150) NOT NULL,
        status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_lesson_name (lesson_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tutor_lessons (
        id INT NOT NULL AUTO_INCREMENT,
        tutor_id INT NOT NULL,
        lesson_id INT NOT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_tutor_lesson (tutor_id, lesson_id),
        KEY idx_tutor_lessons_tutor (tutor_id),
        KEY idx_tutor_lessons_lesson (lesson_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS matching_lesson_progress (
        id INT NOT NULL AUTO_INCREMENT,
        matching_id INT NOT NULL,
        lesson_id INT NOT NULL,
        is_completed TINYINT(1) NOT NULL DEFAULT 0,
        completed_at DATETIME NULL DEFAULT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_matching_lesson (matching_id, lesson_id),
        KEY idx_matching_progress_matching (matching_id),
        KEY idx_matching_progress_lesson (lesson_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function lessons_resolve_ids(mysqli $conn, array $lessonNames): array
{
    $resolvedIds = [];
    foreach ($lessonNames as $rawName) {
        $name = trim((string) $rawName);
        if ($name === '') {
            continue;
        }

        $sel = mysqli_prepare($conn, "SELECT id FROM lessons WHERE LOWER(TRIM(lesson_name)) = LOWER(TRIM(?)) LIMIT 1");
        mysqli_stmt_bind_param($sel, "s", $name);
        mysqli_stmt_execute($sel);
        $selRes = mysqli_stmt_get_result($sel);
        $row = $selRes ? mysqli_fetch_assoc($selRes) : null;
        mysqli_stmt_close($sel);

        if ($row && isset($row['id'])) {
            $resolvedIds[] = (int) $row['id'];
            continue;
        }

        $ins = mysqli_prepare($conn, "INSERT INTO lessons (lesson_name, status) VALUES (?, 'Active')");
        mysqli_stmt_bind_param($ins, "s", $name);
        $ok = mysqli_stmt_execute($ins);
        $newId = $ok ? (int) mysqli_insert_id($conn) : 0;
        mysqli_stmt_close($ins);
        if ($newId > 0) {
            $resolvedIds[] = $newId;
        }
    }

    return array_values(array_unique(array_filter($resolvedIds)));
}
?>
