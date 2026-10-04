@extends('layouts.app')
@section('title', 'Branches')
@section('content')
<div class="flex justify-between mb-6">
    <h3 class="text-lg font-semibold">Manage Branches</h3>
    <button onclick="document.getElementById('addBranchModal').classList.remove('hidden')" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium"><i class="fas fa-plus mr-1"></i> Add Branch</button>
</div>
<div class="bg-white rounded-xl border shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50"><tr>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Name</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Location / Address</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Departments</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Employees</th>
            <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
        </tr></thead>
        <tbody class="divide-y">
        @forelse($branches as $b)
        <tr class="hover:bg-gray-50">
            <td class="px-5 py-3 text-sm font-semibold">{{ $b->name }}</td>
            <td class="px-5 py-3 text-sm text-gray-500">{{ $b->address ?? '—' }}</td>
            <td class="px-5 py-3 text-sm">{{ $b->departments_count }}</td>
            <td class="px-5 py-3 text-sm">{{ $b->employees_count }}</td>
            <td class="px-5 py-3 text-right">
                <form method="POST" action="{{ route('branches.destroy', $b) }}" class="inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="px-2 py-1 bg-red-50 text-red-600 rounded text-xs"><i class="fas fa-trash"></i></button></form>
            </td>
        </tr>
        @empty
        <tr><td colspan="5" class="px-5 py-12 text-center text-gray-400">No branches added.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div id="addBranchModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-2xl">
        <h3 class="text-lg font-semibold mb-4">Add Branch</h3>
        <form method="POST" action="{{ route('branches.store') }}">@csrf
            <div class="mb-3"><label class="block text-sm font-medium mb-1">Name *</label><input type="text" name="name" required class="w-full px-3 py-2 border rounded-lg text-sm"></div>
            <div class="mb-3"><label class="block text-sm font-medium mb-1">Address / Location</label><input type="text" name="address" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
            <div class="mb-4"><label class="block text-sm font-medium mb-1">Phone</label><input type="text" name="phone" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('addBranchModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 rounded-lg text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection
