@extends('layouts.app')
@section('title', 'Audit Logs')
@section('content')
<div class="flex flex-wrap items-end gap-3 mb-6">
    <form class="flex flex-wrap gap-3 items-end" method="GET">
        <div><label class="block text-xs font-medium text-gray-500 mb-1">Action</label><input type="text" name="action" value="{{ request('action') }}" placeholder="Search action..." class="px-3 py-2 border rounded-lg text-sm"></div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm"><i class="fas fa-search mr-1"></i> Filter</button>
    </form>
</div>
<div class="bg-white rounded-xl border shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50"><tr>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Timestamp</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">User</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Action</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">IP Address</th>
        </tr></thead>
        <tbody class="divide-y">
        @forelse($logs as $log)
        <tr class="hover:bg-gray-50">
            <td class="px-5 py-3 text-sm font-mono text-gray-500">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
            <td class="px-5 py-3 text-sm font-semibold">{{ $log->user->name ?? 'System' }}</td>
            <td class="px-5 py-3 text-sm text-gray-800">{{ $log->action }}</td>
            <td class="px-5 py-3 text-sm font-mono text-gray-500">{{ $log->ip_address }}</td>
        </tr>
        @empty
        <tr><td colspan="4" class="px-5 py-12 text-center text-gray-400">No audit logs found.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="px-5 py-3 border-t bg-gray-50">{{ $logs->appends(request()->query())->links() }}</div>
</div>
@endsection
