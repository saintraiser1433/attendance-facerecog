<?php
if (!isset($conn)) { include __DIR__ . '/../db_conn.php'; }
include_once __DIR__ . '/biometric_security.php';

function saveFingerprintTemplate(int $userId, string $userType, string $rawTemplate): bool {
	global $conn;
	$enc = encryptBiometric($rawTemplate);
	$sql = "INSERT INTO fingerprint_templates (user_id, user_type, fingerprint_template) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE fingerprint_template = VALUES(fingerprint_template), updated_at = CURRENT_TIMESTAMP";
	$stmt = mysqli_prepare($conn, $sql);
	mysqli_stmt_bind_param($stmt, 'iss', $userId, $userType, $enc);
	$res = mysqli_stmt_execute($stmt);
	mysqli_stmt_close($stmt);
	return $res;
}

function getTemplatesForType(string $userType): array {
	global $conn;
	$list = [];
	$sql = "SELECT user_id, fingerprint_template FROM fingerprint_templates WHERE user_type = ?";
	$stmt = mysqli_prepare($conn, $sql);
	mysqli_stmt_bind_param($stmt, 's', $userType);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);
	while ($row = mysqli_fetch_assoc($result)) {
		$list[] = [
			'user_id' => (int)$row['user_id'],
			'raw' => decryptBiometric($row['fingerprint_template'])
		];
	}
	mysqli_stmt_close($stmt);
	return $list;
}

function logBiometricAttempt(int $userId = 0, string $userType = 'staff', string $actionType = 'Verification', float $score = 0.0, bool $success = false): void {
	global $conn;
	$ip = $_SERVER['REMOTE_ADDR'] ?? '';
	$now = date('Y-m-d H:i:s');
	$sql = "INSERT INTO biometric_logs (user_id, user_type, action_type, fingerprint_match_score, success, ip_address, timestamp) VALUES (?, ?, ?, ?, ?, ?, ?)";
	$stmt = mysqli_prepare($conn, $sql);
	$successInt = $success ? 1 : 0;
	mysqli_stmt_bind_param($stmt, 'issdiss', $userId, $userType, $actionType, $score, $successInt, $ip, $now);
	mysqli_stmt_execute($stmt);
	mysqli_stmt_close($stmt);
}

function verifyTemplateSimple(string $probe, array $candidates): array {
	// Simple placeholder matcher: exact-match = score 98-100, else 0.
	foreach ($candidates as $row) {
		if (!empty($row['raw']) && hash_equals($row['raw'], $probe)) {
			return ['user_id' => $row['user_id'], 'score' => 98.5, 'match' => true];
		}
	}
	return ['user_id' => 0, 'score' => 0.0, 'match' => false];
}
