@extends('layouts.app')
@section('title', 'Employees')
@section('content')
<div class="flex items-center justify-between mb-6">
    <form class="flex gap-3" method="GET">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..." class="px-4 py-2 border rounded-lg text-sm w-64">
        <select name="department_id" class="px-4 py-2 border rounded-lg text-sm">
            <option value="">All Departments</option>
            @foreach($departments as $d)<option value="{{ $d->id }}" {{ request('department_id')==$d->id?'selected':'' }}>{{ $d->name }}</option>@endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-gray-100 rounded-lg text-sm"><i class="fas fa-search"></i></button>
    </form>
    <div class="flex gap-2">
        <a href="{{ route('exports.employees') }}" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700"><i class="fas fa-file-csv mr-1"></i> Export</a>
        <a href="{{ route('employees.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700"><i class="fas fa-user-plus mr-1"></i> Add Employee</a>
    </div>
</div>
<div class="bg-white rounded-xl border shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50"><tr>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Code</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Name</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Department</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Phone</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
            <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
        </tr></thead>
        <tbody class="divide-y">
        @forelse($employees as $e)
        <tr class="hover:bg-gray-50">
            <td class="px-5 py-3 text-sm font-mono">{{ $e->employee_code }}</td>
            <td class="px-5 py-3 text-sm font-semibold">{{ $e->full_name }}</td>
            <td class="px-5 py-3 text-sm text-gray-600">{{ $e->department->name ?? '—' }}</td>
            <td class="px-5 py-3 text-sm">{{ $e->phone ?? '—' }}</td>
            <td class="px-5 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $e->status==='active'?'bg-green-100 text-green-800':'bg-red-100 text-red-800' }}">{{ ucfirst($e->status) }}</span></td>
            <td class="px-5 py-3 text-right">
                <a href="{{ route('employees.edit', $e) }}" class="px-2 py-1 bg-gray-100 rounded text-xs"><i class="fas fa-edit"></i></a>
                <form method="POST" action="{{ route('employees.destroy', $e) }}" class="inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="px-2 py-1 bg-red-50 text-red-600 rounded text-xs"><i class="fas fa-trash"></i></button></form>
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-5 py-12 text-center text-gray-400">No employees found.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="px-5 py-3 border-t bg-gray-50">{{ $employees->links() }}</div>
</div>
@endsection
