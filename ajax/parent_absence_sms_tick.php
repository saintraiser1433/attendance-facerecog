<?php

/**
 * Consecutive-absence parent SMS tick; JSON-only response (same hardening as sms_test.php).
 */
ob_start();
ini_set('display_errors', '0');
session_start();

header('Content-Type: application/json; charset=utf-8');

/**
 * @param array<string, mixed> $data
 */
function absence_sms_tick_respond(array $data): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if (!isset($_SESSION['username'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        absence_sms_tick_respond(['ok' => false, 'error' => 'Unauthorized']);
    }

    require_once __DIR__ . '/../db_conn.php';
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../includes/sms_schema.php';
    require_once __DIR__ . '/../includes/ph_phone.php';
    require_once __DIR__ . '/../includes/sms_send.php';

    sms_ensure_schema($conn);

    $settings = sms_load_settings($conn);
    if (!$settings || $settings['api_username'] === '' || $settings['api_password'] === '') {
        absence_sms_tick_respond(['ok' => true, 'sent' => 0, 'skipped' => 0, 'errors' => [], 'note' => 'SMS credentials not configured']);
    }

    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    $sql = "SELECT s.id, s.student_id, s.first_name, s.last_name,
                   s.phone, s.emergency_contact_phone, s.emergency_contact_name
            FROM students s
            INNER JOIN student_attendance sa_today ON sa_today.student_id = s.id
                AND sa_today.attendance_date = ?
                AND sa_today.status = 'Absent'
            INNER JOIN student_attendance sa_y ON sa_y.student_id = s.id
                AND sa_y.attendance_date = ?
                AND sa_y.status = 'Absent'
            WHERE s.status = 'Active'";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ss', $today, $yesterday);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $sent = 0;
    $skipped = 0;
    $errors = [];
    /** @var list<array{student_id:string, channel:string, phone:string, contact_name:?string}> */
    $notifications = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $sid = (string) ($row['student_id'] ?? '');
        $emRaw = isset($row['emergency_contact_phone']) ? trim((string) $row['emergency_contact_phone']) : '';
        $stuRaw = isset($row['phone']) ? trim((string) $row['phone']) : '';
        $emName = isset($row['emergency_contact_name']) ? trim((string) $row['emergency_contact_name']) : '';

        $channel = null;
        $phone = null;

        // If a guardian number was entered, use ONLY that (no silent fallback to student phone).
        if ($emRaw !== '') {
            $phone = normalize_ph_mobile($emRaw);
            if ($phone === null || !preg_match('/^\+639\d{9}$/', $phone)) {
                $errors[] = "{$sid}: Emergency contact phone is invalid or cannot be normalized to +639 (e.g. use 09XXXXXXXXX). SMS not sent — fix the number in the student record.";
                $skipped++;
                continue;
            }
            $channel = 'emergency';
        } else {
            $phone = normalize_ph_mobile($stuRaw);
            if ($phone === null || !preg_match('/^\+639\d{9}$/', $phone)) {
                $skipped++;
                continue;
            }
            $channel = 'student';
        }

        $dup = mysqli_prepare($conn, 'SELECT id, success FROM parent_absence_sms_log WHERE student_id = ? AND streak_end_date = ? LIMIT 1');
        mysqli_stmt_bind_param($dup, 'is', $row['id'], $today);
        mysqli_stmt_execute($dup);
        $dupRes = mysqli_stmt_get_result($dup);
        $prevLog = $dupRes ? mysqli_fetch_assoc($dupRes) : null;
        mysqli_stmt_close($dup);
        if ($prevLog && (int) $prevLog['success'] === 1) {
            $skipped++;
            continue;
        }

        $name = trim($row['first_name'] . ' ' . $row['last_name']);

        if ($channel === 'emergency') {
            $greet = $emName !== '' ? "Dear {$emName}," : 'Dear Parent/Guardian,';
            $body = "{$greet} this is regarding student {$name} (ID: {$sid}). They have been marked ABSENT two school days in a row: {$yesterday} and {$today}. Please ensure they attend or contact the school. Thank you.";
        } else {
            $body = "Attendance notice for {$name} (ID: {$sid}): marked ABSENT two days in a row ({$yesterday} and {$today}). No emergency contact on file — this message was sent to the number we have for the student. Please contact the school if needed.";
        }

        $send = sms_send_raw(
            $settings['gateway_url'],
            $settings['api_username'],
            $settings['api_password'],
            $body,
            [$phone]
        );

        $preview = function_exists('mb_substr') ? mb_substr($body, 0, 480) : substr($body, 0, 480);
        $ok = $send['ok'] ? 1 : 0;
        $err = $send['ok'] ? '' : (string) ($send['error'] ?? 'Unknown');

        if ($prevLog) {
            $upd = mysqli_prepare($conn, 'UPDATE parent_absence_sms_log SET phone_used = ?, message_preview = ?, success = ?, error_message = ? WHERE id = ?');
            mysqli_stmt_bind_param($upd, 'ssisi', $phone, $preview, $ok, $err, $prevLog['id']);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
        } else {
            $ins = mysqli_prepare($conn, 'INSERT INTO parent_absence_sms_log (student_id, streak_end_date, phone_used, message_preview, success, error_message) VALUES (?, ?, ?, ?, ?, ?)');
            mysqli_stmt_bind_param($ins, 'isssis', $row['id'], $today, $phone, $preview, $ok, $err);
            mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);
        }

        if ($send['ok']) {
            $sent++;
            $notifications[] = [
                'student_id' => $sid,
                'channel' => $channel,
                'phone' => $phone,
                'emergency_contact_name' => $channel === 'emergency' ? ($emName !== '' ? $emName : null) : null,
            ];
            if (!$prevLog || (int) $prevLog['success'] !== 1) {
                $who = $channel === 'emergency' ? "guardian at {$phone}" : "student number {$phone}";
                $alertMsg = "Consecutive absence SMS sent for {$name} ({$sid}) to {$who}.";
                $insAlert = mysqli_prepare($conn, "INSERT INTO attendance_alerts (student_id, alert_type, absence_count, alert_message, severity) VALUES (?, 'Consecutive Absence', 2, ?, 'High')");
                mysqli_stmt_bind_param($insAlert, 'is', $row['id'], $alertMsg);
                mysqli_stmt_execute($insAlert);
                mysqli_stmt_close($insAlert);
            }
        } else {
            $errors[] = 'Student ' . $sid . ': ' . $err;
        }
    }
    mysqli_stmt_close($stmt);

    absence_sms_tick_respond([
        'ok' => true,
        'sent' => $sent,
        'skipped' => $skipped,
        'errors' => $errors,
        'notifications' => $notifications,
    ]);
} catch (Throwable $e) {
    absence_sms_tick_respond(['ok' => false, 'error' => $e->getMessage(), 'sent' => 0, 'skipped' => 0, 'errors' => []]);
}
