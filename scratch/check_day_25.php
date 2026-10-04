<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AttendanceLog;
use App\Models\AttendanceSummary;
use Illuminate\Contracts\Console\Kernel;

$summaries = AttendanceSummary::where('employee_id', 2)
    ->where('summary_date', '2026-05-25')
    ->get();

echo 'Summaries count: '.$summaries->count()."\n";
foreach ($summaries as $s) {
    echo "ID: {$s->id} | Shift ID: {$s->shift_id} | In: {$s->check_in_time} | Out: {$s->check_out_time} | Status: {$s->status}\n";
}

$logs = AttendanceLog::where('employee_id', 2)
    ->whereBetween('punch_time', ['2026-05-24 00:00:00', '2026-05-26 23:59:59'])
    ->get();

echo "\nLogs in window:\n";
foreach ($logs as $log) {
    echo "Time: {$log->punch_time} | Type: {$log->punch_type} | Processed: ".($log->is_processed ? 'YES' : 'NO')."\n";
}
