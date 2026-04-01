<?php
// AI-Based Tutor-Student Matching System
$success_message = '';
$error_message = '';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../includes/tutor_reviews_schema.php';
tutor_reviews_ensure_schema($conn);

// ── Filter state ────────────────────────────────────────────────────────────
// Priority: POST (new generation) > GET > SESSION > default
$is_generate_request = (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'generate_matches'
);

if (isset($_GET['clear_filter'])) {
    unset($_SESSION['tutor_matching_ai_filter_student_id']);
    unset($_SESSION['tutor_matching_ai_filter_subject']);
}

// FIX: Resolve filter values ONCE, in a single authoritative pass.
// POST wins (a new generation just happened), then GET, then SESSION, then nothing.
if ($is_generate_request) {
    $effective_filter_student_id = isset($_POST['student_id']) && is_numeric($_POST['student_id'])
        ? (int) $_POST['student_id'] : 0;
    $effective_filter_subject = trim((string) ($_POST['subject'] ?? ''));
    if ($effective_filter_student_id <= 0 || $effective_filter_subject === '') {
        $error_message = 'Please select a student and enter a subject before generating.';
        $is_generate_request = false;
    }
    // Persist to session for subsequent page loads
    if ($is_generate_request) {
        $_SESSION['tutor_matching_ai_filter_student_id'] = $effective_filter_student_id;
        $_SESSION['tutor_matching_ai_filter_subject']    = $effective_filter_subject;
    }
} elseif (isset($_GET['student_id']) || isset($_GET['subject'])) {
    $effective_filter_student_id = isset($_GET['student_id']) && is_numeric($_GET['student_id'])
        ? (int) $_GET['student_id'] : 0;
    $effective_filter_subject = trim((string) ($_GET['subject'] ?? ''));
    $_SESSION['tutor_matching_ai_filter_student_id'] = $effective_filter_student_id;
    $_SESSION['tutor_matching_ai_filter_subject']    = $effective_filter_subject;
} else {
    $effective_filter_student_id = isset($_SESSION['tutor_matching_ai_filter_student_id'])
        ? (int) $_SESSION['tutor_matching_ai_filter_student_id'] : 0;
    $effective_filter_subject = isset($_SESSION['tutor_matching_ai_filter_subject'])
        ? trim((string) $_SESSION['tutor_matching_ai_filter_subject']) : '';
}

// ── Handle manual matching acceptance ───────────────────────────────────────
if (isset($_POST['accept_match'])) {
    $suggestion_id = intval($_POST['suggestion_id']);
    $end_date = isset($_POST['end_date']) ? trim((string) $_POST['end_date']) : '';
    if ($end_date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
        $error_message = 'Please select a valid end date.';
    }
    $update_sql = "UPDATE tutor_matching_suggestions SET status = 'Accepted' WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "i", $suggestion_id);

    if (empty($error_message) && mysqli_stmt_execute($stmt)) {
        $get_sql = "SELECT student_id, tutor_id, subject FROM tutor_matching_suggestions WHERE id = ?";
        $get_stmt = mysqli_prepare($conn, $get_sql);
        mysqli_stmt_bind_param($get_stmt, "i", $suggestion_id);
        mysqli_stmt_execute($get_stmt);
        $result = mysqli_stmt_get_result($get_stmt);
        $suggestion = mysqli_fetch_assoc($result);

        $match_sql = "INSERT INTO tutor_student_matching (tutor_id, student_id, subject, status, start_date, end_date)
                      VALUES (?, ?, ?, 'Active', CURDATE(), ?)";
        $match_stmt = mysqli_prepare($conn, $match_sql);
        mysqli_stmt_bind_param($match_stmt, "iiss",
            $suggestion['tutor_id'], $suggestion['student_id'], $suggestion['subject'], $end_date);
        mysqli_stmt_execute($match_stmt);
        mysqli_stmt_close($match_stmt);
        mysqli_stmt_close($get_stmt);

        $success_message = "Match accepted and activated successfully!";
    }
    mysqli_stmt_close($stmt);
}

// ── Handle match rejection ───────────────────────────────────────────────────
if (isset($_POST['reject_match'])) {
    $suggestion_id = intval($_POST['suggestion_id']);
    $update_sql = "UPDATE tutor_matching_suggestions SET status = 'Rejected' WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "i", $suggestion_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $success_message = "Match suggestion rejected.";
}

