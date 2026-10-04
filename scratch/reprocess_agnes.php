<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->handle(Request::capture());

use App\Models\AttendanceLog;
use App\Models\AttendanceSummary;
use App\Models\Employee;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

$employeeId = 2; // Agnes
$dateFrom = '2026-04-01';
$dateTo = '2026-04-30';

$service = new AttendanceService;
$employee = Employee::find($employeeId);

if (! $employee) {
    exit('Employee not found');
}

echo "Re-processing April for {$employee->full_name}...\n";

// Reset logs for this employee in April
AttendanceLog::where('employee_id', $employeeId)
    ->whereBetween('punch_time', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
    ->update(['is_processed' => false]);

// Delete old summaries for this employee in April
AttendanceSummary::where('employee_id', $employeeId)
    ->whereBetween('summary_date', [$dateFrom, $dateTo])
    ->delete();

$currentDate = Carbon::parse($dateFrom);
$end = Carbon::parse($dateTo);

while ($currentDate <= $end) {
    $dateStr = $currentDate->toDateString();
    $summary = $service->processEmployeeDate($employee, $dateStr);

    // Manual log processing update (as done in AttendanceController)
    AttendanceLog::where('employee_id', $employeeId)
        ->whereDate('punch_time', $dateStr)
        ->update(['is_processed' => true]);

    echo "Processed {$dateStr}: ".($summary ? $summary->status : 'Skipped')."\n";
    $currentDate->addDay();
}

echo "\nDone.\n";
