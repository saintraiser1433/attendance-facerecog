<?php
require_once __DIR__ . '/core/helpers/Requests/src/Autoload.php';

WpOrg\Requests\Autoload::register();

$url = 'http://localhost:5555/coreComponents/enroll.php';
$payload = [
    'data' => json_encode([
        'index_finger' => ['abc'],
        'middle_finger' => ['def'],
    ]),
];

try {
    $res = WpOrg\Requests\Requests::post(
        $url,
        ['Content-Type' => 'application/x-www-form-urlencoded'],
        $payload
    );
    echo "status=" . ($res->status_code ?? 'unknown') . PHP_EOL;
    echo "body=" . substr((string)($res->body ?? ''), 0, 200) . PHP_EOL;
} catch (Throwable $e) {
    echo "EX:" . $e->getMessage() . PHP_EOL;
}