// ── Generate AI matching suggestions ────────────────────────────────────────
if ($is_generate_request) {
    $student_id = $effective_filter_student_id;   // already resolved above
    $subject    = $effective_filter_subject;
    $generated_count = 0;

    // Clear ALL existing matches/suggestions, then build a fresh new set.
    // This removes old suggested matches and accepted/actual match rows.
    $before_clear_count = 0;
    $after_clear_count = 0;
    $before_actual_matches = 0;
    $after_actual_matches = 0;
    $count_before_res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM tutor_matching_suggestions");
    if ($count_before_res && ($row_before = mysqli_fetch_assoc($count_before_res))) {
        $before_clear_count = (int) ($row_before['c'] ?? 0);
    }
    $count_before_match_res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM tutor_student_matching");
    if ($count_before_match_res && ($row_match_before = mysqli_fetch_assoc($count_before_match_res))) {
        $before_actual_matches = (int) ($row_match_before['c'] ?? 0);
    }

    try {
        mysqli_query($conn, "DELETE FROM tutor_matching_suggestions");
        mysqli_query($conn, "DELETE FROM tutor_student_matching");
    } catch (Throwable $e2) {
        $error_message = "Could not clear old matches/suggestions: " . $e2->getMessage();
    }

    $count_after_res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM tutor_matching_suggestions");
    if ($count_after_res && ($row_after = mysqli_fetch_assoc($count_after_res))) {
        $after_clear_count = (int) ($row_after['c'] ?? 0);
    }
    $count_after_match_res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM tutor_student_matching");
    if ($count_after_match_res && ($row_match_after = mysqli_fetch_assoc($count_after_match_res))) {
        $after_actual_matches = (int) ($row_match_after['c'] ?? 0);
    }
    if ($after_clear_count > 0 && empty($error_message)) {
        $error_message = "Could not clear all suggestions (remaining: {$after_clear_count}).";
    }
    if ($after_actual_matches > 0 && empty($error_message)) {
        $error_message = "Could not clear all existing matches (remaining: {$after_actual_matches}).";
    }
    if (!empty($error_message)) {
        // Stop generation if cleanup failed so user sees the exact issue.
    } else {

    // Student performance proxy: attendance last 30 days
    $perf_absent = $perf_late = $perf_present = 0;
    $perf_sql = "SELECT
                    SUM(CASE WHEN status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count,
                    SUM(CASE WHEN status = 'Late'    THEN 1 ELSE 0 END) AS late_count,
                    SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_count
                 FROM student_attendance
                 WHERE student_id = ?
                   AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    $perf_stmt = mysqli_prepare($conn, $perf_sql);
    mysqli_stmt_bind_param($perf_stmt, "i", $student_id);
    mysqli_stmt_execute($perf_stmt);
    $perf_res = mysqli_stmt_get_result($perf_stmt);
    if ($perf_res && ($perf_row = mysqli_fetch_assoc($perf_res))) {
        $perf_absent  = (int) ($perf_row['absent_count']  ?? 0);
        $perf_late    = (int) ($perf_row['late_count']    ?? 0);
        $perf_present = (int) ($perf_row['present_count'] ?? 0);
    }
    mysqli_stmt_close($perf_stmt);

    // Fetch all active tutors with aggregated review data
    $tutor_sql = "SELECT t.id, t.tutor_id, CONCAT(t.first_name, ' ', t.last_name) AS name,
                         t.specialization, t.experience_years, t.hourly_rate, t.status,
                         COALESCE(rr.avg_rating, 0)  AS avg_rating,
                         COALESCE(rr.review_count, 0) AS review_count
                  FROM tutors t
                  LEFT JOIN (
                      SELECT tutor_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
                      FROM tutor_reviews
                      GROUP BY tutor_id
                  ) rr ON rr.tutor_id = t.id
                  WHERE t.status = 'Active'
                  ORDER BY
                    (COALESCE(rr.review_count, 0) > 0) DESC,
                    COALESCE(rr.avg_rating, 0) DESC,
                    t.experience_years DESC";
    $tutor_result = mysqli_query($conn, $tutor_sql);

    while ($tutor = mysqli_fetch_assoc($tutor_result)) {
        $match_score = 0;
        $reasons     = [];

        // Specialization match
        if (stripos($tutor['specialization'], $subject) !== false) {
            $match_score += 40;
            $reasons[] = "Specializes in " . $tutor['specialization'];
        } else {
            $match_score += 10;
        }

        // Experience
        if ($tutor['experience_years'] >= 10) {
            $match_score += 30;
            $reasons[] = "Highly experienced (" . $tutor['experience_years'] . " years)";
        } elseif ($tutor['experience_years'] >= 5) {
            $match_score += 20;
            $reasons[] = "Experienced (" . $tutor['experience_years'] . " years)";
        } else {
            $match_score += 10;
        }

        // Rating
        $avg_rating   = (float) ($tutor['avg_rating']   ?? 0);
        $review_count = (int)   ($tutor['review_count'] ?? 0);
        if ($review_count > 0) {
            if ($avg_rating >= 9.0) {
                $match_score += 45;
                $reasons[] = "Excellent rating (" . number_format($avg_rating, 1) . "/10) - Recommended";
            } elseif ($avg_rating >= 8.0) {
                $match_score += 35;
                $reasons[] = "High rating (" . number_format($avg_rating, 1) . "/10) - Recommended";
            } elseif ($avg_rating >= 7.0) {
                $match_score += 25;
                $reasons[] = "Good rating (" . number_format($avg_rating, 1) . "/10)";
            } else {
                $match_score += 10;
            }
        }

        // Availability
        if ($tutor['status'] === 'Active') {
            $match_score += 20;
            $reasons[] = "Currently available";
        }

        // Workload
        $workload_sql = "SELECT COUNT(*) AS count FROM tutor_student_matching
                         WHERE tutor_id = ? AND status = 'Active'";
        $workload_stmt = mysqli_prepare($conn, $workload_sql);
        mysqli_stmt_bind_param($workload_stmt, "i", $tutor['id']);
        mysqli_stmt_execute($workload_stmt);
        $workload_result = mysqli_stmt_get_result($workload_stmt);
        $workload = (int) (mysqli_fetch_assoc($workload_result)['count'] ?? 0);
        mysqli_stmt_close($workload_stmt);
        if ($workload < 3) {
            $match_score += 10;
            $reasons[] = "Low workload (can give more attention)";
        }

        // Past sessions
        $history_sql = "SELECT
                            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed_count,
                            SUM(CASE WHEN status = 'Active'    THEN 1 ELSE 0 END) AS active_count,
                            SUM(CASE WHEN subject = ?          THEN 1 ELSE 0 END) AS same_subject_count
                        FROM tutor_student_matching
                        WHERE tutor_id = ? AND student_id = ?";
        $history_stmt = mysqli_prepare($conn, $history_sql);
        mysqli_stmt_bind_param($history_stmt, "sii", $subject, $tutor['id'], $student_id);
        mysqli_stmt_execute($history_stmt);
        $history_res = mysqli_stmt_get_result($history_stmt);
        $history     = $history_res ? mysqli_fetch_assoc($history_res) : null;
        mysqli_stmt_close($history_stmt);

        $completed_count    = (int) ($history['completed_count']    ?? 0);
        $same_subject_count = (int) ($history['same_subject_count'] ?? 0);
        if ($same_subject_count > 0) {
            $match_score += 20;
            $reasons[] = "Worked with this student before (same subject)";
        } elseif ($completed_count > 0) {
            $match_score += 10;
            $reasons[] = "Worked with this student before";
        }

        // Student attendance performance
        $issues = $perf_absent + $perf_late;
        if ($issues >= 5) {
            $reasons[] = "Student attendance last 30 days: {$perf_absent} absent, {$perf_late} late";
            if ($tutor['experience_years'] >= 5 || $avg_rating >= 8.0) {
                $match_score += 10;
                $reasons[] = "Stronger support recommended for improvement";
            }
        }

        if ($match_score >= 50) {
            $reason_text = implode(", ", $reasons);
            $insert_sql = "INSERT INTO tutor_matching_suggestions
                           (student_id, tutor_id, subject, match_score, reason, status)
                           VALUES (?, ?, ?, ?, ?, 'Pending')";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "iisds",
                $student_id, $tutor['id'], $subject, $match_score, $reason_text);
            mysqli_stmt_execute($insert_stmt);
            mysqli_stmt_close($insert_stmt);
            $generated_count++;
        }
    }

    $cleared_count = max(0, $before_clear_count - $after_clear_count);
    $cleared_actual = max(0, $before_actual_matches - $after_actual_matches);
    $success_message = "AI matching suggestions generated successfully! Cleared {$cleared_count} suggestion row(s), cleared {$cleared_actual} actual match row(s), inserted {$generated_count} new result(s).";
    }
}

