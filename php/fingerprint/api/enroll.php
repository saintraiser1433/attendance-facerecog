<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../../db_conn.php';
require_once __DIR__ . '/../../../includes/fingerprint_functions.php';

$userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$userType = isset($_POST['user_type']) ? trim($_POST['user_type']) : '';
$template = isset($_POST['template']) ? trim($_POST['template']) : '';

if ($userId <= 0 || $userType === '' || $template === '') {
	echo json_encode(['ok' => false, 'error' => 'Missing parameters']);
	exit;
}

$allowed = ['student','staff','tutor'];
if (!in_array($userType, $allowed, true)) {
	echo json_encode(['ok' => false, 'error' => 'Invalid user_type']);
	exit;
}

$ok = saveFingerprintTemplate($userId, $userType, $template);
if ($ok) {
	logBiometricAttempt($userId, $userType, 'Enrollment', 0.0, true);
	echo json_encode(['ok' => true, 'message' => 'Fingerprint enrolled']);
} else {
	logBiometricAttempt($userId, $userType, 'Enrollment', 0.0, false);
	echo json_encode(['ok' => false, 'error' => 'Failed to save template']);
}
