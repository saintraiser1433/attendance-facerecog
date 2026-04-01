<?php
require_once __DIR__ . '/../includes/lesson_schema.php';
lessons_ensure_schema($conn);

// Filters
$tutor_id = isset($_GET['tutor_id']) && is_numeric($_GET['tutor_id']) ? (int) $_GET['tutor_id'] : 0;
$date_from = isset($_GET['date_from']) ? trim((string) $_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim((string) $_GET['date_to']) : '';

if ($date_from === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
    $date_from = date('Y-m-01');
}
if ($date_to === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $date_to = date('Y-m-d');
}

$export = isset($_GET['export']) ? (string) $_GET['export'] : '';

// Base query (per matching + per lesson)
$sql = "SELECT
            t.id AS tutor_pk,
            t.tutor_id AS tutor_code,
            CONCAT(t.first_name, ' ', t.last_name) AS tutor_name,
            s.student_id AS student_code,
            CONCAT(s.first_name, ' ', s.last_name) AS student_name,
            tsm.id AS matching_id,
            tsm.subject,
            tsm.status,
            tsm.start_date,
            tsm.end_date,
            l.lesson_name,
            mlp.is_completed,
            mlp.completed_at
        FROM tutor_student_matching tsm
        INNER JOIN tutors t ON tsm.tutor_id = t.id
        INNER JOIN students s ON tsm.student_id = s.id
        LEFT JOIN matching_lesson_progress mlp ON mlp.matching_id = tsm.id
        LEFT JOIN lessons l ON l.id = mlp.lesson_id
        WHERE tsm.start_date BETWEEN ? AND ?
          AND (? = 0 OR t.id = ?)
        ORDER BY t.last_name, t.first_name, tsm.start_date DESC, tsm.id DESC, l.lesson_name ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssii", $date_from, $date_to, $tutor_id, $tutor_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$rows = [];
while ($res && ($r = mysqli_fetch_assoc($res))) {
    $rows[] = $r;
}
mysqli_stmt_close($stmt);

// Tutor dropdown
$tutor_list = mysqli_query($conn, "SELECT id, tutor_id, CONCAT(first_name, ' ', last_name) AS name FROM tutors ORDER BY first_name, last_name");

function pr_csv_cell(string $s): string {
    $s = str_replace('"', '""', $s);
    return '"' . $s . '"';
}

if ($export === 'csv') {
    if (ob_get_level()) { @ob_end_clean(); }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="tutor_progress_by_lesson_' . date('Y-m-d') . '.csv"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel

    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'Tutor ID', 'Tutor Name',
        'Student ID', 'Student Name',
        'Matching ID', 'Subject', 'Status',
        'Start Date', 'End Date',
        'Lesson', 'Lesson Done', 'Lesson Completed At'
    ]);
    foreach ($rows as $r) {
        fputcsv($out, [
            (string) ($r['tutor_code'] ?? ''),
            (string) ($r['tutor_name'] ?? ''),
            (string) ($r['student_code'] ?? ''),
            (string) ($r['student_name'] ?? ''),
            (string) ($r['matching_id'] ?? ''),
            (string) ($r['subject'] ?? ''),
            (string) ($r['status'] ?? ''),
            (string) ($r['start_date'] ?? ''),
            (string) ($r['end_date'] ?? ''),
            (string) ($r['lesson_name'] ?? ''),
            ((int) ($r['is_completed'] ?? 0) === 1) ? 'Yes' : 'No',
            (string) ($r['completed_at'] ?? ''),
        ]);
    }
    fclose($out);
    exit;
}