// ── Fetch pending suggestions ────────────────────────────────────────────────
// FIX: Use the already-resolved $effective_filter_student_id / $effective_filter_subject.
// There is no second override block — the values are stable from here on.

// FIX: "is_recommended" is now derived purely from avg_rating >= 8.0 (raw /10 scale),
// matching the same threshold used in the scoring algorithm and the PHP display code.
// Previously the SQL used avg_rating >= 8 while PHP recalculated via percentage,
// causing mismatches. Now both SQL and PHP use the same raw-rating threshold.
$suggestions_sql = "SELECT tms.*,
                           CONCAT(s.first_name, ' ', s.last_name) AS student_name,
                           s.id AS student_db_id,
                           s.student_id,
                           CONCAT(t.first_name, ' ', t.last_name) AS tutor_name,
                           t.tutor_id, t.specialization, t.experience_years, t.hourly_rate,
                           COALESCE(tr.avg_rating,   0) AS avg_rating,
                           COALESCE(tr.review_count, 0) AS review_count,
                           CASE WHEN COALESCE(tr.review_count, 0) > 0
                                 AND (COALESCE(tr.avg_rating, 0) * 10) > 80
                                THEN 1 ELSE 0 END AS is_recommended
                    FROM tutor_matching_suggestions tms
                    JOIN students s ON tms.student_id = s.id
                    JOIN tutors   t ON tms.tutor_id   = t.id
                    LEFT JOIN (
                        SELECT tutor_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
                        FROM tutor_reviews
                        GROUP BY tutor_id
                    ) tr ON tr.tutor_id = t.id
                    WHERE tms.status = 'Pending'";

