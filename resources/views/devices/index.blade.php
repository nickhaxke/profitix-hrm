@extends('layouts.app')
@section('title', 'Devices')
@section('content')
<div class="flex justify-between mb-6">
    <h3 class="text-lg font-semibold text-gray-700">Biometric Devices</h3>
    <div class="flex gap-2">
        <form action="{{ route('devices.sync-all') }}" method="POST" onsubmit="return startAllSync(this)">
            @csrf
            <button type="submit" id="syncAllBtn" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700 transition flex items-center gap-2">
                <i class="fas fa-sync" id="syncAllIcon"></i>
                <span id="syncAllText">Sync All Devices</span>
            </button>
        </form>
        <button onclick="document.getElementById('addDeviceModal').classList.remove('hidden')" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
            <i class="fas fa-plus mr-1"></i> Add Device
        </button>
    </div>
</div>
<div class="bg-white rounded-xl border shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50"><tr>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Device</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">IP / Port</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Connectivity</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Last Sync</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Logs</th>
            <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
        </tr></thead>
        <tbody class="divide-y">
        @forelse($devices as $d)
        <tr class="hover:bg-gray-50">
            <td class="px-5 py-3"><div class="text-sm font-semibold">{{ $d->name }}</div><div class="text-xs text-gray-400">{{ $d->location ?? '' }}</div></td>
            <td class="px-5 py-3 text-sm font-mono">{{ $d->ip_address }}:{{ $d->port }}</td>
            <td class="px-5 py-3">
                <div class="flex flex-col gap-1">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase w-fit {{ $d->bridge_online?'bg-blue-100 text-blue-800':'bg-gray-100 text-gray-800' }}">Bridge: {{ $d->bridge_online?'Online':'Offline' }}</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase w-fit {{ $d->hardware_online?'bg-green-100 text-green-800':'bg-red-100 text-red-800' }}">Device: {{ $d->hardware_online?'Online':'Offline' }}</span>
                </div>
            </td>
            <td class="px-5 py-3 text-sm text-gray-500">{{ $d->last_sync ? $d->last_sync->diffForHumans() : 'Never' }}</td>
            <td class="px-5 py-3 text-sm">{{ $d->attendance_logs_count ?? 0 }}</td>
            <td class="px-5 py-3 text-right space-x-1">
                <form method="POST" action="{{ route('devices.test', $d) }}" class="inline">@csrf<button class="px-2 py-1 bg-blue-50 text-blue-600 rounded text-xs" title="Test Connection"><i class="fas fa-wifi"></i></button></form>
                <form method="POST" action="{{ route('devices.sync', $d) }}" class="inline">@csrf<button type="button" onclick="startSingleSync(this)" class="px-2 py-1 bg-green-50 text-green-600 rounded text-xs transition hover:bg-green-600 hover:text-white" title="Sync Logs"><i class="fas fa-sync"></i></button></form>
                <form method="POST" action="{{ route('devices.destroy', $d) }}" class="inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="px-2 py-1 bg-red-50 text-red-600 rounded text-xs" title="Delete"><i class="fas fa-trash"></i></button></form>
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-5 py-12 text-center text-gray-400">No devices added yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- Add Device Modal --}}
<div id="addDeviceModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-2xl">
        <div class="flex justify-between mb-4"><h3 class="text-lg font-semibold">Add Device</h3><button onclick="document.getElementById('addDeviceModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">&times;</button></div>
        <form method="POST" action="{{ route('devices.store') }}">@csrf
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium mb-1">Name *</label><input type="text" name="name" required class="w-full px-3 py-2 border rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium mb-1">Location</label><input type="text" name="location" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium mb-1">IP Address *</label><input type="text" name="ip_address" required class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="192.168.1.201"></div>
                <div><label class="block text-sm font-medium mb-1">Port</label><input type="number" name="port" value="4370" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium mb-1">Serial Number</label><input type="text" name="serial_number" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium mb-1">Model</label><input type="text" name="device_model" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('addDeviceModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 rounded-lg text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium"><i class="fas fa-save mr-1"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
    function startAllSync(form) {
        const btn = document.getElementById('syncAllBtn');
        const icon = document.getElementById('syncAllIcon');
        const text = document.getElementById('syncAllText');
        
        btn.classList.add('opacity-75', 'cursor-not-allowed');
        btn.disabled = true;
        icon.classList.add('fa-spin');
        text.innerText = 'Syncing All...';
        return true;
    }

    function startSingleSync(btn) {
        const icon = btn.querySelector('i');
        btn.classList.add('opacity-50');
        btn.disabled = true;
        icon.classList.add('fa-spin');
        btn.closest('form').submit();
    }
</script>
@endsection
