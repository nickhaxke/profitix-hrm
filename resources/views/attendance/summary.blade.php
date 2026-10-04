@extends('layouts.app')
@section('title', 'Daily Summary')
@section('content')
<div class="flex flex-wrap items-end gap-3 mb-6">
    <form class="flex flex-wrap gap-3 items-end" method="GET">
        <div><label class="block text-xs font-medium text-gray-500 mb-1">From</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="px-3 py-2 border rounded-lg text-sm"></div>
        <div><label class="block text-xs font-medium text-gray-500 mb-1">To</label><input type="date" name="date_to" value="{{ $dateTo }}" class="px-3 py-2 border rounded-lg text-sm"></div>
        <div><label class="block text-xs font-medium text-gray-500 mb-1">Employee</label><select name="employee_id" class="px-3 py-2 border rounded-lg text-sm"><option value="">All</option>@foreach($employees as $e)<option value="{{ $e->id }}" {{ request('employee_id')==$e->id?'selected':'' }}>{{ $e->full_name }}</option>@endforeach</select></div>
        <div><label class="block text-xs font-medium text-gray-500 mb-1">Branch</label><select name="branch_id" class="px-3 py-2 border rounded-lg text-sm"><option value="">All</option>@foreach($branches as $b)<option value="{{ $b->id }}" {{ request('branch_id')==$b->id?'selected':'' }}>{{ $b->name }}</option>@endforeach</select></div>
        <div><label class="block text-xs font-medium text-gray-500 mb-1">Department</label><select name="department_id" class="px-3 py-2 border rounded-lg text-sm"><option value="">All</option>@foreach($departments as $d)<option value="{{ $d->id }}" {{ request('department_id')==$d->id?'selected':'' }}>{{ $d->name }}</option>@endforeach</select></div>
        <div><label class="block text-xs font-medium text-gray-500 mb-1">Status</label><select name="status" class="px-3 py-2 border rounded-lg text-sm"><option value="">All</option>@foreach(['present','late','absent','half_day','missing_checkout','early_out'] as $s)<option value="{{ $s }}" {{ request('status')==$s?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
    </form>
    <form method="POST" action="{{ route('attendance.process') }}" class="ml-auto">@csrf
        <input type="date" name="date" value="{{ now()->toDateString() }}" class="px-3 py-2 border rounded-lg text-sm">
        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm"><i class="fas fa-cogs mr-1"></i> Process</button>
    </form>
</div>

@if(session('processing_stats'))
<div class="mb-6 p-4 bg-gray-900 text-gray-100 rounded-xl border border-gray-700 shadow-lg font-mono text-sm">
    <div class="flex items-center gap-2 mb-3 text-emerald-400 font-bold border-b border-gray-700 pb-2">
        <i class="fas fa-terminal"></i>
        <span>ATTENDANCE_ENGINE_DEBUG_PANEL</span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div>
            <span class="text-gray-500">RAW_LOGS_COUNT:</span>
            <span class="text-white">{{ session('processing_stats')['total_logs'] }}</span>
        </div>
        <div>
            <span class="text-gray-500">PROCESSED_EMPLOYEES:</span>
            <span class="text-emerald-400">{{ session('processing_stats')['processed_employees'] }}</span>
        </div>
        <div>
            <span class="text-gray-500">SKIPPED_EMPLOYEES:</span>
            <span class="text-amber-400">{{ session('processing_stats')['skipped_employees'] }}</span>
        </div>
        <div>
            <span class="text-gray-500">ERRORS_FOUND:</span>
            <span class="{{ count(session('processing_stats')['errors']) > 0 ? 'text-red-400' : 'text-gray-400' }}">{{ count(session('processing_stats')['errors']) }}</span>
        </div>
    </div>
    @if(!empty(session('processing_stats')['errors']))
    <div class="mt-2 text-xs text-red-300 bg-red-900/30 p-2 rounded border border-red-900/50">
        <div class="font-bold mb-1">ERROR_LOG:</div>
        <ul class="list-disc list-inside">
            @foreach(session('processing_stats')['errors'] as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
</div>
@endif
@php $statusColors = ['present'=>'bg-green-100 text-green-800','late'=>'bg-amber-100 text-amber-800','absent'=>'bg-red-100 text-red-800','half_day'=>'bg-orange-100 text-orange-800','missing_checkout'=>'bg-purple-100 text-purple-800','early_out'=>'bg-indigo-100 text-indigo-800','leave'=>'bg-blue-100 text-blue-800','holiday'=>'bg-cyan-100 text-cyan-800']; @endphp
<div class="bg-white rounded-xl border shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50/50"><tr>
            <th class="text-left px-4 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Date</th>
            <th class="text-left px-4 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Employee</th>
            <th class="text-left px-4 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Shift</th>
            <th class="text-left px-4 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">In</th>
            <th class="text-left px-4 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Out</th>
            <th class="text-left px-4 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Work Time</th>
            <th class="text-left px-4 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Late</th>
            <th class="text-left px-4 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
            <th class="text-right px-4 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Action</th>
        </tr></thead>
        <tbody class="divide-y">
        @forelse($summaries as $s)
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 text-sm font-mono text-gray-600">{{ $s->summary_date->format('Y-m-d') }}</td>
            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                {{ $s->employee->full_name ?? '—' }}
                <div class="text-[10px] text-gray-400">{{ $s->employee->employee_code }}</div>
            </td>
            <td class="px-4 py-3 text-sm text-gray-500">
                {{ $s->shift->name ?? 'Standard' }}
                <div class="text-[10px]">{{ $s->shift ? \Carbon\Carbon::parse($s->shift->start_time)->format('H:i') . '-' . \Carbon\Carbon::parse($s->shift->end_time)->format('H:i') : '08:00-17:00' }}</div>
            </td>
            <td class="px-4 py-3 text-sm font-mono {{ $s->is_late ? 'text-amber-600 font-bold' : 'text-emerald-600' }}">
                {{ $s->check_in_time ? date('H:i', strtotime($s->check_in_time)) : '—' }}
            </td>
            <td class="px-4 py-3 text-sm font-mono {{ $s->status == 'missing_checkout' ? 'text-purple-600 italic' : 'text-gray-700' }}">
                {{ $s->check_out_time ? date('H:i', strtotime($s->check_out_time)) : '—' }}
            </td>
            <td class="px-4 py-3 text-sm">
                <span class="font-bold text-gray-800">{{ number_format($s->total_hours, 1) }}h</span>
                @if($s->overtime_hours > 0)
                    <span class="text-[10px] text-blue-500 block">+{{ number_format($s->overtime_hours, 1) }}h OT</span>
                @endif
            </td>
            <td class="px-4 py-3 text-sm {{ $s->late_minutes > 0 ? 'text-amber-600' : 'text-gray-400' }}">
                {{ $s->late_minutes > 0 ? $s->late_minutes.'m' : '—' }}
            </td>
            <td class="px-4 py-3">
                <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-wider {{ $statusColors[$s->status] ?? 'bg-gray-100 text-gray-600' }}">
                    {{ str_replace('_',' ',$s->status) }}
                </span>
            </td>
            <td class="px-4 py-3 text-right">
                <form method="POST" action="{{ route('attendance.process') }}" class="inline">
                    @csrf
                    <input type="hidden" name="date" value="{{ $s->summary_date->format('Y-m-d') }}">
                    <input type="hidden" name="employee_id" value="{{ $s->employee_id }}">
                    <button type="submit" title="Re-process this employee" class="p-1.5 hover:bg-emerald-50 text-emerald-600 rounded-lg transition-colors border border-transparent hover:border-emerald-200">
                        <i class="fas fa-sync-alt text-xs"></i>
                    </button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="9" class="px-4 py-12 text-center text-gray-400">No records. Click "Process" to generate summaries.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t bg-gray-50">{{ $summaries->appends(request()->query())->links() }}</div>
</div>
@endsection
