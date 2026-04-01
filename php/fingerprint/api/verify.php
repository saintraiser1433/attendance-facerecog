<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../../db_conn.php';
require_once __DIR__ . '/../../../includes/fingerprint_functions.php';

$userType = isset($_POST['user_type']) ? trim($_POST['user_type']) : '';
$template = isset($_POST['template']) ? trim($_POST['template']) : '';

if ($userType === '' || $template === '') {
	echo json_encode(['ok' => false, 'error' => 'Missing parameters']);
	exit;
}

$allowed = ['student','staff','tutor'];
if (!in_array($userType, $allowed, true)) {
	echo json_encode(['ok' => false, 'error' => 'Invalid user_type']);
	exit;
}

$candidates = getTemplatesForType($userType);
$res = verifyTemplateSimple($template, $candidates);

// Log attempt
logBiometricAttempt($res['user_id'], $userType, 'Verification', (float)$res['score'], (bool)$res['match']);

// Auto-mark attendance on successful match
if ($res['match']) {
    $now = date('Y-m-d H:i:s');
    $today = date('Y-m-d');
    if ($userType === 'staff') {
        // Staff → user_attendance with session_id NULL
        // Check existing record for today (session_id IS NULL)
        $checkSql = "SELECT id, check_in_time, check_out_time FROM user_attendance WHERE user_id = ? AND attendance_date = ? AND session_id IS NULL ORDER BY id DESC LIMIT 1";
        $stmt = mysqli_prepare($conn, $checkSql);
        mysqli_stmt_bind_param($stmt, 'is', $res['user_id'], $today);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($r);
        mysqli_stmt_close($stmt);
        if (!$row) {
            $ins = "INSERT INTO user_attendance (user_id, session_id, attendance_date, check_in_time, status, attendance_method, is_verified) VALUES (?, NULL, ?, ?, 'Present', 'Biometric', 1)";
            $s2 = mysqli_prepare($conn, $ins);
            mysqli_stmt_bind_param($s2, 'iss', $res['user_id'], $today, $now);
            mysqli_stmt_execute($s2);
            mysqli_stmt_close($s2);
        } else if (empty($row['check_out_time'])) {
            $upd = "UPDATE user_attendance SET check_out_time = ? WHERE id = ?";
            $s3 = mysqli_prepare($conn, $upd);
            mysqli_stmt_bind_param($s3, 'si', $now, $row['id']);
            mysqli_stmt_execute($s3);
            mysqli_stmt_close($s3);
        }
    } elseif ($userType === 'student') {
        // Students → student_attendance
        $checkSql = "SELECT id, check_in_time, check_out_time FROM student_attendance WHERE student_id = ? AND attendance_date = ? ORDER BY id DESC LIMIT 1";
        $stmt = mysqli_prepare($conn, $checkSql);
        mysqli_stmt_bind_param($stmt, 'is', $res['user_id'], $today);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($r);
        mysqli_stmt_close($stmt);
        if (!$row) {
            $status = 'Present';
            $ins = "INSERT INTO student_attendance (student_id, attendance_date, check_in_time, status, is_biometric_verified, fingerprint_match_score) VALUES (?, ?, ?, ?, 1, ?)";
            $s2 = mysqli_prepare($conn, $ins);
            $score = (float)$res['score'];
            mysqli_stmt_bind_param($s2, 'isssd', $res['user_id'], $today, $now, $status, $score);
            mysqli_stmt_execute($s2);
            mysqli_stmt_close($s2);
        } else if (empty($row['check_out_time'])) {
            $upd = "UPDATE student_attendance SET check_out_time = ? WHERE id = ?";
            $s3 = mysqli_prepare($conn, $upd);
            mysqli_stmt_bind_param($s3, 'si', $now, $row['id']);
            mysqli_stmt_execute($s3);
            mysqli_stmt_close($s3);
        }
    } elseif ($userType === 'tutor') {
        // Tutors → tutor_attendance
        $checkSql = "SELECT id, check_in_time, check_out_time FROM tutor_attendance WHERE tutor_id = ? AND attendance_date = ? ORDER BY id DESC LIMIT 1";
        $stmt = mysqli_prepare($conn, $checkSql);
        mysqli_stmt_bind_param($stmt, 'is', $res['user_id'], $today);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($r);
        mysqli_stmt_close($stmt);
        if (!$row) {
            $status = 'Present';
            $ins = "INSERT INTO tutor_attendance (tutor_id, attendance_date, check_in_time, status, is_biometric_verified, fingerprint_match_score) VALUES (?, ?, ?, ?, 1, ?)";
            $s2 = mysqli_prepare($conn, $ins);
            $score = (float)$res['score'];
            mysqli_stmt_bind_param($s2, 'isssd', $res['user_id'], $today, $now, $status, $score);
            mysqli_stmt_execute($s2);
            mysqli_stmt_close($s2);
        } else if (empty($row['check_out_time'])) {
            $upd = "UPDATE tutor_attendance SET check_out_time = ? WHERE id = ?";
            $s3 = mysqli_prepare($conn, $upd);
            mysqli_stmt_bind_param($s3, 'si', $now, $row['id']);
            mysqli_stmt_execute($s3);
            mysqli_stmt_close($s3);
        }
    }
}

echo json_encode([
    'ok' => true,
    'match' => (bool)$res['match'],
    'user_id' => (int)$res['user_id'],
    'score' => (float)$res['score']
]);
