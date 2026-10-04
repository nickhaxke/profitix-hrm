<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\AttendanceSummary;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Device;
use App\Models\Employee;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function logs(Request $request)
    {
        $query = AttendanceLog::with(['employee', 'device'])
            ->when($request->date_from, fn ($q, $d) => $q->whereDate('punch_time', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->whereDate('punch_time', '<=', $d))
            ->when($request->employee_id, fn ($q, $e) => $q->where('employee_id', $e))
            ->when($request->device_id, fn ($q, $d) => $q->where('device_id', $d))
            ->orderBy('punch_time', 'desc');

        $logs = $query->paginate(30);
        $employees = Employee::active()->orderBy('full_name')->get();
        $devices = Device::where('is_active', true)->orderBy('name')->get();

        return view('attendance.logs', compact('logs', 'employees', 'devices'));
    }

    public function summary(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth()->toDateString();
        $dateTo = $request->date_to ?? now()->toDateString();

        $query = AttendanceSummary::with(['employee.department', 'shift'])
            ->whereBetween('summary_date', [$dateFrom, $dateTo])
            ->when($request->employee_id, fn ($q, $e) => $q->where('employee_id', $e))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->department_id, function ($q, $d) {
                $q->whereHas('employee', fn ($eq) => $eq->where('department_id', $d));
            })
            ->orderBy('summary_date', 'desc')
            ->orderBy('employee_id');

        $summaries = $query->paginate(30);
        $employees = Employee::active()->orderBy('full_name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();

        return view('attendance.summary', compact('summaries', 'employees', 'departments', 'branches', 'dateFrom', 'dateTo'));
    }

    public function process(Request $request)
    {
        set_time_limit(300); // Kuongeza muda kwa sababu logic mpya ni nzito kidogo
        $date = $request->date ?? now()->toDateString();
        $employeeId = $request->employee_id;
        $service = new AttendanceService;

        if ($employeeId) {
            $employee = Employee::findOrFail($employeeId);
            $targetDate = Carbon::parse($date);

            // Reset a window of 3 days (yesterday, today, tomorrow) to fix overnight pairing
            $startRange = $targetDate->copy()->subDay()->startOfDay();
            $endRange = $targetDate->copy()->addDay()->endOfDay();

            // 1. Mark logs as unprocessed
            AttendanceLog::where('employee_id', $employeeId)
                ->whereBetween('punch_time', [$startRange, $endRange])
                ->update(['is_processed' => false]);

            // 2. Delete existing summaries to prevent duplicates or stale records
            AttendanceSummary::where('employee_id', $employeeId)
                ->whereBetween('summary_date', [$startRange->toDateString(), $endRange->toDateString()])
                ->delete();

            // 3. Run the pairing engine
            $service->processEmployeeAttendance($employee);

            // 4. Mark the window as processed again
            AttendanceLog::where('employee_id', $employeeId)
                ->whereBetween('punch_time', [$startRange, $endRange])
                ->update(['is_processed' => true]);

            $msg = 'Re-processed attendance for '.($employee->full_name).' around '.$date.'.';
            $stats = [
                'total_logs' => AttendanceLog::where('employee_id', $employeeId)
                    ->whereBetween('punch_time', [$startRange, $endRange])->count(),
                'processed_employees' => 1,
                'skipped_employees' => 0,
                'errors' => [],
                'status' => 'success',
            ];
        } else {
            // For the whole date (All employees)
            $result = $service->rebuildDate($date);
            $msg = 'Attendance processing completed for '.$date;
            $stats = [
                'status' => 'success',
                'processed_employees' => 'Multiple',
                'total_logs' => AttendanceLog::whereDate('punch_time', $date)->count(),
                'skipped_employees' => 0,
                'errors' => [],
                'date' => $date,
            ];
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $msg, 'stats' => $stats]);
        }

        return redirect()->back()
            ->with('success', $msg)
            ->with('processing_stats', $stats);
    }

    public function manualPunch(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'punch_time' => 'required|date',
            'punch_type' => 'required|in:in,out',
        ]);

        AttendanceLog::create([
            'employee_id' => $request->employee_id,
            'punch_time' => $request->punch_time,
            'punch_type' => $request->punch_type,
            'source' => 'manual',
        ]);

        return redirect()->route('attendance.logs')->with('success', 'Manual punch recorded.');
    }

    public function repair()
    {
        set_time_limit(600);
        $service = new AttendanceService;

        $dateFrom = now()->subDays(2)->toDateString();
        $dateTo = now()->toDateString();
        $service->rebuildRange($dateFrom, $dateTo);

        return redirect()->back()->with('success', 'Attendance system repair completed for the last 3 days.');
    }

    public function deepRepair(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        set_time_limit(1800); // Kuongeza muda zaidi (dakika 30)
        $service = new AttendanceService;

        $start = Carbon::parse($request->date_from);
        $end = Carbon::parse($request->date_to);
        $days = $start->diffInDays($end) + 1;

        if ($days > 62) {
            return redirect()->back()->with('error', 'Please select a range of maximum 2 months for performance reasons.');
        }

        $service->rebuildRange($request->date_from, $request->date_to);

        return redirect()->back()->with('success', "Deep repair completed for $days days from {$request->date_from} to {$request->date_to}.");
    }

    public function importFile(Request $request)
    {
        $request->validate([
            'device_id' => 'nullable|exists:devices,id',
            'new_device_name' => 'nullable|string|max:255',
            'file' => 'required|file|max:10240', // 10MB limit
        ]);

        if (! $request->device_id && empty($request->new_device_name)) {
            return redirect()->back()->with('error', 'Please select an existing device or enter a name to create a new one.');
        }

        $deviceId = $request->device_id;

        if (! $deviceId && ! empty($request->new_device_name)) {
            $branch = Branch::firstOrCreate(
                ['name' => $request->new_device_name],
                ['is_active' => true]
            );

            $device = Device::create([
                'name' => $request->new_device_name.' Device',
                'branch_id' => $branch->id,
                'ip_address' => '0.0.0.0',
                'port' => 4370,
                'is_active' => true,
            ]);

            $deviceId = $device->id;
        }

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $filePath = $file->getRealPath();

        if ($extension === 'mdb') {
            // MDB Files require PowerShell to extract since pdo_odbc is usually disabled
            $scriptPath = storage_path('app/temp_extract.ps1');
            $psScript = <<<PS
\$connectionString = "Provider=Microsoft.ACE.OLEDB.12.0;Data Source=$filePath;Persist Security Info=False;"
\$connection = New-Object System.Data.OleDb.OleDbConnection(\$connectionString)
try {
    \$connection.Open()
} catch {
    \$connectionString = "Provider=Microsoft.Jet.OLEDB.4.0;Data Source=$filePath;"
    \$connection = New-Object System.Data.OleDb.OleDbConnection(\$connectionString)
    \$connection.Open()
}
\$command = \$connection.CreateCommand()
\$command.CommandText = "SELECT USERINFO.Badgenumber, CHECKINOUT.CHECKTIME FROM CHECKINOUT INNER JOIN USERINFO ON CHECKINOUT.USERID = USERINFO.USERID"
\$reader = \$command.ExecuteReader()
while (\$reader.Read()) {
    Write-Output (\$reader["Badgenumber"].ToString() + "`t" + \$reader["CHECKTIME"].ToString())
}
\$connection.Close()
PS;
            file_put_contents($scriptPath, $psScript);
            $content = shell_exec("powershell -ExecutionPolicy Bypass -File \"$scriptPath\"");
            @unlink($scriptPath);
            if (! $content) {
                $content = '';
            }
        } else {
            $content = file_get_contents($filePath);
        }

        // Handle different line endings
        $content = str_replace("\r\n", "\n", $content);
        $content = str_replace("\r", "\n", $content);
        $lines = explode("\n", $content);

        $imported = 0;
        $skipped = 0;

        $employees = Employee::whereNotNull('biometric_id')->get()->keyBy('biometric_id');

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $biometricId = null;
            $punchTimeStr = null;

            // Matches: [BOM/Spaces] (ID) [Separators] (Date/Time with optional AM/PM)
            if (preg_match('/^[\x{FEFF}]?\s*(\d+)[,\t\s]+(\d{1,4}[-\/]\d{1,2}[-\/]\d{1,4}\s+\d{1,2}:\d{1,2}(?::\d{1,2})?(?:\s*(?:AM|PM|am|pm))?)/u', $line, $matches)) {
                $biometricId = $matches[1];
                $punchTimeStr = $matches[2];
            } else {
                // Some formats put Date first then Time
                if (preg_match('/(\d+).*?(\d{1,4}[-\/]\d{1,2}[-\/]\d{1,4}\s+\d{1,2}:\d{1,2}(?::\d{1,2})?(?:\s*(?:AM|PM|am|pm))?)/', $line, $matches)) {
                    $biometricId = $matches[1];
                    $punchTimeStr = $matches[2];
                }
            }

            if ($biometricId && $punchTimeStr) {
                try {
                    $punchTime = Carbon::parse($punchTimeStr);
                } catch (\Exception $e) {
                    $skipped++; // Invalid date format

                    continue;
                }

                if (! isset($employees[$biometricId])) {
                    // Auto-create missing employee
                    $device = Device::find($deviceId);
                    $maxId = Employee::max('id') ?? 0;
                    $empCode = 'EMP'.str_pad($maxId + 1, 4, '0', STR_PAD_LEFT);

                    $newEmp = Employee::create([
                        'employee_code' => $empCode,
                        'full_name' => 'Device User '.$biometricId,
                        'biometric_id' => $biometricId,
                        'branch_id' => $device ? $device->branch_id : null,
                        'join_date' => now()->toDateString(),
                        'status' => 'active',
                    ]);

                    $employees[$biometricId] = $newEmp;
                }

                $emp = $employees[$biometricId];

                $exists = AttendanceLog::where('employee_id', $emp->id)
                    ->where('punch_time', $punchTime)
                    ->exists();

                if (! $exists) {
                    AttendanceLog::create([
                        'employee_id' => $emp->id,
                        'device_id' => $deviceId,
                        'punch_time' => $punchTime,
                        'punch_type' => 'in',
                        'source' => 'import',
                        'raw_data' => $line,
                        'is_processed' => false,
                    ]);
                    $imported++;
                } else {
                    $skipped++; // Duplicate
                }
            } else {
                $skipped++; // Invalid format
            }
        }

        if ($imported == 0 && count($lines) > 0) {
            $sample = array_slice(array_filter($lines), 0, 3);
            $sampleText = implode(' \\n ', $sample);

            return redirect()->back()->with('error', 'File format not recognized. 0 logs imported. Sample from your file: '.substr(htmlspecialchars($sampleText), 0, 150));
        }

        return redirect()->back()->with('success', "File import complete! Imported: {$imported} logs. Skipped/Duplicates: {$skipped}.");
    }
}
