<?php

use App\Models\Device;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$new_ip = '172.16.213.203';

echo "All devices:\n";
foreach (Device::all() as $d) {
    echo $d->id.' - '.$d->name.' - '.$d->ip_address."\n";
}

$device = Device::where('name', 'like', '%tanrail%')->first();

if ($device) {
    echo "\nUpdating Device ID: {$device->id}, Name: {$device->name}\n";
    echo "Old IP: {$device->ip_address}\n";
    $device->ip_address = $new_ip;
    $device->save();
    echo "New IP saved: {$device->ip_address}\n\n";

    // Quick TCP test on port 4370
    echo "Testing connection on port 4370...\n";
    $fp = @fsockopen($device->ip_address, $device->port ?: 4370, $errno, $errstr, 2);
    if ($fp) {
        echo "✅ Port 4370: REACHABLE\n";
        fclose($fp);
    } else {
        echo "❌ Port 4370: NOT REACHABLE ($errstr)\n";
    }
} else {
    echo "Device 'tanrail' not found.\n";
}
