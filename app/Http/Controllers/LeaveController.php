<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PublicHoliday;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $leaves = LeaveRequest::with(['employee', 'leaveType', 'approver'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->employee_id, fn ($q, $e) => $q->where('employee_id', $e))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $employees = Employee::active()->orderBy('full_name')->get();
        $leaveTypes = LeaveType::where('is_active', true)->orderBy('name')->get();

        return view('leave.index', compact('leaves', 'employees', 'leaveTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
        ]);

        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);

        $holidays = PublicHoliday::whereBetween('holiday_date', [$start->toDateString(), $end->toDateString()])->pluck('holiday_date')->toArray();

        $totalDays = 0;
        $current = $start->copy();
        while ($current->lte($end)) {
            if (! $current->isWeekend() && ! in_array($current->toDateString(), $holidays)) {
                $totalDays++;
            }
            $current->addDay();
        }

        LeaveRequest::create([
            'employee_id' => $request->employee_id,
            'leave_type_id' => $request->leave_type_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'total_days' => $totalDays,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return redirect()->route('leave.index')->with('success', 'Leave request submitted.');
    }

    public function approve(LeaveRequest $leave)
    {
        $leave->update(['status' => 'approved', 'approved_by' => auth()->id()]);

        return redirect()->route('leave.index')->with('success', 'Leave approved.');
    }

    public function reject(LeaveRequest $leave, Request $request)
    {
        $leave->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'admin_remarks' => $request->remarks,
        ]);

        return redirect()->route('leave.index')->with('success', 'Leave rejected.');
    }

    public function destroy(LeaveRequest $leave)
    {
        $leave->delete();

        return redirect()->route('leave.index')->with('success', 'Leave request deleted.');
    }
}
