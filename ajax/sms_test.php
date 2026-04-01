<?php

/**
 * Admin-only test SMS; always responds with JSON (no HTML noise from PHP errors).
 */
ob_start();
ini_set('display_errors', '0');
session_start();

header('Content-Type: application/json; charset=utf-8');

/**
 * @param array<string, mixed> $data
 */
function sms_test_respond(array $data): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if (!isset($_SESSION['username'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        sms_test_respond(['ok' => false, 'error' => 'Unauthorized']);
    }

    require_once __DIR__ . '/../db_conn.php';
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../includes/sms_schema.php';
    require_once __DIR__ . '/../includes/ph_phone.php';
    require_once __DIR__ . '/../includes/sms_send.php';

    sms_ensure_schema($conn);

    $phoneInput = isset($_POST['phone']) ? trim((string) $_POST['phone']) : '';
    $normalized = normalize_ph_mobile($phoneInput);
    if ($normalized === null || !preg_match('/^\+639\d{9}$/', $normalized)) {
        sms_test_respond(['ok' => false, 'error' => 'Invalid number. Use Philippine mobile (e.g. 9123456789 or +639123456789).']);
    }

    $settings = sms_load_settings($conn);
    if (!$settings) {
        sms_test_respond(['ok' => false, 'error' => 'SMS settings not found.']);
    }

    if ($settings['api_username'] === '' || $settings['api_password'] === '') {
        sms_test_respond([
            'ok' => false,
            'error' => 'API password is empty. Re-enter your password and click Save (leave other fields as needed).',
        ]);
    }

    $testMsg = '[Attendance System] Test SMS - gateway OK.';
    $r = sms_send_raw(
        $settings['gateway_url'],
        $settings['api_username'],
        $settings['api_password'],
        $testMsg,
        [$normalized]
    );

    if ($r['ok']) {
        sms_test_respond(['ok' => true, 'message_id' => $r['id'] ?? '']);
    }
    sms_test_respond(['ok' => false, 'error' => $r['error'] ?? 'Send failed']);
} catch (Throwable $e) {
    sms_test_respond(['ok' => false, 'error' => $e->getMessage()]);
}
