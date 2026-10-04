@extends('layouts.app')
@section('title', 'Database Backups')
@section('content')
<div class="flex justify-between mb-6">
    <div>
        <h3 class="text-lg font-semibold">Database Backups</h3>
        <p class="text-sm text-gray-500">Create, download, and manage system database dumps.</p>
    </div>
    <form method="POST" action="{{ route('backups.store') }}">@csrf
        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium"><i class="fas fa-database mr-1"></i> Create Backup</button>
    </form>
</div>

<div class="bg-white rounded-xl border shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50"><tr>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Filename</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Date Created</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Size</th>
            <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
        </tr></thead>
        <tbody class="divide-y">
        @forelse($backups as $b)
        <tr class="hover:bg-gray-50">
            <td class="px-5 py-3 text-sm font-semibold text-blue-600"><i class="fas fa-file-archive text-gray-400 mr-2"></i>{{ $b['name'] }}</td>
            <td class="px-5 py-3 text-sm text-gray-500">{{ $b['date'] }}</td>
            <td class="px-5 py-3 text-sm font-mono text-gray-500">{{ $b['size'] }}</td>
            <td class="px-5 py-3 text-right">
                <a href="{{ route('backups.download', $b['name']) }}" class="inline-block px-2 py-1 bg-blue-50 text-blue-600 rounded text-xs mr-1"><i class="fas fa-download"></i> Download</a>
                <form method="POST" action="{{ route('backups.destroy', $b['name']) }}" class="inline" onsubmit="return confirm('Delete this backup forever?')">@csrf @method('DELETE')<button class="px-2 py-1 bg-red-50 text-red-600 rounded text-xs"><i class="fas fa-trash"></i> Delete</button></form>
            </td>
        </tr>
        @empty
        <tr><td colspan="4" class="px-5 py-12 text-center text-gray-400">No backups found. Create one now.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
