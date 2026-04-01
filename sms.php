<?php

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/ph_phone.php';

use AndroidSmsGateway\Client;
use AndroidSmsGateway\Domain\Message;

if (!isset($_POST['message'], $_POST['number'], $_POST['username'], $_POST['password'])) {
    echo 'Please provide all required fields: message, number, username, and password';
    exit(1);
}

$login = $_POST['username'];
$password = $_POST['password'];
$messageText = $_POST['message'];
$gateway = isset($_POST['gateway_url']) ? trim((string) $_POST['gateway_url']) : 'https://api.sms-gate.app/3rdparty/v1';
if ($gateway === '') {
    $gateway = 'https://api.sms-gate.app/3rdparty/v1';
}

$number = normalize_ph_mobile($_POST['number']);
if ($number === null || !preg_match('/^\+639\d{9}$/', $number)) {
    echo 'Error: Philippine mobile number must normalize to +639 followed by 9 digits.';
    exit(1);
}

$client = new Client($login, $password, rtrim($gateway, '/'));
$message = new Message($messageText, [$number]);

try {
    $messageState = $client->Send($message);
    echo "Message sent with ID: " . $messageState->ID() . PHP_EOL;
} catch (Exception $e) {
    echo "Error sending message: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

try {
    $messageState = $client->GetState($messageState->ID());
    echo "Message state: " . $messageState->State() . PHP_EOL;
} catch (Exception $e) {
    echo "Error getting message state: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
