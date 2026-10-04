<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = Shift::orderBy('is_default', 'desc')->orderBy('name')->get();

        return view('shifts.index', compact('shifts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'start_time' => 'required',
            'end_time' => 'required',
            'grace_minutes' => 'required|integer|min:0|max:120',
            'required_hours' => 'required|numeric|min:1|max:24',
        ]);

        Shift::create($request->only('name', 'start_time', 'end_time', 'grace_minutes', 'late_threshold_minutes', 'required_hours', 'half_day_hours', 'crosses_midnight', 'is_default'));

        return redirect()->route('shifts.index')->with('success', 'Shift created.');
    }

    public function update(Request $request, Shift $shift)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        $shift->update($request->only('name', 'start_time', 'end_time', 'grace_minutes', 'late_threshold_minutes', 'required_hours', 'half_day_hours', 'crosses_midnight', 'is_default'));

        return redirect()->route('shifts.index')->with('success', 'Shift updated.');
    }

    public function destroy(Shift $shift)
    {
        $shift->delete();

        return redirect()->route('shifts.index')->with('success', 'Shift deleted.');
    }

    public function assignments(Request $request)
    {
        $shifts = Shift::orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $employees = Employee::active()
            ->with('shifts')
            ->when($request->department_id, fn ($q, $d) => $q->where('department_id', $d))
            ->when($request->search, fn ($q, $s) => $q->where('full_name', 'like', "%{$s}%"))
            ->orderBy('full_name')
            ->get();

        return view('shifts.assignments', compact('shifts', 'departments', 'employees'));
    }

    public function storeAssignment(Request $request)
    {
        $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'shift_ids' => 'required|array',
            'shift_ids.*' => 'exists:shifts,id',
            'effective_from' => 'required|date',
        ]);

        foreach ($request->employee_ids as $empId) {
            $employee = Employee::findOrFail($empId);
            // Attach shifts with effective dates
            foreach ($request->shift_ids as $shiftId) {
                // Avoid duplicates
                $employee->shifts()->syncWithoutDetaching([
                    $shiftId => [
                        'effective_from' => $request->effective_from,
                        'effective_to' => $request->effective_to,
                    ],
                ]);
            }
        }

        return redirect()->back()->with('success', 'Shifts assigned successfully.');
    }

    public function destroyAssignment(Employee $employee, Shift $shift)
    {
        $employee->shifts()->detach($shift->id);

        return redirect()->back()->with('success', 'Assignment removed.');
    }
}
