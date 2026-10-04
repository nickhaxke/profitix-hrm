<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with('department')
            ->when($request->search, fn ($q, $s) => $q->where('full_name', 'like', "%{$s}%")->orWhere('employee_code', 'like', "%{$s}%"))
            ->when($request->department_id, fn ($q, $d) => $q->where('department_id', $d))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s), fn ($q) => $q->where('status', 'active'))
            ->orderBy('full_name');

        $employees = $query->paginate(20);
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('employees.index', compact('employees', 'departments'));
    }

    public function create()
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('employees.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_code' => 'required|unique:employees,employee_code',
            'full_name' => 'required|string|max:191',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:191',
            'department_id' => 'nullable|exists:departments,id',
            'join_date' => 'required|date',
            'biometric_id' => 'nullable|string',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string',
        ]);

        Employee::create($validated);

        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    public function edit(Employee $employee)
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('employees.edit', compact('employee', 'departments'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'employee_code' => 'required|unique:employees,employee_code,'.$employee->id,
            'full_name' => 'required|string|max:191',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:191',
            'department_id' => 'nullable|exists:departments,id',
            'join_date' => 'required|date',
            'biometric_id' => 'nullable|string',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string',
            'status' => 'nullable|in:active,inactive,terminated',
        ]);

        $employee->update($validated);

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee)
    {
        $employee->update(['status' => 'terminated']);

        return redirect()->route('employees.index')->with('success', 'Employee deactivated.');
    }
}
