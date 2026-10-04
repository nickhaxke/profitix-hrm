<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AttendanceLog;
use App\Models\AttendanceSummary;
use App\Models\Employee;
use Illuminate\Contracts\Console\Kernel;

// Find Agnes
$agnes = Employee::where('full_name', 'LIKE', '%AGNES%')->first();
if (! $agnes) {
    echo "Agnes not found in database!\n";
    // List some employees so we see who is there
    echo "List of all employees:\n";
    foreach (Employee::all() as $emp) {
        echo "ID: {$emp->id} | Name: {$emp->full_name} | Active: {$emp->is_active}\n";
    }
    exit;
}

echo "Found Employee:\n";
echo "ID: {$agnes->id}\n";
echo "Name: {$agnes->full_name}\n";
echo 'Status: '.($agnes->is_active ? 'Active' : 'Inactive')."\n";

// Count logs in May 2026
$logsCount = AttendanceLog::where('employee_id', $agnes->id)
    ->whereBetween('punch_time', ['2026-05-01 00:00:00', '2026-05-31 23:59:59'])
    ->count();
echo "Raw logs in May 2026: {$logsCount}\n";

// Count summaries in May 2026
$summariesCount = AttendanceSummary::where('employee_id', $agnes->id)
    ->whereBetween('summary_date', ['2026-05-01', '2026-05-31'])
    ->count();
echo "Summaries in May 2026: {$summariesCount}\n";
