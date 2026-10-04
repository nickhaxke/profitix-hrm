<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AttendanceSummary;
use App\Models\Employee;
use Illuminate\Contracts\Console\Kernel;

$summaries = AttendanceSummary::where('employee_id', 2)
    ->where('summary_date', '2026-05-04')
    ->get();

echo 'Count: '.$summaries->count()."\n";
foreach ($summaries as $s) {
    echo "ID: {$s->id} | Shift ID: {$s->shift_id} | In: {$s->check_in_time} | Out: {$s->check_out_time} | Status: {$s->status}\n";
}

// Also let's check what shifts are assigned to this employee on that date
$emp = Employee::find(2);
$shifts = $emp->shifts()
    ->wherePivot('effective_from', '<=', '2026-05-04')
    ->where(function ($q) {
        $q->whereNull('employee_shifts.effective_to')
            ->orWhere('employee_shifts.effective_to', '>=', '2026-05-04');
    })
    ->get();

echo "\nAssigned shifts count: ".$shifts->count()."\n";
foreach ($shifts as $sh) {
    echo "Shift ID: {$sh->id} | Name: {$sh->name} | Pivot ID: {$sh->pivot->id} | From: {$sh->pivot->effective_from} | To: {$sh->pivot->effective_to}\n";
}
