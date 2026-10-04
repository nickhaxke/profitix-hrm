<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AttendanceLog;
use Illuminate\Contracts\Console\Kernel;

$logs = AttendanceLog::where('employee_id', 2)
    ->where('punch_time', '>=', '2026-04-01')
    ->orderBy('punch_time', 'asc')
    ->get();

echo 'Total Logs: '.$logs->count()."\n";
$last = null;
foreach ($logs as $log) {
    $time = Carbon\Carbon::parse($log->punch_time);
    $diff = $last ? $time->diffInSeconds($last) : 'N/A';
    $skipped = ($last && $diff < 300) ? 'YES' : 'NO';
    echo "Time: {$log->punch_time} | Type: {$log->punch_type} | Diff: {$diff}s | Skipped: {$skipped}\n";
    if ($skipped === 'NO') {
        $last = $time;
    }
}
