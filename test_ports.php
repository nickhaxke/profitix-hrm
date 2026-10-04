<?php

$ip = '172.16.213.203';
$ports = [80, 443, 4370, 4371, 8080];
echo "Testing ports on $ip:\n";
foreach ($ports as $port) {
    $fp = @fsockopen($ip, $port, $errno, $errstr, 2);
    if ($fp) {
        echo "✅ Port $port: OPEN\n";
        fclose($fp);
    } else {
        echo "❌ Port $port: CLOSED\n";
    }
}
