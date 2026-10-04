<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::withCount('employees')->with('branch')->orderBy('name')->get();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('departments.index', compact('departments', 'branches'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:191']);
        Department::create($request->only('name', 'description', 'branch_id'));

        return redirect()->route('departments.index')->with('success', 'Department created.');
    }

    public function update(Request $request, Department $department)
    {
        $request->validate(['name' => 'required|string|max:191']);
        $department->update($request->only('name', 'description', 'branch_id'));

        return redirect()->route('departments.index')->with('success', 'Department updated.');
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return redirect()->route('departments.index')->with('success', 'Department deleted.');
    }
}