$order_sql = " ORDER BY
                    COALESCE(tr.avg_rating,   0) DESC,
                    COALESCE(tr.review_count, 0) DESC,
                    tms.match_score DESC,
                    tms.created_at  DESC";

// FIX: Build the query in one clean conditional block with no duplicate re-assignments.
if ($effective_filter_student_id > 0 && $effective_filter_subject !== '') {
    $suggestions_sql .= " AND tms.student_id = ?
                           AND LOWER(TRIM(tms.subject)) = LOWER(TRIM(?)) " . $order_sql;
    $suggestions_stmt = mysqli_prepare($conn, $suggestions_sql);
    mysqli_stmt_bind_param($suggestions_stmt, "is",
        $effective_filter_student_id, $effective_filter_subject);
    mysqli_stmt_execute($suggestions_stmt);
    $suggestions_result = mysqli_stmt_get_result($suggestions_stmt);
} elseif ($effective_filter_student_id > 0) {
    $suggestions_sql .= " AND tms.student_id = ? " . $order_sql;
    $suggestions_stmt = mysqli_prepare($conn, $suggestions_sql);
    mysqli_stmt_bind_param($suggestions_stmt, "i", $effective_filter_student_id);
    mysqli_stmt_execute($suggestions_stmt);
    $suggestions_result = mysqli_stmt_get_result($suggestions_stmt);
} elseif ($effective_filter_subject !== '') {
    $suggestions_sql .= " AND LOWER(TRIM(tms.subject)) = LOWER(TRIM(?)) " . $order_sql;
    $suggestions_stmt = mysqli_prepare($conn, $suggestions_sql);
    mysqli_stmt_bind_param($suggestions_stmt, "s", $effective_filter_subject);
    mysqli_stmt_execute($suggestions_stmt);
    $suggestions_result = mysqli_stmt_get_result($suggestions_stmt);
} else {
    $suggestions_sql .= $order_sql;
    $suggestions_result = mysqli_query($conn, $suggestions_sql);
}

