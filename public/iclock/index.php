<?php

$body = file_get_contents('php://input');

$uri = $_SERVER['REQUEST_URI'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? '';
$sn = $_GET['SN'] ?? 'UNKNOWN';
$registryCode = "1234567890-{$sn}";

$responseBody = 'OK';
$contentType = 'text/plain';

if (strpos($uri, '/iclock/registry') !== false && $method === 'POST') {
    $contentType = 'application/push;charset=UTF-8';
    $responseBody = "RegistryCode={$registryCode}";
}

$log = [
    'time' => date('Y-m-d H:i:s'),
    'method' => $method,
    'uri' => $uri,
    'query' => $_GET,
    'headers' => getallheaders(),
    'remote_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    'body' => $body,
    'response' => $responseBody,
];

$logPath = __DIR__.'/../../storage/logs/zkadms-diagnostic.log';

file_put_contents(
    $logPath,
    json_encode($log, JSON_UNESCAPED_SLASHES).PHP_EOL,
    FILE_APPEND
);

header("Content-Type: {$contentType}");
echo $responseBody;
