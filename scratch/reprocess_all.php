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

$month = '2026-04';
$dateFrom = $month.'-01';
$dateTo = Carbon::parse($dateFrom)->endOfMonth()->toDateString();

$service = new AttendanceService;
$employees = Employee::active()->get();

echo "Re-processing all active employees for {$month}...\n";

// Reset ALL logs for this month
AttendanceLog::whereBetween('punch_time', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
    ->update(['is_processed' => false]);

// Delete ALL summaries for this month
AttendanceSummary::whereBetween('summary_date', [$dateFrom, $dateTo])
    ->delete();

foreach ($employees as $employee) {
    echo "Processing {$employee->full_name}... ";
    $currentDate = Carbon::parse($dateFrom);
    $end = Carbon::parse($dateTo);

    while ($currentDate <= $end) {
        $dateStr = $currentDate->toDateString();
        $service->processEmployeeDate($employee, $dateStr);

        // Mark logs as processed for this date
        AttendanceLog::where('employee_id', $employee->id)
            ->whereDate('punch_time', $dateStr)
            ->update(['is_processed' => true]);

        $currentDate->addDay();
    }
    echo "Done.\n";
}

echo "\nGlobal re-processing complete.\n";
