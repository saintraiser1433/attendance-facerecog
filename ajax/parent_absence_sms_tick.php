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

    while ($row = mysqli_fetch_assoc($result)) {
        $phone = null;
        if (!empty($row['emergency_contact_phone'])) {
            $phone = normalize_ph_mobile($row['emergency_contact_phone']);
        }
        if ($phone === null && !empty($row['phone'])) {
            $phone = normalize_ph_mobile($row['phone']);
        }
        if ($phone === null || !preg_match('/^\+639\d{9}$/', $phone)) {
            $skipped++;
            continue;
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
        $sid = $row['student_id'];
        $body = "From your school attendance system: {$name} (Student ID: {$sid}) was recorded Absent on two consecutive days ({$yesterday} and {$today}). Please contact the school if you have questions.";

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
            if (!$prevLog || (int) $prevLog['success'] !== 1) {
                $alertMsg = "Parent SMS sent (consecutive absence): {$name} ({$sid}). Notified {$phone}.";
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

    absence_sms_tick_respond(['ok' => true, 'sent' => $sent, 'skipped' => $skipped, 'errors' => $errors]);
} catch (Throwable $e) {
    absence_sms_tick_respond(['ok' => false, 'error' => $e->getMessage(), 'sent' => 0, 'skipped' => 0, 'errors' => []]);
}
