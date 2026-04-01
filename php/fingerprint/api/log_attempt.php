<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../../db_conn.php';
require_once __DIR__ . '/../../../includes/fingerprint_functions.php';

$userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$userType = isset($_POST['user_type']) ? trim($_POST['user_type']) : 'staff';
$action = isset($_POST['action_type']) ? trim($_POST['action_type']) : 'ClientLog';
$score = isset($_POST['score']) ? (float)$_POST['score'] : 0.0;
$success = isset($_POST['success']) ? (bool)$_POST['success'] : false;

logBiometricAttempt($userId, $userType, $action, $score, $success);
echo json_encode(['ok' => true]);
