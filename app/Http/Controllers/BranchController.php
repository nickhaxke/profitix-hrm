<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::withCount(['departments', 'employees'])->orderBy('name')->get();

        return view('branches.index', compact('branches'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:191']);
        Branch::create($request->only('name', 'address', 'phone'));

        return redirect()->route('branches.index')->with('success', 'Branch created.');
    }

    public function update(Request $request, Branch $branch)
    {
        $request->validate(['name' => 'required|string|max:191']);
        $branch->update($request->only('name', 'address', 'phone', 'is_active'));

        return redirect()->route('branches.index')->with('success', 'Branch updated.');
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();

        return redirect()->route('branches.index')->with('success', 'Branch deleted.');
    }
}
