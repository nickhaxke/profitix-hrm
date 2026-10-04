@extends('layouts.app')
@section('title', 'Leave Management')
@section('content')
<div class="flex justify-between mb-6">
    <form class="flex gap-3" method="GET">
        <select name="status" class="px-3 py-2 border rounded-lg text-sm"><option value="">All Status</option><option value="pending" {{ request('status')=='pending'?'selected':'' }}>Pending</option><option value="approved" {{ request('status')=='approved'?'selected':'' }}>Approved</option><option value="rejected" {{ request('status')=='rejected'?'selected':'' }}>Rejected</option></select>
        <button type="submit" class="px-4 py-2 bg-gray-100 rounded-lg text-sm"><i class="fas fa-filter"></i></button>
    </form>
    <button onclick="document.getElementById('leaveModal').classList.remove('hidden')" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium"><i class="fas fa-plus mr-1"></i> New Request</button>
</div>
@php $lc = ['pending'=>'bg-amber-100 text-amber-800','approved'=>'bg-green-100 text-green-800','rejected'=>'bg-red-100 text-red-800']; @endphp
<div class="bg-white rounded-xl border shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50"><tr>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Employee</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Type</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">From</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">To</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Days</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
        </tr></thead>
        <tbody class="divide-y">
        @forelse($leaves as $l)
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm font-medium">{{ $l->employee->full_name ?? 'Deleted Employee' }}</td>
            <td class="px-4 py-3 text-sm">{{ $l->leaveType->name ?? 'Deleted Type' }}</td>
            <td class="px-4 py-3 text-sm font-mono">{{ $l->start_date->format('Y-m-d') }}</td>
            <td class="px-4 py-3 text-sm font-mono">{{ $l->end_date->format('Y-m-d') }}</td>
            <td class="px-4 py-3 text-sm font-semibold">{{ $l->total_days }}</td>
            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $lc[$l->status] ?? '' }}">{{ ucfirst($l->status) }}</span></td>
            <td class="px-4 py-3 text-right space-x-1">
                @if($l->status === 'pending')
                <form method="POST" action="{{ route('leave.approve', $l) }}" class="inline">@csrf<button class="px-2 py-1 bg-green-50 text-green-600 rounded text-xs"><i class="fas fa-check"></i></button></form>
                <form method="POST" action="{{ route('leave.reject', $l) }}" class="inline">@csrf<button class="px-2 py-1 bg-red-50 text-red-600 rounded text-xs"><i class="fas fa-times"></i></button></form>
                @endif
                <form method="POST" action="{{ route('leave.destroy', $l) }}" class="inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs"><i class="fas fa-trash"></i></button></form>
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-12 text-center text-gray-400">No leave requests.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t bg-gray-50">{{ $leaves->appends(request()->query())->links() }}</div>
</div>
{{-- New Leave Modal --}}
<div id="leaveModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-2xl">
        <div class="flex justify-between mb-4"><h3 class="text-lg font-semibold">New Leave Request</h3><button onclick="document.getElementById('leaveModal').classList.add('hidden')" class="text-gray-400">&times;</button></div>
        <form method="POST" action="{{ route('leave.store') }}">@csrf
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2"><label class="block text-sm font-medium mb-1">Employee *</label><select name="employee_id" required class="w-full px-3 py-2 border rounded-lg text-sm">@foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->full_name }}</option>@endforeach</select></div>
                <div class="col-span-2"><label class="block text-sm font-medium mb-1">Leave Type *</label><select name="leave_type_id" required class="w-full px-3 py-2 border rounded-lg text-sm">@foreach($leaveTypes as $t)<option value="{{ $t->id }}">{{ $t->name }} ({{ $t->max_days_per_year }} days/yr)</option>@endforeach</select></div>
                <div><label class="block text-sm font-medium mb-1">Start Date *</label><input type="date" name="start_date" required class="w-full px-3 py-2 border rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium mb-1">End Date *</label><input type="date" name="end_date" required class="w-full px-3 py-2 border rounded-lg text-sm"></div>
                <div class="col-span-2"><label class="block text-sm font-medium mb-1">Reason</label><textarea name="reason" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm"></textarea></div>
            </div>
            <div class="mt-4 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('leaveModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 rounded-lg text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium">Submit</button>
            </div>
        </form>
    </div>
</div>
@endsection
