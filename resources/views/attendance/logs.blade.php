@extends('layouts.app')
@section('title', 'Raw Attendance Logs')
@section('content')
<div class="flex flex-wrap items-end gap-3 mb-6">
    <form class="flex flex-wrap gap-3 items-end" method="GET">
        <div><label class="block text-xs font-medium text-gray-500 mb-1">From</label><input type="date" name="date_from" value="{{ request('date_from', now()->subDays(7)->toDateString()) }}" class="px-3 py-2 border rounded-lg text-sm"></div>
        <div><label class="block text-xs font-medium text-gray-500 mb-1">To</label><input type="date" name="date_to" value="{{ request('date_to', now()->toDateString()) }}" class="px-3 py-2 border rounded-lg text-sm"></div>
        <div><label class="block text-xs font-medium text-gray-500 mb-1">Employee</label><select name="employee_id" class="px-3 py-2 border rounded-lg text-sm"><option value="">All</option>@foreach($employees as $e)<option value="{{ $e->id }}" {{ request('employee_id')==$e->id?'selected':'' }}>{{ $e->full_name }}</option>@endforeach</select></div>
        <div><label class="block text-xs font-medium text-gray-500 mb-1">Device</label><select name="device_id" class="px-3 py-2 border rounded-lg text-sm"><option value="">All</option>@foreach($devices as $d)<option value="{{ $d->id }}" {{ request('device_id')==$d->id?'selected':'' }}>{{ $d->name }}</option>@endforeach</select></div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
    </form>
    <button onclick="document.getElementById('importModal').classList.remove('hidden')" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm ml-auto"><i class="fas fa-file-upload mr-1"></i> Upload ABT/DAT Log</button>
    <button onclick="document.getElementById('punchModal').classList.remove('hidden')" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm ml-2"><i class="fas fa-plus mr-1"></i> Manual Punch</button>
</div>
<div class="bg-white rounded-xl border shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50"><tr>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Time</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Bio ID</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Employee</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Type</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Device</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Source</th>
        </tr></thead>
        <tbody class="divide-y">
        @forelse($logs as $log)
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm font-mono">{{ $log->punch_time->format('Y-m-d H:i:s') }}</td>
            <td class="px-4 py-3 text-sm font-bold text-blue-600">{{ $log->employee->biometric_id ?? '—' }}</td>
            <td class="px-4 py-3 text-sm font-medium">{{ $log->employee->full_name ?? '—' }}</td>
            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $log->punch_type==='in'?'bg-green-100 text-green-800':($log->punch_type==='out'?'bg-red-100 text-red-800':'bg-gray-100 text-gray-600') }}">{{ ucfirst($log->punch_type) }}</span></td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ $log->device->name ?? '—' }}</td>
            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-xs {{ $log->source==='manual_file'?'bg-indigo-100 text-indigo-800':($log->source==='manual'?'bg-amber-100 text-amber-800':'bg-blue-100 text-blue-800') }}">{{ ucfirst(str_replace('_',' ',$log->source)) }}</span></td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">No logs found.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t bg-gray-50">{{ $logs->appends(request()->query())->links() }}</div>
</div>

{{-- Import File Modal --}}
<div id="importModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-2xl">
        <div class="flex justify-between mb-4"><h3 class="text-lg font-semibold">Upload Device Log (.abt / .dat)</h3><button onclick="document.getElementById('importModal').classList.add('hidden')" class="text-gray-400">&times;</button></div>
        <form method="POST" action="{{ route('attendance.import-file') }}" enctype="multipart/form-data">@csrf
            <div class="mb-3"><label class="block text-sm font-medium mb-1">Select Existing Device/Branch</label>
                <select name="device_id" class="w-full px-3 py-2 border rounded-lg text-sm mb-2">
                    <option value="">-- Create New Branch/Device --</option>
                    @foreach($devices as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                </select>
                <label class="block text-sm font-medium mb-1">OR Type New Device/Branch Name *</label>
                <input type="text" name="new_device_name" placeholder="e.g. Dodoma Branch" class="w-full px-3 py-2 border rounded-lg text-sm">
                <p class="text-xs text-gray-500 mt-1">If device is not listed, type the name above to auto-create it.</p>
            </div>
            <div class="mb-4"><label class="block text-sm font-medium mb-1">Log File (.mdb, .abt, .dat, .csv, .txt) *</label>
                <input type="file" name="file" accept=".mdb,.dat,.txt,.abt,.csv,.xls,.xlsx" required class="w-full px-3 py-2 border rounded-lg text-sm">
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 rounded-lg text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium">Upload & Extract</button>
            </div>
        </form>
    </div>
</div>

{{-- Manual Punch Modal --}}
<div id="punchModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-2xl">
        <div class="flex justify-between mb-4"><h3 class="text-lg font-semibold">Manual Punch</h3><button onclick="document.getElementById('punchModal').classList.add('hidden')" class="text-gray-400">&times;</button></div>
        <form method="POST" action="{{ route('attendance.manual-punch') }}">@csrf
            <div class="mb-3"><label class="block text-sm font-medium mb-1">Employee *</label><select name="employee_id" required class="w-full px-3 py-2 border rounded-lg text-sm">@foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->full_name }}</option>@endforeach</select></div>
            <div class="mb-3"><label class="block text-sm font-medium mb-1">Date & Time *</label><input type="datetime-local" name="punch_time" required class="w-full px-3 py-2 border rounded-lg text-sm" value="{{ now()->format('Y-m-d\TH:i') }}"></div>
            <div class="mb-4"><label class="block text-sm font-medium mb-1">Type *</label><select name="punch_type" required class="w-full px-3 py-2 border rounded-lg text-sm"><option value="in">Check In</option><option value="out">Check Out</option></select></div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('punchModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 rounded-lg text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection
