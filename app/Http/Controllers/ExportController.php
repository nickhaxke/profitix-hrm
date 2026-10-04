<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\ExcelExportService;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function export(Request $request, ReportService $reportService)
    {
        $reportParams = $reportService->getReportData($request);
        $format = $request->format ?? 'csv';

        $filenamePrefix = "report_{$reportParams['type']}_{$reportParams['dateFrom']}_to_{$reportParams['dateTo']}";
        if ($reportParams['type'] === 'personal' && ! empty($reportParams['employeeId'])) {
            $employee = Employee::find($reportParams['employeeId']);
            if ($employee) {
                $empName = strtolower(str_replace(' ', '_', $employee->full_name));
                $monthName = Carbon::parse($reportParams['dateFrom'])->format('F_Y');
                $filenamePrefix = "{$empName}_{$monthName}_report";
            }
        }

        if ($format === 'pdf') {
            if (! class_exists(Pdf::class)) {
                return back()->with('error', 'PDF library is not installed. Please run "composer update" in your terminal first.');
            }
            $pdf = Pdf::loadView('reports.pdf.master', $reportParams)
                ->setPaper('A4', 'landscape');

            return $pdf->download("{$filenamePrefix}.pdf");
        }

        if ($format === 'excel_split') {
            $excelService = new ExcelExportService;
            $sheets = [];

            $start = Carbon::parse($request->date_from ?? now()->startOfMonth());
            $end = Carbon::parse($request->date_to ?? now());

            // Limit to 6 months to prevent memory issues
            if ($start->diffInMonths($end) > 6) {
                $start = $end->copy()->subMonths(6)->startOfMonth();
            }

            $currentMonth = $start->copy()->startOfMonth();
            while ($currentMonth <= $end) {
                $monthEnd = $currentMonth->copy()->endOfMonth();
                if ($monthEnd > $end) {
                    $monthEnd = $end->copy();
                }

                // Fake request for this specific month
                $monthReq = new Request;
                $monthReq->merge([
                    'type' => $request->type,
                    'date_from' => $currentMonth->toDateString(),
                    'date_to' => $monthEnd->toDateString(),
                    'branch_id' => $request->branch_id,
                    'department_id' => $request->department_id,
                    'employee_id' => $request->employee_id,
                ]);

                $monthData = $reportService->getReportData($monthReq);
                $type = $monthData['type'];
                $data = $monthData['data'];

                $sheetName = $currentMonth->format('M Y');
                $headings = [];
                $rows = [];

                if ($type === 'daily' || $type === 'personal') {
                    $headings = ['Date', 'Employee', 'Department', 'In', 'Out', 'Hours', 'Status'];
                    foreach ($data as $r) {
                        $rows[] = [
                            $r->summary_date->format('Y-m-d'),
                            $r->employee->full_name ?? '',
                            $r->employee->department->name ?? '-',
                            $r->check_in_time ?? '-',
                            $r->check_out_time ?? '-',
                            number_format($r->total_hours, 1),
                            ucfirst(str_replace('_', ' ', $r->status)),
                        ];
                    }
                } elseif ($type === 'department') {
                    $headings = ['Department', 'Employees', 'Present', 'Late', 'Absent', 'On Leave', 'Holiday', 'Avg Hours'];
                    foreach ($data as $r) {
                        $rows[] = [$r['department'], $r['employees'], $r['present'], $r['late'], $r['absent'], $r['on_leave'], $r['holiday'], $r['avg_hours']];
                    }
                } elseif ($type === 'monthly') {
                    $headings = ['Employee', 'Code', 'Department', 'Present', 'Late', 'Absent', 'On Leave', 'Holiday', 'Total Hrs', 'Overtime'];
                    foreach ($data as $r) {
                        $rows[] = [$r['employee'], $r['code'], $r['department'], $r['present'], $r['late'], $r['absent'], $r['on_leave'], $r['holiday'], $r['total_hours'], $r['overtime']];
                    }
                }

                $sheets[] = [
                    'name' => $sheetName,
                    'headings' => $headings,
                    'rows' => $rows,
                ];

                $currentMonth->addMonth()->startOfMonth();
            }

            return $excelService->downloadMultipleSheets("{$filenamePrefix}.xls", $sheets);
        }

        // CSV export
        $data = $reportParams['data'];
        $type = $reportParams['type'];
        $filename = "{$filenamePrefix}.csv";

        return response()->streamDownload(function () use ($data, $type) {
            $out = fopen('php://output', 'w');

            // Output BOM for Excel UTF-8 compatibility
            fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));

            if ($type === 'daily' || $type === 'personal') {
                fputcsv($out, ['Date', 'Employee', 'Department', 'In', 'Out', 'Hours', 'Status']);
                foreach ($data as $r) {
                    fputcsv($out, [
                        $r->summary_date->format('Y-m-d'),
                        $r->employee->full_name ?? '',
                        $r->employee->department->name ?? '-',
                        $r->check_in_time ?? '-',
                        $r->check_out_time ?? '-',
                        number_format($r->total_hours, 1),
                        ucfirst(str_replace('_', ' ', $r->status)),
                    ]);
                }
            } elseif ($type === 'department') {
                fputcsv($out, ['Department', 'Employees', 'Present', 'Late', 'Absent', 'On Leave', 'Holiday', 'Avg Hours']);
                foreach ($data as $r) {
                    fputcsv($out, [$r['department'], $r['employees'], $r['present'], $r['late'], $r['absent'], $r['on_leave'], $r['holiday'], $r['avg_hours']]);
                }
            } elseif ($type === 'monthly') {
                fputcsv($out, ['Employee', 'Code', 'Department', 'Present', 'Late', 'Absent', 'On Leave', 'Holiday', 'Total Hrs', 'Overtime']);
                foreach ($data as $r) {
                    fputcsv($out, [$r['employee'], $r['code'], $r['department'], $r['present'], $r['late'], $r['absent'], $r['on_leave'], $r['holiday'], $r['total_hours'], $r['overtime']]);
                }
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function employeesCsv()
    {
        $employees = Employee::with('department')->orderBy('full_name')->get();

        return response()->streamDownload(function () use ($employees) {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Code', 'Name', 'Email', 'Phone', 'Department', 'Position', 'Join Date', 'Biometric ID', 'Gender', 'Status']);

            foreach ($employees as $e) {
                fputcsv($out, [
                    $e->employee_code, $e->full_name, $e->email, $e->phone,
                    $e->department->name ?? '', $e->position,
                    $e->join_date?->format('Y-m-d'), $e->biometric_id,
                    $e->gender, $e->status,
                ]);
            }
            fclose($out);
        }, 'employees.csv', ['Content-Type' => 'text/csv']);
    }
}
