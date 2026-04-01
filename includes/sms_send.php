<?php

use AndroidSmsGateway\Client;
use AndroidSmsGateway\Domain\Message;

/**
 * @return array{ok:bool, id?:string, error?:string}
 */
function sms_send_raw(string $gatewayUrl, string $apiUser, string $apiPassword, string $text, array $e164Numbers): array
{
    foreach ($e164Numbers as $n) {
        if (!preg_match('/^\+639\d{9}$/', $n)) {
            return ['ok' => false, 'error' => 'Invalid destination (must be +639XXXXXXXXX): ' . $n];
        }
    }
    if ($apiUser === '' || $apiPassword === '') {
        return ['ok' => false, 'error' => 'SMS API username or password is not configured.'];
    }
    $base = rtrim($gatewayUrl, '/');
    try {
        $client = new Client($apiUser, $apiPassword, $base);
        $msg = new Message($text, $e164Numbers);
        $state = $client->Send($msg);
        return ['ok' => true, 'id' => $state->ID()];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Load row id=1 from sms_settings (after sms_ensure_schema).
 */
function sms_load_settings(mysqli $conn): ?array
{
    $res = mysqli_query($conn, "SELECT gateway_url, api_username, api_password FROM sms_settings WHERE id = 1 LIMIT 1");
    if (!$res || !($row = mysqli_fetch_assoc($res))) {
        return null;
    }
    return $row;
}
