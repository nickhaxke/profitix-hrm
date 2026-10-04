<?php

use App\Http\Controllers\AttendanceController;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

try {
    $controller = new AttendanceController;
    // Simulate repair method but handle redirection/response
    $response = $controller->repair();
    echo "Repair completed successfully locally!\n";
} catch (Exception $e) {
    echo 'ERROR: '.$e->getMessage()."\n";
    echo "Trace:\n".$e->getTraceAsString()."\n";
}
