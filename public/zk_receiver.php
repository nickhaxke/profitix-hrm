<?php

// Raw PHP Receiver for ZKTeco ADMS
// Bypasses all Laravel middleware, routing, and CSRF.

$body = file_get_contents('php://input');

$log = [
    'time' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'] ?? '',
    'uri' => $_SERVER['REQUEST_URI'] ?? '',
    'query' => $_GET,
    'headers' => getallheaders(),
    'body' => $body,
];

file_put_contents(
    __DIR__.'/zk_device.log',
    json_encode($log, JSON_UNESCAPED_SLASHES).PHP_EOL,
    FILE_APPEND
);

header('Content-Type: text/plain');

// Emulate ADMS responses to keep the device happy
$uri = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($uri, '/iclock/cdata') !== false && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $sn = $_GET['SN'] ?? 'UNKNOWN';
    echo "GET OPTION FROM: {$sn}\nErrorDelay=60\nDelay=30\nTransTimes=00:00;14:00\nTransInterval=1\nTransFlag=1111000000\nRealtime=1\nEncrypt=0";
} else {
    echo 'OK';
}
