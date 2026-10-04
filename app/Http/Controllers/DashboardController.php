<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\AttendanceSummary;
use App\Models\Device;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        if (auth()->check() && auth()->user()->role === 'superadmin') {
            return redirect()->route('admin.licenses.index');
        }

        $today = now()->toDateString();

        $stats = [
            'total_employees' => Employee::active()->count(),
            'present_today' => AttendanceSummary::where('summary_date', $today)->whereIn('status', ['present', 'late', 'missing_checkout'])->count(),
            'absent_today' => max(0, Employee::active()->count() - AttendanceSummary::where('summary_date', $today)->whereIn('status', ['present', 'late', 'missing_checkout'])->count()),
            'late_today' => AttendanceSummary::where('summary_date', $today)->where('is_late', true)->count(),
            'total_hours_today' => AttendanceSummary::where('summary_date', $today)->sum('total_hours'),
            'devices_total' => Device::where('is_active', true)->count(),
            'devices_online' => Device::where('is_active', true)->where('is_online', true)->count(),
            'last_sync' => Device::whereNotNull('last_sync')->max('last_sync'),
            'today_punches' => AttendanceLog::whereDate('punch_time', $today)->count(),
        ];

        return view('dashboard.index', compact('stats'));
    }

    public function getList(Request $request)
    {
        $type = $request->type;
        $today = now()->toDateString();

        $data = [];
        if ($type === 'present') {
            $data = AttendanceSummary::with('employee')->where('summary_date', $today)->whereIn('status', ['present', 'late', 'missing_checkout'])->get()->map(function ($item) {
                return ['name' => $item->employee->full_name ?? 'Unknown', 'detail' => 'In: '.($item->check_in_time ? Carbon::parse($item->check_in_time)->format('H:i') : '-'), 'status' => 'present'];
            });
        } elseif ($type === 'absent') {
            $presentIds = AttendanceSummary::where('summary_date', $today)->whereIn('status', ['present', 'late', 'missing_checkout'])->pluck('employee_id');
            $data = Employee::active()->whereNotIn('id', $presentIds)->get()->map(function ($item) {
                return ['name' => $item->full_name, 'detail' => 'Did not punch in', 'status' => 'absent'];
            });
        } elseif ($type === 'late') {
            $data = AttendanceSummary::with('employee')->where('summary_date', $today)->where('is_late', true)->get()->map(function ($item) {
                return ['name' => $item->employee->full_name ?? 'Unknown', 'detail' => 'In: '.($item->check_in_time ? Carbon::parse($item->check_in_time)->format('H:i') : '-'), 'status' => 'late'];
            });
        } elseif ($type === 'hours') {
            $data = AttendanceSummary::with('employee')->where('summary_date', $today)->where('total_hours', '>', 0)->get()->map(function ($item) {
                return ['name' => $item->employee->full_name ?? 'Unknown', 'detail' => number_format($item->total_hours, 1).' hours', 'status' => 'worked'];
            });
        }

        return response()->json($data);
    }
}
