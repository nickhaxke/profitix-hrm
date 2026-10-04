@extends('layouts.app')
@section('title', 'Settings')
@section('content')

@if(isset($license) && $license)
<div class="bg-gradient-to-r from-slate-800 to-blue-900 rounded-xl border border-blue-800 shadow-lg p-6 mb-8 text-white relative overflow-hidden">
    <div class="absolute right-0 top-0 opacity-10">
        <i class="fas fa-shield-alt" style="font-size: 150px; transform: translate(20%, -20%);"></i>
    </div>
    <h3 class="text-xs font-bold uppercase tracking-widest mb-5 text-blue-300 relative z-10"><i class="fas fa-key mr-2"></i> Active License Information</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 relative z-10">
        <div>
            <p class="text-xs font-semibold text-blue-400 uppercase tracking-wider mb-1">License Key</p>
            <p class="text-xl font-mono font-bold tracking-widest bg-black/20 inline-block px-3 py-1 rounded">{{ $license->license_key }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold text-blue-400 uppercase tracking-wider mb-1">Expiry Date</p>
            <p class="text-xl font-bold">{{ $license->expires_at ? $license->expires_at->format('M d, Y') : 'Lifetime' }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold text-blue-400 uppercase tracking-wider mb-1">Status</p>
            @if($license->expires_at)
                @php
                    $days = now()->diffInDays($license->expires_at, false);
                @endphp
                @if($days > 0)
                    <p class="text-xl font-bold text-green-400"><i class="fas fa-clock mr-1"></i> {{ intval($days) }} Days Remaining</p>
                @else
                    <p class="text-xl font-bold text-red-400"><i class="fas fa-exclamation-triangle mr-1"></i> Expired</p>
                @endif
            @else
                <p class="text-xl font-bold text-green-400"><i class="fas fa-check-circle mr-1"></i> Active (Lifetime)</p>
            @endif
        </div>
    </div>
</div>
@endif

<form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    {{-- Institution Profile --}}
    <div class="bg-white rounded-xl border shadow-sm p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4"><i class="fas fa-building mr-1"></i> Institution Profile</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Institution Name</label>
                <input type="text" name="company_name" value="{{ \App\Models\Setting::getValue('company_name', 'Profitix HRM') }}" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="Enter institution name">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Institution Slogan</label>
                <input type="text" name="company_slogan" value="{{ \App\Models\Setting::getValue('company_slogan', '') }}" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="e.g. Empowering Your Workforce">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Institution Logo</label>
                @if(\App\Models\Setting::getValue('company_logo'))
                    <div class="mb-2">
                        <img src="{{ asset('storage/' . \App\Models\Setting::getValue('company_logo')) }}" alt="Logo" class="h-16 object-contain">
                    </div>
                @endif
                <input type="file" name="company_logo" accept="image/*" class="w-full px-3 py-2 border rounded-lg text-sm bg-gray-50">
                <p class="text-xs text-gray-500 mt-1">Recommended size: 200x50 pixels. PNG or JPG.</p>
            </div>
        </div>
    </div>

    @foreach($settings as $group => $items)
    @php
        // Filter out items already handled
        $filteredItems = $items->filter(fn($item) => !in_array($item->key, ['company_name', 'company_slogan', 'company_logo']));
    @endphp
    @if($filteredItems->count() > 0)
    <div class="bg-white rounded-xl border shadow-sm p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4"><i class="fas fa-cog mr-1"></i> {{ ucfirst($group) }} Settings</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($items as $setting)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ ucwords(str_replace('_', ' ', $setting->key)) }}</label>
                <input type="text" name="{{ str_replace('.', '__', $setting->key) }}" value="{{ $setting->value }}" class="w-full px-3 py-2 border rounded-lg text-sm">
            </div>
            @endforeach
        </div>
    </div>
    @endif
    @endforeach
    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium"><i class="fas fa-save mr-1"></i> Save Settings</button>
</form>
@endsection
