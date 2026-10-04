@extends('layouts.app')

@section('title', 'License Management')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-end mb-6">
        <div class="px-3 py-1 bg-red-100 text-red-700 rounded text-xs font-mono font-bold border border-red-200">SUPER ADMIN MODE</div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- FORM TO CREATE -->
        <div class="md:col-span-1">
            <div class="bg-white p-6 rounded-xl border shadow-sm">
                <h3 class="font-semibold mb-4 text-gray-700">Generate New License</h3>
                <form action="{{ route('admin.licenses.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Organization Name</label>
                        <input type="text" name="organization_name" required placeholder="e.g. Acme Corp" 
                               class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Client Email (Optional)</label>
                        <input type="email" name="client_email" placeholder="client@example.com" 
                               class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Client Phone (WhatsApp) (Optional)</label>
                        <input type="text" name="client_phone" placeholder="e.g. 255712345678" 
                               class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Expiry Date</label>
                        <input type="date" name="expires_at" required value="{{ date('Y-m-d', strtotime('+1 year')) }}"
                               class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div class="mb-6">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Max Devices</label>
                        <input type="number" name="max_devices" value="5"
                               class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <button type="submit" class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition">
                        <i class="fas fa-plus mr-1"></i> Create License
                    </button>
                </form>
            </div>
        </div>

        <!-- LIST OF LICENSES -->
        <div class="md:col-span-2">
            <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Organization</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Contact</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">License Key / Token</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($licenses as $l)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">{{ $l->client_name }}</div>
                                <div class="text-xs text-gray-400 italic">Expires: {{ $l->expires_at->format('M d, Y') }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @if($l->client_email)<div class="text-xs text-gray-600"><i class="fas fa-envelope mr-1"></i>{{ $l->client_email }}</div>@endif
                                @if($l->client_phone)<div class="text-xs text-gray-600"><i class="fab fa-whatsapp mr-1"></i>{{ $l->client_phone }}</div>@endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 bg-gray-100 font-mono text-blue-700 rounded border border-gray-200 select-all cursor-pointer" title="Copy this Token">
                                    {{ $l->license_key }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $l->status==='active'?'bg-green-100 text-green-700':'bg-red-100 text-red-700' }}">
                                    {{ $l->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    @if($l->client_email)
                                    <form action="{{ route('admin.licenses.send_key', $l->id) }}?type=email" method="POST">
                                        @csrf
                                        <button type="submit" class="px-2 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded text-xs" title="Send Email">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                    </form>
                                    @endif
                                    
                                    @if($l->client_phone)
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $l->client_phone) }}?text={{ urlencode('Hello '.$l->client_name.', your Profitix HRM license key is: '.$l->license_key.'. It expires on '.$l->expires_at->format('M d, Y').'.') }}" 
                                       target="_blank" 
                                       class="px-2 py-1 bg-green-100 hover:bg-green-200 text-green-700 rounded text-xs" title="Send WhatsApp">
                                        <i class="fab fa-whatsapp"></i>
                                    </a>
                                    @endif

                                    <form action="{{ route('admin.licenses.suspend', $l->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="px-2 py-1 {{ $l->status === 'suspended' ? 'bg-green-100 hover:bg-green-200 text-green-700' : 'bg-orange-100 hover:bg-orange-200 text-orange-700' }} rounded text-xs" title="{{ $l->status === 'suspended' ? 'Activate License' : 'Suspend License' }}">
                                            <i class="fas {{ $l->status === 'suspended' ? 'fa-play' : 'fa-pause' }}"></i>
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.licenses.destroy', $l->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this license? The client will lose access entirely!')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded text-xs" title="Delete License">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-4 py-12 text-center text-gray-400 italic">No licenses found. Generate your first one!</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- DANGEROUS ACTIONS -->
    <div class="mt-12 p-6 bg-red-50 border border-red-200 rounded-xl">
        <h3 class="text-lg font-bold text-red-700 mb-2">Dangerous Zone</h3>
        <p class="text-sm text-red-600 mb-4 italic">Warning: Factory Reset will permanently delete ALL employees, devices, licenses, and attendance data. This cannot be undone.</p>
        
        <form action="{{ route('admin.licenses.reset') }}" method="POST" onsubmit="return confirm('CRITICAL WARNING: Are you absolutely sure? This will WIPE EVERYTHING!')">
            @csrf
            <button type="submit" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-bold transition">
                <i class="fas fa-exclamation-triangle mr-1"></i> FULL FACTORY RESET
            </button>
        </form>
    </div>
</div>
@endsection
