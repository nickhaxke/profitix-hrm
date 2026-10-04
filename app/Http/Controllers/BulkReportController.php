<?php

namespace App\Http\Controllers;

use App\Mail\PersonalReportMail;
use App\Models\Employee;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BulkReportController extends Controller
{
    public function exportAll(Request $request, ReportService $reportService)
    {
        // Increase limits for generating massive PDFs
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        if (! class_exists(Pdf::class)) {
            return back()->with('error', 'PDF library is not installed. Please run "composer update" in your terminal first.');
        }

        // We only want active employees, or employees who have data in the requested range
        $employees = Employee::active()->orderBy('full_name')->get();
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->toDateString() : now()->startOfMonth()->toDateString();
        $dateTo = $request->date_to ? Carbon::parse($request->date_to)->toDateString() : now()->toDateString();

        $allReportsData = [];

        foreach ($employees as $emp) {
            // Build pseudo request for each employee
            $req = new Request;
            $req->merge([
                'type' => 'personal',
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'employee_id' => $emp->id,
            ]);

            $reportParams = $reportService->getReportData($req);

            // Only include employees who actually have attendance records for this period to avoid blank pages
            if (count($reportParams['data']) > 0) {
                $allReportsData[] = $reportParams;
            }
        }

        if (empty($allReportsData)) {
            return back()->with('error', 'No attendance data found for any employees in this date range.');
        }

        // We will create a new view specifically for bulk PDF that iterates over all reports
        $pdf = Pdf::loadView('reports.pdf.bulk_master', ['reports' => $allReportsData])
            ->setPaper('A4', 'landscape');

        $monthName = Carbon::parse($dateFrom)->format('F_Y');

        return $pdf->download("all_employees_reports_{$monthName}.pdf");
    }

    public function emailAll(Request $request, ReportService $reportService)
    {
        // Increase limits for generating multiple PDFs
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '600');

        if (! class_exists(Pdf::class)) {
            return back()->with('error', 'PDF library is not installed.');
        }

        $employees = Employee::active()->whereNotNull('email')->where('email', '!=', '')->get();
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->toDateString() : now()->startOfMonth()->toDateString();
        $dateTo = $request->date_to ? Carbon::parse($request->date_to)->toDateString() : now()->toDateString();

        $sentCount = 0;

        foreach ($employees as $emp) {
            $req = new Request;
            $req->merge([
                'type' => 'personal',
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'employee_id' => $emp->id,
            ]);

            $reportParams = $reportService->getReportData($req);

            if (count($reportParams['data']) > 0) {
                $pdf = Pdf::loadView('reports.pdf.master', $reportParams)
                    ->setPaper('A4', 'landscape');

                $monthName = Carbon::parse($dateFrom)->format('F_Y');
                $pdfContent = $pdf->output();
                $filename = "{$monthName}_attendance_report.pdf";

                try {
                    Mail::to($emp->email)->send(new PersonalReportMail($emp, $monthName, $pdfContent, $filename));
                    $sentCount++;
                } catch (\Exception $e) {
                    Log::error("Failed to send email to {$emp->email}: ".$e->getMessage());
                }
            }
        }

        if ($sentCount > 0) {
            return back()->with('success', "Successfully queued emails to {$sentCount} employees.");
        }

        return back()->with('error', 'No emails were sent. Make sure employees have valid email addresses and attendance data exists.');
    }
}
