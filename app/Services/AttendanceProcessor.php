<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceProcessor
{
    /**
     * Process a single attendance log to determine if it is IN, OUT, or IGNORED.
     */
    public function process(AttendanceLog $log): void
    {
        // Ignore if no employee is mapped
        if (! $log->employee_id) {
            $log->update(['is_ignored' => true, 'punch_type' => 'unknown']);

            return;
        }

        $employee = Employee::with('shifts')->find($log->employee_id);
        if (! $employee) {
            $log->update(['is_ignored' => true, 'punch_type' => 'unknown']);

            return;
        }

        $punchTime = Carbon::parse($log->punch_time);
        $date = $punchTime->format('Y-m-d');
        $time = $punchTime->format('H:i:s');

        // Find if they already have an IN punch for today
        $firstInLog = AttendanceLog::where('employee_id', $employee->id)
            ->whereDate('punch_time', $date)
            ->where('punch_type', 'in')
            ->where('is_ignored', false)
            ->orderBy('punch_time', 'asc')
            ->first();

        if (! $firstInLog) {
            // First valid punch of the day -> CHECK-IN
            $log->update(['punch_type' => 'in', 'is_ignored' => false]);

            return;
        }

        // We already have an IN punch. Compare time with earliest checkout time
        $earliestCheckoutTimeStr = $this->getEarliestCheckoutTime($employee, $date);

        // Check if there is already an OUT punch for today
        $outLog = AttendanceLog::where('employee_id', $employee->id)
            ->whereDate('punch_time', $date)
            ->where('punch_type', 'out')
            ->where('is_ignored', false)
            ->first();

        if ($outLog) {
            // Already checked out -> IGNORE
            $log->update(['punch_type' => 'unknown', 'is_ignored' => true]);

            return;
        }

        if ($time >= $earliestCheckoutTimeStr) {
            // First valid punch at/after earliest checkout -> CHECK-OUT
            $log->update(['punch_type' => 'out', 'is_ignored' => false]);
        } else {
            // Punch before earliest checkout -> IGNORE
            $log->update(['punch_type' => 'unknown', 'is_ignored' => true]);
        }
    }

    private function getEarliestCheckoutTime(Employee $employee, string $date): string
    {
        $shift = $employee->shifts()->first();

        if ($shift && $shift->earliest_checkout_time) {
            return Carbon::parse($shift->earliest_checkout_time)->format('H:i:s');
        }

        $fallback = Setting::where('key', 'earliest_checkout_time')->value('value');
        if ($fallback) {
            return Carbon::parse($fallback)->format('H:i:s');
        }

        return '16:00:00';
    }
}
