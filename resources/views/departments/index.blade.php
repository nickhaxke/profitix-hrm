@extends('layouts.app')
@section('title', 'Departments')
@section('content')
<div class="flex justify-between mb-6">
    <h3 class="text-lg font-semibold">Manage Departments</h3>
    <button onclick="document.getElementById('addDeptModal').classList.remove('hidden')" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium"><i class="fas fa-plus mr-1"></i> Add Department</button>
</div>
<div class="bg-white rounded-xl border shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50"><tr>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Name</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Branch</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Employees</th>
            <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
        </tr></thead>
        <tbody class="divide-y">
        @forelse($departments as $d)
        <tr class="hover:bg-gray-50">
            <td class="px-5 py-3 text-sm font-semibold">{{ $d->name }}</td>
            <td class="px-5 py-3 text-sm text-gray-500">{{ $d->branch->name ?? '—' }}</td>
            <td class="px-5 py-3 text-sm">{{ $d->employees_count }}</td>
            <td class="px-5 py-3 text-right">
                <form method="POST" action="{{ route('departments.destroy', $d) }}" class="inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="px-2 py-1 bg-red-50 text-red-600 rounded text-xs"><i class="fas fa-trash"></i></button></form>
            </td>
        </tr>
        @empty
        <tr><td colspan="4" class="px-5 py-12 text-center text-gray-400">No departments.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div id="addDeptModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-2xl">
        <h3 class="text-lg font-semibold mb-4">Add Department</h3>
        <form method="POST" action="{{ route('departments.store') }}">@csrf
            <div class="mb-3"><label class="block text-sm font-medium mb-1">Name *</label><input type="text" name="name" required class="w-full px-3 py-2 border rounded-lg text-sm"></div>
            <div class="mb-3"><label class="block text-sm font-medium mb-1">Description</label><input type="text" name="description" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
            <div class="mb-4"><label class="block text-sm font-medium mb-1">Branch</label><select name="branch_id" class="w-full px-3 py-2 border rounded-lg text-sm"><option value="">Select</option>@foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('addDeptModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 rounded-lg text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection
