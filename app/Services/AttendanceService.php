<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\AttendanceSummary;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PublicHoliday;
use App\Models\Shift;
use Carbon\Carbon;

class AttendanceService
{
    /**
     * Process all unprocessed logs across any dates.
     */
    public function processUnprocessedLogs(): array
    {
        $employeeIds = AttendanceLog::where('is_processed', false)
            ->distinct()
            ->pluck('employee_id');

        $overallStats = [
            'employees_processed' => count($employeeIds),
            'total_summaries' => 0,
        ];

        foreach ($employeeIds as $empId) {
            $employee = Employee::find($empId);
            if ($employee) {
                $this->processEmployeeAttendance($employee);
                $overallStats['total_summaries']++;
            }
        }

        // Mark all logs as processed after the sweep
        AttendanceLog::where('is_processed', false)->update(['is_processed' => true]);

        return $overallStats;
    }

    /**
     * Process a single employee's attendance using a sequence-based pairing engine.
     */
    public function processEmployeeAttendance(Employee $employee): void
    {
        $unprocessedLogs = AttendanceLog::where('employee_id', $employee->id)
            ->where('is_processed', false)
            ->orderBy('punch_time', 'asc')
            ->get();

        if ($unprocessedLogs->isEmpty()) {
            return;
        }

        $sessions = [];
        $currentIn = null;
        $lastPunchTime = null;

        foreach ($unprocessedLogs as $log) {
            $punchTime = Carbon::parse($log->punch_time);

            // Ignore duplicate/double punches within 5 minutes (300 seconds) of the previous punch
            if ($lastPunchTime && abs($punchTime->diffInSeconds($lastPunchTime)) < 300) {
                continue;
            }

            $lastPunchTime = $punchTime;

            if ($currentIn) {
                // Important: calculate diff based on $currentIn to $punchTime
                $hoursDiff = $currentIn->diffInHours($punchTime);
                $isExplicitIn = in_array(strtolower($log->punch_type), ['check_in', 'c/in', 'in', '0']);

                // SHeria: Kwa Hospitali, shift zinaweza fika masaa 18-20. Tunaweka 22 kama kikomo.
                if ($isExplicitIn || $hoursDiff >= 22) {
                    $sessions[] = ['in' => $currentIn, 'out' => null];
                    $currentIn = $punchTime;
                } else {
                    $sessions[] = ['in' => $currentIn, 'out' => $punchTime];
                    $currentIn = null;
                }
            } else {
                $currentIn = $punchTime;
            }
        }

        if ($currentIn) {
            $sessions[] = ['in' => $currentIn, 'out' => null];
        }

        $grouped = [];
        foreach ($sessions as $session) {
            $date = $session['in']->toDateString();
            $shift = $this->getShiftForTime($employee, $session['in']);
            $key = $date.'_'.$shift->id;

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'employee_id' => $employee->id,
                    'date' => $date,
                    'shift' => $shift,
                    'first_in' => $session['in'],
                    'last_out' => null,
                    'total_minutes' => 0,
                    'has_missing_checkout' => false,
                ];
            }

