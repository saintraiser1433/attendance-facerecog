<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../../db_conn.php';
require_once __DIR__ . '/../../../includes/biometric_security.php';

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$userType = isset($_GET['user_type']) ? trim($_GET['user_type']) : '';

if ($userId <= 0 || $userType === '') {
	echo json_encode(['ok' => false, 'error' => 'Missing parameters']);
	exit;
}

$sql = "SELECT fingerprint_template FROM fingerprint_templates WHERE user_id = ? AND user_type = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'is', $userId, $userType);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$row) {
	echo json_encode(['ok' => false, 'error' => 'Not found']);
	exit;
}

echo json_encode(['ok' => true, 'template' => decryptBiometric($row['fingerprint_template'])]);
