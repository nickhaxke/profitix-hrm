@extends('layouts.app')
@section('title', 'Shift Management')
@section('content')
<div class="flex justify-between mb-6">
    <p class="text-sm text-gray-500">Define work shifts with start/end times, grace periods, and overnight support.</p>
    <div class="flex gap-2">
        <a href="{{ route('shifts.assignments') }}" class="px-4 py-2 bg-emerald-50 text-emerald-700 rounded-lg text-sm font-medium hover:bg-emerald-100 transition-colors">
            <i class="fas fa-users-cog mr-1"></i> Manage Assignments
        </a>
        <button onclick="document.getElementById('addShiftModal').classList.remove('hidden')" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium shadow-md shadow-blue-200">
            <i class="fas fa-plus mr-1"></i> Add New Shift
        </button>
    </div>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
@foreach($shifts as $s)
    <div class="bg-white rounded-xl border shadow-sm p-5 {{ $s->is_default ? 'ring-2 ring-blue-400' : '' }}">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-800">{{ $s->name }}</h3>
            @if($s->is_default)<span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded-full text-xs">Default</span>@endif
        </div>
        <div class="grid grid-cols-2 gap-2 text-sm">
            <div class="text-gray-500">Start</div><div class="font-mono font-semibold text-green-600">{{ \Carbon\Carbon::parse($s->start_time)->format('h:i A') }}</div>
            <div class="text-gray-500">End</div><div class="font-mono font-semibold text-red-600">{{ \Carbon\Carbon::parse($s->end_time)->format('h:i A') }}</div>
            <div class="text-gray-500">Grace</div><div>{{ $s->grace_minutes }} min</div>
            <div class="text-gray-500">Required</div><div>{{ $s->required_hours }}h</div>
            <div class="text-gray-500">Overnight</div><div>{{ $s->crosses_midnight ? '✅ Yes' : '❌ No' }}</div>
        </div>
        <div class="mt-4 flex gap-2 border-t pt-4">
            <button onclick='editShift(@json($s))' class="flex-1 px-3 py-1.5 bg-blue-50 text-blue-600 rounded-lg text-xs font-medium hover:bg-blue-100 transition-colors"><i class="fas fa-edit mr-1"></i> Edit</button>
            <form method="POST" action="{{ route('shifts.destroy', $s) }}" onsubmit="return confirm('Delete this shift?')" class="flex-1">@csrf @method('DELETE')<button class="w-full px-3 py-1.5 bg-red-50 text-red-600 rounded-lg text-xs font-medium hover:bg-red-100 transition-colors"><i class="fas fa-trash mr-1"></i> Delete</button></form>
        </div>
    </div>
@endforeach
</div>
{{-- Add Shift Modal --}}
<div id="addShiftModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-2xl">
        <div class="flex justify-between mb-4"><h3 class="text-lg font-semibold">Add Shift</h3><button onclick="document.getElementById('addShiftModal').classList.add('hidden')" class="text-gray-400">&times;</button></div>
        <form method="POST" action="{{ route('shifts.store') }}">@csrf
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2"><label class="block text-sm font-medium mb-1">Name *</label><input type="text" name="name" required class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="Morning Shift"></div>
                <div><label class="block text-sm font-medium mb-1">Start Time *</label><input type="time" name="start_time" required class="w-full px-3 py-2 border rounded-lg text-sm" value="08:00"></div>
                <div><label class="block text-sm font-medium mb-1">End Time *</label><input type="time" name="end_time" required class="w-full px-3 py-2 border rounded-lg text-sm" value="17:00"></div>
                <div><label class="block text-sm font-medium mb-1">Grace Minutes *</label><input type="number" name="grace_minutes" required class="w-full px-3 py-2 border rounded-lg text-sm" value="15"></div>
                <div><label class="block text-sm font-medium mb-1">Late Threshold (min)</label><input type="number" name="late_threshold_minutes" class="w-full px-3 py-2 border rounded-lg text-sm" value="30"></div>
                <div><label class="block text-sm font-medium mb-1">Required Hours *</label><input type="number" name="required_hours" step="0.5" required class="w-full px-3 py-2 border rounded-lg text-sm" value="8"></div>
                <div><label class="block text-sm font-medium mb-1">Half Day Hours</label><input type="number" name="half_day_hours" step="0.5" class="w-full px-3 py-2 border rounded-lg text-sm" value="4"></div>
                <div class="flex items-center gap-2"><input type="checkbox" name="crosses_midnight" value="1" id="overnight"><label for="overnight" class="text-sm">Crosses Midnight</label></div>
                <div class="flex items-center gap-2"><input type="checkbox" name="is_default" value="1" id="isDefault"><label for="isDefault" class="text-sm">Default Shift</label></div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('addShiftModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 rounded-lg text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium">Save</button>
            </div>
        </form>
    </div>
</div>
{{-- Edit Shift Modal --}}
<div id="editShiftModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-2xl">
        <div class="flex justify-between mb-4"><h3 class="text-lg font-semibold">Edit Shift</h3><button onclick="document.getElementById('editShiftModal').classList.add('hidden')" class="text-gray-400">&times;</button></div>
        <form id="editShiftForm" method="POST" action="">@csrf @method('PUT')
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2"><label class="block text-sm font-medium mb-1">Name *</label><input type="text" name="name" id="edit_name" required class="w-full px-3 py-2 border rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium mb-1">Start Time *</label><input type="time" name="start_time" id="edit_start_time" required class="w-full px-3 py-2 border rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium mb-1">End Time *</label><input type="time" name="end_time" id="edit_end_time" required class="w-full px-3 py-2 border rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium mb-1">Grace Minutes *</label><input type="number" name="grace_minutes" id="edit_grace_minutes" required class="w-full px-3 py-2 border rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium mb-1">Late Threshold (min)</label><input type="number" name="late_threshold_minutes" id="edit_late_threshold_minutes" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium mb-1">Required Hours *</label><input type="number" name="required_hours" id="edit_required_hours" step="0.5" required class="w-full px-3 py-2 border rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium mb-1">Half Day Hours</label><input type="number" name="half_day_hours" id="edit_half_day_hours" step="0.5" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
                <div class="flex items-center gap-2"><input type="checkbox" name="crosses_midnight" value="1" id="edit_overnight"><label for="edit_overnight" class="text-sm">Crosses Midnight</label></div>
                <div class="flex items-center gap-2"><input type="checkbox" name="is_default" value="1" id="edit_isDefault"><label for="edit_isDefault" class="text-sm">Default Shift</label></div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('editShiftModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 rounded-lg text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium shadow-md shadow-blue-200">Update Shift</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editShift(shift) {
        const form = document.getElementById('editShiftForm');
        form.action = `/shifts/${shift.id}`;
        document.getElementById('edit_name').value = shift.name;
        document.getElementById('edit_start_time').value = shift.start_time;
        document.getElementById('edit_end_time').value = shift.end_time;
        document.getElementById('edit_grace_minutes').value = shift.grace_minutes;
        document.getElementById('edit_late_threshold_minutes').value = shift.late_threshold_minutes || 0;
        document.getElementById('edit_required_hours').value = shift.required_hours;
        document.getElementById('edit_half_day_hours').value = shift.half_day_hours || 0;
        document.getElementById('edit_overnight').checked = !!shift.crosses_midnight;
        document.getElementById('edit_isDefault').checked = !!shift.is_default;
        document.getElementById('editShiftModal').classList.remove('hidden');
    }
</script>
@endsection