if ($export === 'print') {
    if (ob_get_level()) { @ob_end_clean(); }
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Progress Report by Lesson</title>';
    echo '<style>
            body{font-family:Arial,sans-serif;margin:16px;color:#222}
            h1{margin:0 0 6px 0}
            .meta{color:#555;margin-bottom:14px}
            table{width:100%;border-collapse:collapse;font-size:12px}
            th,td{border:1px solid #222;padding:6px;vertical-align:top}
            th{background:#f0f0f0}
            .small{color:#555;font-size:11px}
            @media print{button{display:none}}
          </style></head><body>';
    echo '<button onclick="window.print()" style="padding:8px 10px;border:none;background:#3498db;color:#fff;border-radius:6px;cursor:pointer;">Print / Save as PDF</button>';
    echo '<h1>Progress Report (Tutor → Lessons)</h1>';
    echo '<div class="meta">Date range: <strong>' . htmlspecialchars($date_from) . '</strong> to <strong>' . htmlspecialchars($date_to) . '</strong>'
       . ($tutor_id > 0 ? (' · Tutor filter: #' . (int) $tutor_id) : '')
       . '<div class="small">Generated: ' . date('Y-m-d H:i:s') . '</div></div>';
    echo '<table><thead><tr>'
       . '<th>Tutor</th><th>Student</th><th>Matching</th><th>Subject</th><th>Status</th>'
       . '<th>Start</th><th>End</th><th>Lesson</th><th>Done</th><th>Completed At</th>'
       . '</tr></thead><tbody>';
    if (empty($rows)) {
        echo '<tr><td colspan="10" style="text-align:center;">No data for this filter.</td></tr>';
    } else {
        foreach ($rows as $r) {
            $done = ((int) ($r['is_completed'] ?? 0) === 1);
            echo '<tr>';
            echo '<td>' . htmlspecialchars(($r['tutor_code'] ?? '') . ' - ' . ($r['tutor_name'] ?? '')) . '</td>';
            echo '<td>' . htmlspecialchars(($r['student_code'] ?? '') . ' - ' . ($r['student_name'] ?? '')) . '</td>';
            echo '<td>#' . htmlspecialchars((string) ($r['matching_id'] ?? '')) . '</td>';
            echo '<td>' . htmlspecialchars((string) ($r['subject'] ?? '')) . '</td>';
            echo '<td>' . htmlspecialchars((string) ($r['status'] ?? '')) . '</td>';
            echo '<td>' . htmlspecialchars((string) ($r['start_date'] ?? '')) . '</td>';
            echo '<td>' . htmlspecialchars((string) ($r['end_date'] ?? '')) . '</td>';
            echo '<td>' . htmlspecialchars((string) ($r['lesson_name'] ?? '')) . '</td>';
            echo '<td style="font-weight:700;color:' . ($done ? '#155724' : '#721c24') . ';">' . ($done ? 'Yes' : 'No') . '</td>';
            echo '<td>' . htmlspecialchars((string) ($r['completed_at'] ?? '')) . '</td>';
            echo '</tr>';
        }
    }
    echo '</tbody></table></body></html>';
    exit;
}
?>

<div class="card" style="background:#fff;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,.1);padding:20px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;border-bottom:2px solid #f0f0f0;padding-bottom:12px;">
        <h2 style="margin:0;color:#2c3e50;"><i class="fas fa-clipboard-list"></i> Progress Report (Tutor → Lessons)</h2>
    </div>

    <form method="get" action="" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:10px;align-items:end;margin-bottom:14px;">
        <input type="hidden" name="page" value="progress_report_lessons">

        <div>
            <label style="display:block;font-weight:700;margin-bottom:6px;">Tutor</label>
            <select name="tutor_id" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;">
                <option value="0">All tutors</option>
                <?php if ($tutor_list && mysqli_num_rows($tutor_list) > 0): ?>
                    <?php while ($t = mysqli_fetch_assoc($tutor_list)): ?>
                        <option value="<?php echo (int) $t['id']; ?>" <?php echo ((int)$t['id'] === $tutor_id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(($t['tutor_id'] ?? '') . ' - ' . ($t['name'] ?? '')); ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>

        <div>
            <label style="display:block;font-weight:700;margin-bottom:6px;">Date From</label>
            <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;">
        </div>

        <div>
            <label style="display:block;font-weight:700;margin-bottom:6px;">Date To</label>
            <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;">
        </div>

        <div style="display:flex;gap:8px;">
            <button type="submit" style="padding:10px 14px;background:#3498db;color:#fff;border:none;border-radius:6px;cursor:pointer;">
                <i class="fas fa-filter"></i> Apply
            </button>
        </div>
    </form>

    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px;">
        <a class="btn" style="padding:10px 14px;background:#27ae60;color:#fff;border-radius:6px;text-decoration:none;"
           href="?page=progress_report_lessons&tutor_id=<?php echo (int) $tutor_id; ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&export=csv">
            <i class="fas fa-file-excel"></i> Export Excel (CSV)
        </a>
        <a class="btn" style="padding:10px 14px;background:#8e44ad;color:#fff;border-radius:6px;text-decoration:none;"
           target="_blank"
           href="?page=progress_report_lessons&tutor_id=<?php echo (int) $tutor_id; ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&export=print">
            <i class="fas fa-file-pdf"></i> Export PDF (Print)
        </a>
    </div>

    <div style="overflow:auto;">
        <table style="width:100%;border-collapse:collapse;background:#fff;">
            <thead>
            <tr>
                <th style="padding:10px;background:#34495e;color:#fff;">Tutor</th>
                <th style="padding:10px;background:#34495e;color:#fff;">Student</th>
                <th style="padding:10px;background:#34495e;color:#fff;">Matching</th>
                <th style="padding:10px;background:#34495e;color:#fff;">Subject</th>
                <th style="padding:10px;background:#34495e;color:#fff;">Status</th>
                <th style="padding:10px;background:#34495e;color:#fff;">Start</th>
                <th style="padding:10px;background:#34495e;color:#fff;">End</th>
                <th style="padding:10px;background:#34495e;color:#fff;">Lesson</th>
                <th style="padding:10px;background:#34495e;color:#fff;">Done</th>
                <th style="padding:10px;background:#34495e;color:#fff;">Completed At</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="10" style="padding:10px;text-align:center;">No data for this filter.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <?php $done = ((int) ($r['is_completed'] ?? 0) === 1); ?>
                    <tr>
                        <td style="padding:10px;border-bottom:1px solid #eee;"><?php echo htmlspecialchars(($r['tutor_code'] ?? '') . ' - ' . ($r['tutor_name'] ?? '')); ?></td>
                        <td style="padding:10px;border-bottom:1px solid #eee;"><?php echo htmlspecialchars(($r['student_code'] ?? '') . ' - ' . ($r['student_name'] ?? '')); ?></td>
                        <td style="padding:10px;border-bottom:1px solid #eee;">#<?php echo htmlspecialchars((string) ($r['matching_id'] ?? '')); ?></td>
                        <td style="padding:10px;border-bottom:1px solid #eee;"><?php echo htmlspecialchars((string) ($r['subject'] ?? '')); ?></td>
                        <td style="padding:10px;border-bottom:1px solid #eee;"><?php echo htmlspecialchars((string) ($r['status'] ?? '')); ?></td>
                        <td style="padding:10px;border-bottom:1px solid #eee;"><?php echo htmlspecialchars((string) ($r['start_date'] ?? '')); ?></td>
                        <td style="padding:10px;border-bottom:1px solid #eee;"><?php echo htmlspecialchars((string) ($r['end_date'] ?? '')); ?></td>
                        <td style="padding:10px;border-bottom:1px solid #eee;"><?php echo htmlspecialchars((string) ($r['lesson_name'] ?? '')); ?></td>
                        <td style="padding:10px;border-bottom:1px solid #eee;font-weight:700;color:<?php echo $done ? '#155724' : '#721c24'; ?>;"><?php echo $done ? 'Yes' : 'No'; ?></td>
                        <td style="padding:10px;border-bottom:1px solid #eee;"><?php echo htmlspecialchars((string) ($r['completed_at'] ?? '')); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

