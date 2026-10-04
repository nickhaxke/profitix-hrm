<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Services\AttendanceService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reportService)
    {
        $reportParams = $reportService->getReportData($request);

        $type = $reportParams['type'];
        $dateFrom = $reportParams['dateFrom'];
        $dateTo = $reportParams['dateTo'];
        $deptId = $reportParams['deptId'];
        $employeeId = $reportParams['employeeId'];

        // Automatically process data for the selected date range before showing it
        $service = new AttendanceService;
        $current = Carbon::parse($dateFrom);
        $end = Carbon::parse($dateTo);

        // Cap automatic processing to max 35 days to prevent long load times
        if ($current->diffInDays($end) <= 35) {
            while ($current->lte($end)) {
                $service->rebuildDate($current->toDateString());
                $current->addDay();
            }
        }

        // Refetch the data after processing
        $reportParams = $reportService->getReportData($request);
        $data = $reportParams['data'];

        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $employees = Employee::active()->orderBy('full_name')->get();
        $branches = Branch::orderBy('name')->get();
        $branchId = $reportParams['branchId'] ?? null;

        return view('reports.index', compact('type', 'dateFrom', 'dateTo', 'departments', 'employees', 'branches', 'data', 'deptId', 'employeeId', 'branchId'));
    }

    public function process(Request $request)
    {
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->toDateString() : now()->toDateString();
        $dateTo = $request->date_to ? Carbon::parse($request->date_to)->toDateString() : now()->toDateString();

        $service = new AttendanceService;
        $current = Carbon::parse($dateFrom);
        $end = Carbon::parse($dateTo);

        $processedCount = 0;
        while ($current->lte($end)) {
            $service->rebuildDate($current->toDateString());
            $current->addDay();
            $processedCount++;
        }

        return back()->with('success', "Processed attendance for $processedCount days.");
    }
}
