<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AttendanceLog;
use App\Models\AttendanceSummary;
use App\Models\Employee;
use App\Services\AttendanceService;
use Illuminate\Contracts\Console\Kernel;

// Let's implement rebuildRange inline here to test it first
function rebuildRangeInline($dateFrom, $dateTo, $empId)
{
    $nextDate = Carbon\Carbon::parse($dateTo)->addDay()->toDateString();

    // Delete summaries
    AttendanceSummary::where('employee_id', $empId)
        ->whereBetween('summary_date', [$dateFrom, $nextDate])
        ->delete();

    // Reset is_processed
    AttendanceLog::where('employee_id', $empId)
        ->whereBetween('punch_time', [$dateFrom.' 00:00:00', $nextDate.' 23:59:59'])
        ->update(['is_processed' => false]);

    // Process
    $emp = Employee::find($empId);
    $service = new AttendanceService;
    $service->processEmployeeAttendance($emp);

    // Mark processed
    AttendanceLog::where('employee_id', $empId)
        ->whereBetween('punch_time', [$dateFrom.' 00:00:00', $nextDate.' 23:59:59'])
        ->update(['is_processed' => true]);
}

echo "Testing rebuildRangeInline for Employee 2 (May 1 to May 10)...\n";
rebuildRangeInline('2026-05-01', '2026-05-10', 2);

$summaries = AttendanceSummary::where('employee_id', 2)
    ->whereBetween('summary_date', ['2026-05-01', '2026-05-10'])
    ->orderBy('summary_date', 'asc')
    ->get();

echo "\nResulting Summaries (should have no duplicate dates):\n";
foreach ($summaries as $s) {
    echo "Date: {$s->summary_date->format('Y-m-d')} | In: {$s->check_in_time} | Out: {$s->check_out_time} | Status: {$s->status}\n";
}
