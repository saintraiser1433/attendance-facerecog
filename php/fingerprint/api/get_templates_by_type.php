<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../../db_conn.php';
require_once __DIR__ . '/../../../includes/biometric_security.php';

$userType = isset($_POST['user_type']) ? trim($_POST['user_type']) : '';

if ($userType === '') {
	echo json_encode(['ok' => false, 'error' => 'Missing user_type parameter']);
	exit;
}

$allowedTypes = ['student', 'staff', 'tutor'];
if (!in_array($userType, $allowedTypes)) {
	echo json_encode(['ok' => false, 'error' => 'Invalid user_type']);
	exit;
}

$sql = "SELECT user_id, fingerprint_template FROM fingerprint_templates WHERE user_type = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 's', $userType);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$templates = [];
while ($row = mysqli_fetch_assoc($result)) {
	$templates[] = [
		'user_id' => (int)$row['user_id'],
		'raw' => decryptBiometric($row['fingerprint_template'])
	];
}
mysqli_stmt_close($stmt);

echo json_encode(['ok' => true, 'templates' => $templates]);