// Students dropdown
$students_sql    = "SELECT id, student_id, CONCAT(first_name, ' ', last_name) AS name
                    FROM students WHERE status = 'Active' ORDER BY first_name";
$students_result = mysqli_query($conn, $students_sql);
?>

<style>
    .matching-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 30px;
        margin-bottom: 30px;
    }
    .suggestion-card {
        background: #fff;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        transition: all 0.3s;
    }
    .suggestion-card:hover {
        border-color: #3498db;
        box-shadow: 0 4px 8px rgba(52,152,219,0.2);
    }
    .match-score {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 1.1em;
    }
    .score-high   { background: #d4edda; color: #155724; }
    .score-medium { background: #fff3cd; color: #856404; }
    .score-low    { background: #f8d7da; color: #721c24; }
    .alert { padding: 12px 20px; margin-bottom: 20px; border-radius: 4px; font-weight: 500; }
    .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-danger  { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50; }
    .form-control { width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
    .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 600; margin-right: 10px; transition: all 0.3s; }
    .btn-primary { background: #3498db; color: #fff; }
    .btn-success { background: #27ae60; color: #fff; }
    .btn-danger  { background: #e74c3c; color: #fff; }
    .btn-sm { padding: 6px 12px; font-size: 12px; }
    .tutor-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 15px 0; }
    .info-item { display: flex; align-items: center; }
    .info-item i { margin-right: 8px; color: #3498db; }
    .rating-pill {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 10px; border-radius: 999px;
        background: #eef6ff; color: #1f4d7a;
        font-weight: 700; font-size: 12px;
    }
    .mini-carousel { position: relative; min-height: 40px; background: #ffffff; border: 1px solid #e8eef5; border-radius: 8px; padding: 10px 12px; overflow: hidden; }
    .mini-carousel .slide { position: absolute; inset: 10px 12px 10px 12px; opacity: 0; transition: opacity 500ms ease; font-size: 13px; color: #34495e; line-height: 1.35; }
    .mini-carousel .slide.active { opacity: 1; }
</style>

<div class="matching-card">
    <h2><i class="fas fa-brain"></i> AI-Powered Tutor Matching System</h2>
    <p>Intelligent matching based on subject needs, availability, experience, and preferences</p>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="action" value="generate_matches">
        <div style="display:grid;grid-template-columns:2fr 2fr 1fr;gap:15px;align-items:end;">
            <div class="form-group">
                <label for="student_id">Select Student</label>
                <select class="form-control" id="student_id" name="student_id" required>
                    <option value="">-- Select Student --</option>
                    <?php while ($student = mysqli_fetch_assoc($students_result)): ?>
                        <option value="<?php echo (int) $student['id']; ?>"
                            <?php echo ((int) $student['id'] === $effective_filter_student_id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($student['student_id'] . ' - ' . $student['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="subject">Subject Needed</label>
                <input type="text" class="form-control" id="subject" name="subject"
                       placeholder="e.g., Mathematics, Physics"
                       value="<?php echo htmlspecialchars($effective_filter_subject); ?>" required>
            </div>
            <div class="form-group">
                <button type="submit" name="generate_suggestions" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-magic"></i> Generate Matches
                </button>
            </div>
        </div>
    </form>
</div>

<div class="matching-card">
    <h3><i class="fas fa-list-alt"></i> Pending Match Suggestions</h3>

    <?php if ($effective_filter_student_id > 0 || $effective_filter_subject !== ''): ?>
        <div style="margin:10px 0 16px 0;padding:10px 12px;border:1px solid #e8eef5;background:#f8f9fa;border-radius:8px;color:#34495e;">
            Showing suggestions<?php echo $effective_filter_student_id > 0 ? ' for selected student' : ''; ?>
            <?php echo $effective_filter_subject !== '' ? ' › subject: <strong>' . htmlspecialchars($effective_filter_subject) . '</strong>' : ''; ?>.
            <a href="?page=tutor_matching_ai&clear_filter=1" style="margin-left:8px;color:#3498db;text-decoration:none;font-weight:700;">Clear filter</a>
        </div>
    <?php endif; ?>

    <?php if (mysqli_num_rows($suggestions_result) > 0): ?>
        <?php while ($suggestion = mysqli_fetch_assoc($suggestions_result)): ?>
            <div class="suggestion-card">
                <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:15px;">
                    <div>
                        <h4 style="margin:0;color:#2c3e50;">
                            <i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($suggestion['student_name']); ?>
                            <i class="fas fa-arrow-right" style="color:#7f8c8d;margin:0 10px;"></i>
                            <i class="fas fa-chalkboard-teacher"></i> <?php echo htmlspecialchars($suggestion['tutor_name']); ?>
                        </h4>
                        <p style="margin:5px 0;color:#7f8c8d;">Subject: <strong><?php echo htmlspecialchars($suggestion['subject']); ?></strong></p>
                    </div>
                    <div style="text-align:right;">
                        <?php
                        $score       = (float) $suggestion['match_score'];
                        $score_class = $score >= 80 ? 'score-high' : ($score >= 60 ? 'score-medium' : 'score-low');
                        $avg_rating_for_badge = (float) ($suggestion['avg_rating'] ?? 0);
                        $review_count_for_badge = (int) ($suggestion['review_count'] ?? 0);
                        // Recommended when rating percentage is above 80% => avg_rating > 8.0 out of 10
                        $is_recommended = ($avg_rating_for_badge > 8.0);
                        ?>
                        <span class="match-score <?php echo $score_class; ?>">
                            <?php echo number_format($score, 0); ?>% Match
                        </span>
                        <?php if ($is_recommended): ?>
                            <span style="display:inline-block;margin-left:6px;padding:6px 12px;border-radius:20px;background:#27ae60;color:#fff;font-weight:700;font-size:12px;vertical-align:middle;">
                                ★ Recommended
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="tutor-info">
                    <div class="info-item">
                        <i class="fas fa-graduation-cap"></i>
                        <span><?php echo htmlspecialchars($suggestion['specialization']); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <span><?php echo (int) $suggestion['experience_years']; ?> years experience</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-dollar-sign"></i>
                        <span>$<?php echo number_format((float) $suggestion['hourly_rate'], 2); ?>/hour</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-star"></i>
                        <?php
                        $avg = (float) $suggestion['avg_rating'];
                        $rc  = (int)   $suggestion['review_count'];
                        ?>
                        <span class="rating-pill">
                            <?php echo $rc > 0 ? number_format($avg, 1) . '/10' : 'No rating'; ?>
                            <?php if ($rc > 0): ?>
                                <span style="font-weight:600;color:#5b7895;">(<?php echo $rc; ?> reviews)</span>
                            <?php endif; ?>
                            <?php if ($is_recommended): ?>
                                <span style="margin-left:6px;padding:2px 8px;border-radius:999px;background:#d4edda;color:#155724;font-weight:700;">Recommended</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <?php
                $comments_sql  = "SELECT comment, rating, created_at FROM tutor_reviews
                                  WHERE tutor_id = ? ORDER BY created_at DESC LIMIT 5";
                $comments_stmt = mysqli_prepare($conn, $comments_sql);
                mysqli_stmt_bind_param($comments_stmt, "i", $suggestion['tutor_id']);
                mysqli_stmt_execute($comments_stmt);
                $comments_res = mysqli_stmt_get_result($comments_stmt);
                $slides = [];
                while ($c = mysqli_fetch_assoc($comments_res)) { $slides[] = $c; }
                mysqli_stmt_close($comments_stmt);
                ?>
                <?php if (count($slides) > 0): ?>
                    <div style="margin-top:10px;">
                        <div class="mini-carousel">
                            <?php foreach ($slides as $idx => $c): ?>
                                <div class="slide <?php echo $idx === 0 ? 'active' : ''; ?>">
                                    <strong><?php echo (int) $c['rating']; ?>/10</strong>
                                    <span style="color:#7f8c8d;">—</span>
                                    <?php echo htmlspecialchars($c['comment']); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <small style="color:#7f8c8d;display:block;margin-top:6px;">Recent tutor feedback (auto-fade)</small>
                    </div>
                <?php endif; ?>

                <div style="background:#f8f9fa;padding:12px;border-radius:4px;margin:15px 0;">
                    <strong><i class="fas fa-lightbulb"></i> Why this match?</strong>
                    <?php if ($is_recommended): ?>
                        <div style="margin-top:6px;font-weight:700;color:#1e7e34;">
                            <i class="fas fa-check-circle"></i> Recommended tutor based on high rating.
                        </div>
                    <?php endif; ?>
                    <p style="margin:5px 0 0 0;"><?php echo htmlspecialchars($suggestion['reason']); ?></p>
                </div>

                <div style="margin-top:15px;">
                    <form method="POST" action="" style="display:inline;" class="accept-match-form">
                        <input type="hidden" name="suggestion_id" value="<?php echo (int) $suggestion['id']; ?>">
                        <input type="hidden" name="end_date"      value="">
                        <input type="hidden" name="accept_match"  value="1">
                        <button type="button" class="btn btn-success btn-sm js-accept-match-open">
                            <i class="fas fa-check"></i> Accept Match
                        </button>
                    </form>
                    <form method="POST" action="" style="display:inline;">
                        <input type="hidden" name="suggestion_id" value="<?php echo (int) $suggestion['id']; ?>">
                        <button type="submit" name="reject_match" class="btn btn-danger btn-sm">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align:center;padding:40px;color:#7f8c8d;">
            <i class="fas fa-inbox" style="font-size:3em;margin-bottom:15px;display:block;"></i>
            <p>No pending match suggestions. Generate suggestions using the form above.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Accept Match Modal (End Date) -->
<div id="acceptModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:9999;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:10px;max-width:520px;width:100%;padding:18px 18px 14px 18px;box-shadow:0 10px 30px rgba(0,0,0,0.25);">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <h3 style="margin:0;"><i class="fas fa-calendar"></i> Set tutoring end date</h3>
            <button type="button" id="acceptModalClose" style="border:none;background:transparent;font-size:18px;cursor:pointer;">✕</button>
        </div>
        <p style="color:#666;font-size:13px;margin:10px 0 14px 0;">
            Choose when this tutor assignment ends. When the end date is past, the matching will
            auto-update to <strong>Completed</strong> and become reviewable.
        </p>
        <div style="display:grid;gap:10px;">
            <label style="font-weight:600;">End date</label>
            <input type="date" id="acceptEndDate" style="padding:10px;border:1px solid #ddd;border-radius:6px;">
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
            <button type="button" id="acceptModalCancel" class="btn btn-sm" style="background:#95a5a6;color:#fff;"><i class="fas fa-times"></i> Cancel</button>
            <button type="button" id="acceptModalSubmit" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Submit</button>
        </div>
    </div>
</div>

<script>
(function () {
    // ── Accept modal ─────────────────────────────────────────────────────────
    var modal      = document.getElementById('acceptModal');
    var closeBtn   = document.getElementById('acceptModalClose');
    var cancelBtn  = document.getElementById('acceptModalCancel');
    var submitBtn  = document.getElementById('acceptModalSubmit');
    var endInput   = document.getElementById('acceptEndDate');
    var activeForm = null;

    function openModal(form) {
        activeForm = form;
        var today = new Date();
        var min   = today.toISOString().split('T')[0];
        endInput.min   = min;
        endInput.value = min;
        modal.style.display = 'flex';
    }
    function closeModal() { modal.style.display = 'none'; activeForm = null; }

    document.querySelectorAll('.js-accept-match-open').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var form = btn.closest('form');
            if (form) openModal(form);
        });
    });
    [closeBtn, cancelBtn].forEach(function (b) { b.addEventListener('click', closeModal); });
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

    submitBtn.addEventListener('click', function () {
        if (!activeForm || !endInput.value) return;
        activeForm.querySelector('input[name="end_date"]').value = endInput.value;
        activeForm.submit();
    });

    // ── Mini carousels ────────────────────────────────────────────────────────
    document.querySelectorAll('.mini-carousel').forEach(function (car) {
        var slides = car.querySelectorAll('.slide');
        if (!slides || slides.length <= 1) return;
        var idx = 0;
        setInterval(function () {
            slides[idx].classList.remove('active');
            idx = (idx + 1) % slides.length;
            slides[idx].classList.add('active');
        }, 2600);
    });
})();
</script>

<?php if (isset($suggestions_stmt) && $suggestions_stmt) { mysqli_stmt_close($suggestions_stmt); } ?>