            if ($session['out']) {
                $duration = $session['in']->diffInMinutes($session['out']);
                // Safety check: max 22 hours per paired session
                if ($duration > 0 && $duration <= 1320) {
                    $grouped[$key]['total_minutes'] += $duration;
                    if (! $grouped[$key]['last_out'] || $session['out']->gt($grouped[$key]['last_out'])) {
                        $grouped[$key]['last_out'] = $session['out'];
                    }
                }
            } else {
                $grouped[$key]['has_missing_checkout'] = true;
            }
        }

        foreach ($grouped as $data) {
            $totalHours = round($data['total_minutes'] / 60, 2);
            $shift = $data['shift'];
            $overtimeHours = $this->calculateOvertime($shift, $totalHours);

            $shiftStart = Carbon::parse($data['date'].' '.$shift->start_time);
            $graceEnd = $shiftStart->copy()->addMinutes((int) $shift->grace_minutes);
            $isLate = $data['first_in']->gt($graceEnd);
            $lateMinutes = $isLate ? (int) $graceEnd->diffInMinutes($data['first_in']) : 0;

            $status = 'present';
            // Status missing_checkout should only apply if there are 0 hours and at least one session had no OUT
            if ($totalHours == 0) {
                $status = 'missing_checkout';
            } elseif ($totalHours < ($shift->half_day_hours ?? 4)) {
                $status = 'half_day';
            } elseif ($isLate) {
                $status = 'late';
            }

            $shiftEnd = Carbon::parse($data['date'].' '.$shift->end_time);
            // If shift crosses midnight, end time is on the next day
            if (Carbon::parse($shift->end_time)->lt(Carbon::parse($shift->start_time))) {
                $shiftEnd->addDay();
            }

            $isEarlyOut = ($data['last_out'] && $data['last_out']->lt($shiftEnd));
            $earlyOutMinutes = $isEarlyOut ? (int) $data['last_out']->diffInMinutes($shiftEnd) : 0;

            AttendanceSummary::updateOrCreate(
                ['employee_id' => $employee->id, 'summary_date' => $data['date'], 'shift_id' => $shift->id],
                [
                    'check_in_time' => $data['first_in']->format('H:i:s'),
                    'check_out_time' => $data['last_out'] ? $data['last_out']->format('H:i:s') : null,
                    'total_hours' => $totalHours,
                    'overtime_hours' => $overtimeHours,
                    'late_minutes' => $lateMinutes,
                    'early_out_minutes' => $earlyOutMinutes,
                    'is_late' => $isLate,
                    'status' => $status,
                    'is_overnight' => $data['last_out'] && $data['first_in']->format('Y-m-d') !== $data['last_out']->format('Y-m-d'),
                ]
            );
        }
    }

    private function getShiftForTime(Employee $employee, Carbon $punchTime)
    {
        $date = $punchTime->toDateString();
        $shifts = $this->getEmployeeAssignedShifts($employee, $date);

        if ($shifts->count() === 1) {
            return $shifts->first();
        }

        // If multiple shifts, find the one where the punch time fits best
        $bestShift = $shifts->first();
        $minDiff = 9999;

        foreach ($shifts as $shift) {
            $shiftStart = Carbon::parse($date.' '.$shift->start_time);
            $diff = abs($punchTime->diffInMinutes($shiftStart));
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $bestShift = $shift;
            }
        }

        return $bestShift;
    }

    private function getEmployeeAssignedShifts(Employee $employee, string $date)
    {
        $assignedShifts = $employee->shifts()
            ->wherePivot('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('employee_shifts.effective_to')
                    ->orWhere('employee_shifts.effective_to', '>=', $date);
            })
            ->get();

        if ($assignedShifts->isEmpty()) {
            return collect([$this->getDefaultShift()]);
        }

        return $assignedShifts;
    }

    private function getDefaultShift(): Shift
    {
        return Shift::where('is_default', true)->first() ?? Shift::firstOrCreate(
            ['is_default' => true],
            [
                'name' => 'Standard Shift',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'grace_minutes' => 15,
                'required_hours' => 8,
                'half_day_hours' => 4,
                'is_active' => true,
            ]
        );
    }

    private function calculateOvertime(?Shift $shift, float $totalHours): float
    {
        if (! $shift || $totalHours <= 0) {
            return 0;
        }
        $scheduled = $shift->required_hours ?? 8;
        if ($totalHours > $scheduled) {
            return round($totalHours - $scheduled, 2);
        }

        return 0;
    }

    public function rebuildDate(string $date): array
    {
        return $this->rebuildRange($date, $date);
    }

    public function rebuildRange(string $dateFrom, string $dateTo): array
    {
        $targetDate = Carbon::parse($dateFrom);
        $endDate = Carbon::parse($dateTo);
        $nextDate = $endDate->copy()->addDay()->toDateString();

        // Find all active employees, because absent ones have no logs
        $employees = Employee::active()->get();

        // Delete all summaries in the range
        AttendanceSummary::whereBetween('summary_date', [$dateFrom, $dateTo])->delete();

        // Reset is_processed to false for logs in the range
        AttendanceLog::whereBetween('punch_time', [$dateFrom.' 00:00:00', $nextDate.' 23:59:59'])
            ->update(['is_processed' => false]);

        foreach ($employees as $emp) {
            $this->processEmployeeAttendance($emp);
            // Mark logs in range as processed
            AttendanceLog::where('employee_id', $emp->id)
                ->whereBetween('punch_time', [$dateFrom.' 00:00:00', $nextDate.' 23:59:59'])
                ->update(['is_processed' => true]);

            $this->fillMissingSummaries($emp, $dateFrom, $dateTo);
        }

        return ['status' => 'success', 'from' => $dateFrom, 'to' => $dateTo];
    }

    private function fillMissingSummaries(Employee $employee, string $dateFrom, string $dateTo): void
    {
        $holidays = PublicHoliday::whereBetween('holiday_date', [$dateFrom, $dateTo])
            ->get()
            ->map(fn ($h) => $h->holiday_date->toDateString())
            ->toArray();
        $leaves = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where(function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('start_date', [$dateFrom, $dateTo])
                    ->orWhereBetween('end_date', [$dateFrom, $dateTo])
                    ->orWhere(function ($q2) use ($dateFrom, $dateTo) {
                        $q2->where('start_date', '<=', $dateFrom)->where('end_date', '>=', $dateTo);
                    });
            })->get();

        $current = Carbon::parse($dateFrom);
        $end = Carbon::parse($dateTo);

        $shift = $this->getEmployeeAssignedShifts($employee, $current->toDateString())->first();
        if (! $shift) {
            $shift = $this->getDefaultShift();
        }

        while ($current->lte($end)) {
            $dateStr = $current->toDateString();

            // Check if summary already exists (meaning they punched in)
            $exists = AttendanceSummary::where('employee_id', $employee->id)->where('summary_date', $dateStr)->exists();

            if (! $exists) {
                $status = 'absent';

                if ($current->isWeekend()) {
                    $status = 'off_day';
                } elseif (in_array($dateStr, $holidays)) {
                    $status = 'holiday';
                } else {
                    // Check if on leave
                    foreach ($leaves as $leave) {
                        if ($current->between($leave->start_date, $leave->end_date)) {
                            $status = 'leave';
                            break;
                        }
                    }
                }

                AttendanceSummary::create([
                    'employee_id' => $employee->id,
                    'summary_date' => $dateStr,
                    'shift_id' => $shift->id,
                    'status' => $status,
                    'total_hours' => 0,
                    'is_late' => false,
                ]);
            }
            $current->addDay();
        }
    }

    /**
     * Helper to process logs for a specific date (used by API)
     */
    public function processDate(string $date): void
    {
        // We just call the main sweep which handles all unprocessed logs
        $this->processUnprocessedLogs();
    }
}
