@extends('layouts.app')
@section('title', 'Dashboard Overview')

@section('content')
<style>
    /* Custom Animations for the Dashboard */
    @keyframes float {
        0% { transform: translateY(0px); }
        50% { transform: translateY(-5px); }
        100% { transform: translateY(0px); }
    }
    .icon-float {
        animation: float 3s ease-in-out infinite;
    }
    .card-zoom {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .card-zoom:hover {
        transform: translateY(-5px) scale(1.02);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    .glass-panel {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
</style>

<div class="mb-8">
    <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-gray-800 to-gray-500 mb-2">Welcome Back, {{ explode(' ', auth()->user()->name)[0] ?? 'Admin' }}! 👋</h1>
    <p class="text-gray-500 font-medium">Here's what's happening in your organization today.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
    {{-- Hours Worked --}}
    <button type="button" onclick="openDashboardModal('hours')" class="w-full text-left block card-zoom relative overflow-hidden bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl p-6 shadow-lg text-white group">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl group-hover:opacity-20 transition-opacity"></div>
        <div class="flex items-center justify-between relative z-10">
            <div>
                <p class="text-blue-100 font-semibold text-sm uppercase tracking-wider mb-1">Hours Worked</p>
                <h3 class="text-4xl font-black drop-shadow-sm">{{ number_format($stats['total_hours_today'], 1) }}h</h3>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center icon-float border border-white/10 shadow-inner">
                <i class="fas fa-clock text-white text-2xl"></i>
            </div>
        </div>
    </button>

    {{-- Present Today --}}
    <button type="button" onclick="openDashboardModal('present')" class="w-full text-left block card-zoom relative overflow-hidden bg-gradient-to-br from-emerald-400 to-teal-500 rounded-2xl p-6 shadow-lg text-white group">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl group-hover:opacity-20 transition-opacity"></div>
        <div class="flex items-center justify-between relative z-10">
            <div>
                <p class="text-emerald-100 font-semibold text-sm uppercase tracking-wider mb-1">Present Today</p>
                <h3 class="text-4xl font-black drop-shadow-sm">{{ $stats['present_today'] }}</h3>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center icon-float border border-white/10 shadow-inner" style="animation-delay: 0.5s;">
                <i class="fas fa-user-check text-white text-2xl"></i>
            </div>
        </div>
    </button>

    {{-- Absent Today --}}
    <button type="button" onclick="openDashboardModal('absent')" class="w-full text-left block card-zoom relative overflow-hidden bg-gradient-to-br from-rose-400 to-red-500 rounded-2xl p-6 shadow-lg text-white group">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl group-hover:opacity-20 transition-opacity"></div>
        <div class="flex items-center justify-between relative z-10">
            <div>
                <p class="text-rose-100 font-semibold text-sm uppercase tracking-wider mb-1">Absent Today</p>
                <h3 class="text-4xl font-black drop-shadow-sm">{{ $stats['absent_today'] }}</h3>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center icon-float border border-white/10 shadow-inner" style="animation-delay: 1s;">
                <i class="fas fa-user-times text-white text-2xl"></i>
            </div>
        </div>
    </button>

    {{-- Late Today --}}
    <button type="button" onclick="openDashboardModal('late')" class="w-full text-left block card-zoom relative overflow-hidden bg-gradient-to-br from-amber-400 to-orange-500 rounded-2xl p-6 shadow-lg text-white group">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl group-hover:opacity-20 transition-opacity"></div>
        <div class="flex items-center justify-between relative z-10">
            <div>
                <p class="text-amber-100 font-semibold text-sm uppercase tracking-wider mb-1">Late Today</p>
                <h3 class="text-4xl font-black drop-shadow-sm">{{ $stats['late_today'] }}</h3>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center icon-float border border-white/10 shadow-inner" style="animation-delay: 1.5s;">
                <i class="fas fa-user-clock text-white text-2xl"></i>
            </div>
        </div>
    </button>
</div>

{{-- System Status & Quick Actions --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 space-y-8">
        {{-- Activity Overview --}}
        <div class="glass-panel rounded-3xl p-8 shadow-sm relative overflow-hidden">
            <div class="absolute -right-20 -top-20 w-64 h-64 bg-blue-50 rounded-full blur-3xl opacity-60"></div>
            <div class="relative z-10">
                <h4 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-6">System Health & Activity</h4>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="flex items-center gap-5 bg-white p-5 rounded-2xl shadow-sm border border-gray-50 hover:shadow-md transition">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-green-100 to-emerald-100 flex items-center justify-center shadow-inner">
                            <i class="fas fa-wifi text-emerald-600 text-2xl"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-black text-gray-800">{{ $stats['devices_online'] }} <span class="text-sm font-medium text-gray-400">/ {{ $stats['devices_total'] }}</span></div>
                            <div class="text-sm font-semibold text-emerald-600 uppercase tracking-wide">Devices Online</div>
                            <div class="text-xs text-gray-400 mt-1"><i class="fas fa-clock mr-1"></i> Sync: {{ $stats['last_sync'] ? \Carbon\Carbon::parse($stats['last_sync'])->diffForHumans() : 'Never' }}</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-5 bg-white p-5 rounded-2xl shadow-sm border border-gray-50 hover:shadow-md transition">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center shadow-inner">
                            <i class="fas fa-fingerprint text-blue-600 text-2xl"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-black text-gray-800">{{ $stats['today_punches'] }}</div>
                            <div class="text-sm font-semibold text-blue-600 uppercase tracking-wide">Punches Today</div>
                            <div class="text-xs text-gray-400 mt-1"><i class="fas fa-server mr-1"></i> Logs processed</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions Panel --}}
    <div class="space-y-6">
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Quick Shortcuts</h4>
            <div class="space-y-3">
                <a href="{{ route('employees.create') }}" class="group flex items-center justify-between p-4 bg-gray-50 rounded-2xl hover:bg-brand-50 hover:shadow-sm transition-all">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center shadow-sm text-brand-600 group-hover:scale-110 transition-transform">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <span class="font-semibold text-gray-700 group-hover:text-brand-700 transition-colors">Add Employee</span>
                    </div>
                    <i class="fas fa-chevron-right text-gray-300 group-hover:text-brand-400 transition-colors"></i>
                </a>
                
                <a href="{{ route('devices.index') }}" class="group flex items-center justify-between p-4 bg-gray-50 rounded-2xl hover:bg-blue-50 hover:shadow-sm transition-all">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center shadow-sm text-blue-600 group-hover:scale-110 transition-transform">
                            <i class="fas fa-microchip"></i>
                        </div>
                        <span class="font-semibold text-gray-700 group-hover:text-blue-700 transition-colors">Manage Devices</span>
                    </div>
                    <i class="fas fa-chevron-right text-gray-300 group-hover:text-blue-400 transition-colors"></i>
                </a>

                <form action="{{ route('attendance.repair') }}" method="POST" onsubmit="startQuickFix(this); return false;">
                    @csrf
                    <button type="button" onclick="startQuickFix(this.parentElement)" class="w-full group flex items-center justify-between p-4 bg-gray-50 rounded-2xl hover:bg-gray-800 hover:shadow-md transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center shadow-sm text-gray-700 group-hover:text-white transition-colors">
                                <i class="fas fa-tools"></i>
                            </div>
                            <span class="font-semibold text-gray-700 group-hover:text-white transition-colors">Quick Repair Logs</span>
                        </div>
                    </button>
                </form>
            </div>
        </div>

        {{-- Deep Repair Widget --}}
        <div class="bg-gradient-to-b from-gray-800 to-gray-900 rounded-3xl p-6 shadow-xl relative overflow-hidden text-white">
            <div class="absolute -right-6 -top-6 text-gray-700 opacity-20 transform rotate-12 text-8xl">
                <i class="fas fa-bolt"></i>
            </div>
            <div class="relative z-10">
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Deep System Optimization</h4>
                <form action="{{ route('attendance.deep-repair') }}" method="POST" class="space-y-4" id="deepRepairForm">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] text-gray-400 uppercase font-bold tracking-wider mb-1">Start Date</label>
                            <input type="date" name="date_from" required class="w-full px-3 py-2 bg-gray-800/50 border border-gray-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all outline-none" value="{{ now()->startOfMonth()->toDateString() }}">
                        </div>
                        <div>
                            <label class="block text-[10px] text-gray-400 uppercase font-bold tracking-wider mb-1">End Date</label>
                            <input type="date" name="date_to" required class="w-full px-3 py-2 bg-gray-800/50 border border-gray-700 rounded-xl text-xs text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all outline-none" value="{{ now()->toDateString() }}">
                        </div>
                    </div>
                    
                    <button type="submit" id="deepRepairBtn" class="group relative w-full overflow-hidden px-4 py-3 bg-amber-500 text-amber-950 rounded-xl font-bold text-sm shadow-[0_0_15px_rgba(245,158,11,0.3)] transition-all hover:bg-amber-400 active:scale-[0.98]">
                        <div class="flex items-center justify-center gap-2" id="deepRepairBtnText">
                            <i class="fas fa-bolt group-hover:rotate-12 transition-transform"></i>
                            <span>Initialize Deep Fix</span>
                        </div>
                        <div class="hidden flex items-center justify-center gap-3" id="deepRepairBtnLoading">
                            <i class="fas fa-circle-notch fa-spin"></i>
                            <span>Optimizing Database...</span>
                        </div>
                    </button>
                    <p class="text-[10px] text-gray-400 text-center leading-relaxed italic mt-2">Restores overnight shifts and repairs broken logs.</p>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('deepRepairForm').onsubmit = function() {
        const btn = document.getElementById('deepRepairBtn');
        const text = document.getElementById('deepRepairBtnText');
        const loading = document.getElementById('deepRepairBtnLoading');
        
        btn.classList.replace('bg-amber-500', 'bg-gray-600');
        btn.classList.replace('text-amber-950', 'text-gray-300');
        btn.disabled = true;
        text.classList.add('hidden');
        loading.classList.remove('hidden');
    };

    function startQuickFix(form) {
        const btn = form.querySelector('button');
        const icon = btn.querySelector('i');
        const span = btn.querySelector('span');
        
        btn.classList.replace('bg-gray-50', 'bg-gray-800');
        btn.classList.replace('text-gray-700', 'text-white');
        btn.disabled = true;
        icon.className = 'fas fa-sync fa-spin text-white';
        span.innerText = 'Repairing Data...';
        form.submit();
    }

    // Modal Logic
    function openDashboardModal(type) {
        const modal = document.getElementById('dashboardModal');
        const title = document.getElementById('dashboardModalTitle');
        const list = document.getElementById('dashboardModalList');
        const loading = document.getElementById('dashboardModalLoading');
        
        // Titles based on type
        const titles = {
            'present': 'Present Today',
            'absent': 'Absent Today',
            'late': 'Late Today',
            'hours': 'Hours Worked Today'
        };
        title.innerText = titles[type] || 'Employee List';
        
        list.innerHTML = '';
        list.classList.add('hidden');
        loading.classList.remove('hidden');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Fetch data
        fetch(`{{ route('dashboard.list') }}?type=${type}`)
            .then(res => res.json())
            .then(data => {
                loading.classList.add('hidden');
                list.classList.remove('hidden');
                
                if (data.length === 0) {
                    list.innerHTML = '<div class="text-center text-gray-500 py-4">No records found.</div>';
                    return;
                }
                
                let html = '<div class="space-y-3">';
                data.forEach(item => {
                    let badgeClass = 'bg-gray-100 text-gray-600';
                    if (item.status === 'present' || item.status === 'worked') badgeClass = 'bg-emerald-100 text-emerald-700';
                    if (item.status === 'absent') badgeClass = 'bg-rose-100 text-rose-700';
                    if (item.status === 'late') badgeClass = 'bg-amber-100 text-amber-700';
                    
                    html += `
                        <div class="flex items-center justify-between p-3 rounded-xl border border-gray-100 bg-gray-50 hover:bg-white transition-colors">
                            <div class="font-medium text-gray-800">${item.name}</div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm text-gray-500">${item.detail}</span>
                                <span class="px-2 py-1 rounded-lg text-xs font-bold uppercase tracking-wider ${badgeClass}">${item.status}</span>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                list.innerHTML = html;
            })
            .catch(err => {
                loading.classList.add('hidden');
                list.classList.remove('hidden');
                list.innerHTML = '<div class="text-center text-rose-500 py-4">Error loading data. Please try again.</div>';
            });
    }

    function closeDashboardModal() {
        const modal = document.getElementById('dashboardModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>

{{-- Dashboard List Modal --}}
<div id="dashboardModal" class="fixed inset-0 z-[100] hidden items-center justify-center">
    <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" onclick="closeDashboardModal()"></div>
    <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl p-6 m-4 max-h-[85vh] flex flex-col">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-gray-800" id="dashboardModalTitle">List</h3>
            <button onclick="closeDashboardModal()" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-500 transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div id="dashboardModalLoading" class="flex-1 flex flex-col items-center justify-center py-10">
            <i class="fas fa-circle-notch fa-spin text-3xl text-brand-500 mb-3"></i>
            <p class="text-gray-500 font-medium">Loading records...</p>
        </div>
        
        <div id="dashboardModalList" class="flex-1 overflow-y-auto pr-2 custom-scrollbar hidden">
            <!-- List injected via JS -->
        </div>
    </div>
</div>

<style>
    /* Custom Scrollbar for Modal */
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; rounded: 8px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>

@endsection
