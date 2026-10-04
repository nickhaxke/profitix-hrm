<?php

namespace App\Services;

use App\Models\AttendanceSummary;
use App\Models\Department;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportService
{
    public function getReportData(Request $request)
    {
        $type = $request->type ?? 'daily';

        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->toDateString() : now()->startOfMonth()->toDateString();
        $dateTo = $request->date_to ? Carbon::parse($request->date_to)->toDateString() : now()->toDateString();

        $deptId = $request->department_id;
        $employeeId = $request->employee_id;
        $branchId = $request->branch_id;

        $data = [];

        if ($type === 'daily') {
            $data = AttendanceSummary::with(['employee.department', 'shift', 'employee.branch'])
                ->whereBetween('summary_date', [$dateFrom, $dateTo])
                ->when($deptId, fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $deptId)))
                ->when($branchId, fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', $branchId)))
                ->orderBy('summary_date', 'desc')->orderBy('employee_id')
                ->get();
        } elseif ($type === 'personal' && $employeeId) {
            $data = AttendanceSummary::with(['employee.department', 'shift', 'employee.branch'])
                ->where('employee_id', $employeeId)
                ->whereBetween('summary_date', [$dateFrom, $dateTo])
                ->orderBy('summary_date', 'asc')
                ->get();
        } elseif ($type === 'department') {
            $depts = Department::withCount(['employees' => fn ($q) => $q->where('status', 'active')->when($branchId, fn ($b) => $b->where('branch_id', $branchId))])
                ->when($deptId, fn ($q) => $q->where('id', $deptId))
                ->get();
            foreach ($depts as $dept) {
                $summaries = AttendanceSummary::whereHas('employee', fn ($q) => $q->where('department_id', $dept->id)->when($branchId, fn ($b) => $b->where('branch_id', $branchId)))
                    ->whereBetween('summary_date', [$dateFrom, $dateTo])->get();
                $data[] = [
                    'department' => $dept->name,
                    'employees' => $dept->employees_count,
                    'present' => $summaries->whereIn('status', ['present', 'late'])->count(),
                    'late' => $summaries->where('is_late', true)->count(),
                    'absent' => $summaries->where('status', 'absent')->count(),
                    'on_leave' => $summaries->where('status', 'leave')->count(),
                    'holiday' => $summaries->where('status', 'holiday')->count(),
                    'avg_hours' => round($summaries->avg('total_hours'), 2),
                ];
            }
        } elseif ($type === 'monthly') {
            $emps = Employee::active()->with('department', 'branch')
                ->when($deptId, fn ($q) => $q->where('department_id', $deptId))
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->orderBy('full_name')->get();

            $summaries = AttendanceSummary::whereBetween('summary_date', [$dateFrom, $dateTo])
                ->when($deptId, fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $deptId)))
                ->when($branchId, fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', $branchId)))
                ->get()
                ->groupBy('employee_id');

            foreach ($emps as $emp) {
                $empSummaries = $summaries->get($emp->id, collect());
                $data[] = [
                    'employee' => $emp->full_name,
                    'code' => $emp->employee_code,
                    'department' => $emp->department->name ?? '-',
                    'present' => $empSummaries->whereIn('status', ['present', 'late', 'half_day', 'missing_checkout'])->count(),
                    'late' => $empSummaries->where('is_late', true)->count(),
                    'absent' => $empSummaries->where('status', 'absent')->count(),
                    'on_leave' => $empSummaries->where('status', 'leave')->count(),
                    'holiday' => $empSummaries->where('status', 'holiday')->count(),
                    'total_hours' => round($empSummaries->sum('total_hours'), 2),
                    'overtime' => round($empSummaries->sum('overtime_hours'), 2),
                ];
            }
        }

        return [
            'type' => $type,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'deptId' => $deptId,
            'branchId' => $branchId,
            'employeeId' => $employeeId,
            'data' => collect($data),
        ];
    }
}
