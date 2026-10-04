@extends('layouts.app')
@section('title', 'Device Control Center')
@section('content')

<!-- Inject SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="mb-8">
    <h3 class="text-2xl font-extrabold text-gray-900 tracking-tight">Device Control Center</h3>
    <p class="text-sm text-gray-500 mt-1">Manage biometric hardware, synchronize users, and monitor device health.</p>
</div>

<!-- Device Selection Bar -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-8 flex flex-col md:flex-row items-center gap-4">
    <form method="GET" action="{{ route('devices.users') }}" class="flex-1 flex flex-col md:flex-row items-center gap-4 w-full">
        <div class="relative flex-1 w-full max-w-md">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                <i class="fas fa-microchip"></i>
            </div>
            <select name="device_id" class="w-full pl-10 pr-10 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none" required onchange="this.form.submit()">
                <option value="">-- Select Biometric Device --</option>
                @foreach($devices as $d)
                <option value="{{ $d->id }}" {{ ($selectedDevice && $selectedDevice->id == $d->id) ? 'selected' : '' }}>
                    {{ $d->name }} ({{ $d->ip_address }})
                </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="w-full md:w-auto px-6 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-700 shadow-lg shadow-blue-200 transition-all flex items-center justify-center gap-2">
            <i class="fas fa-plug"></i> Connect Device
        </button>
    </form>

    @if($selectedDevice && !$error)
    <div class="flex items-center gap-3 px-4 py-2 {{ $selectedDevice->is_online ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-gray-50 text-gray-400 border-gray-100' }} rounded-xl border transition-all">
        <div class="w-2 h-2 rounded-full {{ $selectedDevice->is_online ? 'bg-emerald-500 animate-ping' : 'bg-gray-300' }}"></div>
        <span class="text-xs font-bold uppercase tracking-wider">{{ $selectedDevice->is_online ? 'Bridge Online (Heartbeat)' : 'Bridge Offline' }}</span>
    </div>
    @endif
</div>

@if($error)
<div class="mb-8 p-6 bg-red-50 text-red-700 rounded-2xl border border-red-100 flex items-start gap-4 shadow-sm">
    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
        <i class="fas fa-exclamation-triangle"></i>
    </div>
    <div>
        <h4 class="font-bold text-lg">Connection Failure</h4>
        <p class="text-sm opacity-90 mb-3">{{ $error }}</p>
        <button onclick="window.location.reload()" class="px-4 py-1.5 bg-red-600 text-white text-xs font-bold rounded-lg hover:bg-red-700 transition-all">Retry Connection</button>
    </div>
</div>
@endif

@if($selectedDevice && !$error)
    @php
        $totalUsers = count($deviceUsers);
        $missingInSystem = collect($deviceUsers)->where('exists_in_system', false)->count();
        $syncedUsers = $totalUsers - $missingInSystem;
    @endphp

    <!-- Tab Navigation -->
    <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-px">
        <button onclick="switchTab('user-sync')" id="tab-user-sync" class="tab-btn px-6 py-3 text-sm font-bold border-b-2 transition-all">
            <i class="fas fa-sync-alt mr-2"></i>User Sync
        </button>
        <button onclick="switchTab('push-center')" id="tab-push-center" class="tab-btn px-6 py-3 text-sm font-bold border-b-2 transition-all">
            <i class="fas fa-cloud-upload-alt mr-2"></i>Push Center
        </button>
        <button onclick="switchTab('logs')" id="tab-logs" class="tab-btn px-6 py-3 text-sm font-bold border-b-2 transition-all">
            <i class="fas fa-history mr-2"></i>Logs & Status
        </button>
        <button onclick="switchTab('advanced')" id="tab-advanced" class="tab-btn px-6 py-3 text-sm font-bold border-b-2 transition-all">
            <i class="fas fa-cogs mr-2"></i>Advanced
        </button>
    </div>

    <!-- Tab Contents -->
    <div id="tab-content">
        
        <!-- Tab: User Sync (Import from Device) -->
        <div id="content-user-sync" class="tab-pane hidden">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                    <p class="text-xs font-bold text-gray-400 uppercase mb-1">In Device</p>
                    <h4 class="text-3xl font-black text-gray-900">{{ $totalUsers }} <span class="text-sm font-medium text-gray-400">Users</span></h4>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                    <p class="text-xs font-bold text-emerald-500 uppercase mb-1">Synced</p>
                    <h4 class="text-3xl font-black text-emerald-600">{{ $syncedUsers }} <span class="text-sm font-medium text-emerald-300">Matching</span></h4>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                    <p class="text-xs font-bold text-orange-500 uppercase mb-1">Missing in System</p>
                    <h4 class="text-3xl font-black text-orange-600">{{ $missingInSystem }} <span class="text-sm font-medium text-orange-300">New</span></h4>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-50 flex flex-wrap items-center justify-between gap-4 bg-gray-50/30">
                    <div>
                        <h4 class="font-bold text-gray-800 text-lg">Device User List</h4>
                        <p class="text-xs text-gray-500">Sync and import users from the biometric hardware into the system.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <form method="POST" action="{{ route('devices.command', $selectedDevice->id) }}" class="ajax-form">
                            @csrf
                            <input type="hidden" name="command" value="fetch_users">
                            <button type="submit" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 rounded-xl text-sm font-bold hover:bg-gray-50 transition-all flex items-center gap-2 shadow-sm">
                                <i class="fas fa-sync-alt"></i> Refresh List from Device
                            </button>
                        </form>
                        <button type="submit" form="importForm" class="px-6 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-700 shadow-lg shadow-blue-100 transition-all flex items-center gap-2">
                            <i class="fas fa-file-import"></i> Import Selected
                        </button>
                    </div>
                </div>

                <form id="importForm" method="POST" action="{{ route('devices.users.import') }}" class="ajax-form">
                    @csrf
                    <input type="hidden" name="device_id" value="{{ $selectedDevice->id }}">
                    <div class="p-5 border-b border-gray-50 flex flex-wrap justify-between items-center bg-gray-50/30 gap-4">
                        <div class="flex items-center gap-3">
                            <h4 class="font-bold text-gray-800">Device Users Table</h4>
                            <div class="relative">
                                <input type="text" id="deviceUserSearch" placeholder="Filter device users..." class="pl-9 pr-3 py-1.5 border border-gray-200 rounded-lg text-xs focus:ring-1 focus:ring-blue-500">
                                <i class="fas fa-search absolute left-3 top-2 text-gray-400 text-[10px]"></i>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if(config('app.env') === 'production')
                            <button type="button" onclick="triggerAction('{{ route('devices.sync-users', $selectedDevice->id) }}', {}, 'Request user list from device? This may take a minute to update.')" class="px-4 py-2 bg-purple-600 text-white rounded-xl text-xs font-bold hover:bg-purple-700 transition-all flex items-center gap-2">
                                <i class="fas fa-sync"></i> Refresh User List
                            </button>
                            @endif
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-700 transition-all flex items-center gap-2">
                                <i class="fas fa-download"></i> Import Selected
                            </button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm" id="deviceUsersTable">
                            <thead class="bg-gray-50/50">
                                <tr class="text-left">
                                    <th class="px-5 py-3 w-10"><input type="checkbox" id="selectAllDevice" class="rounded border-gray-300"></th>
                                    <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase">Bio ID</th>
                                    <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase">Name</th>
                                    <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase">Role</th>
                                    <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase text-center">Fingers</th>
                                    <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase text-center">Status</th>
                                    <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse($deviceUsers as $u)
                                <tr class="hover:bg-blue-50/30 transition-colors device-user-row">
                                    <td class="px-5 py-3">
                                        @if(!$u['exists_in_system'])
                                        <input type="checkbox" name="selected_users[{{ $u['user_id'] }}]" value="{{ $u['name'] ?: 'Device User ' . $u['user_id'] }}" class="device-checkbox rounded border-gray-300">
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 font-mono font-bold text-gray-500">{{ $u['user_id'] }}</td>
                                    <td class="px-5 py-3 font-semibold text-gray-800">{{ $u['name'] ?: '—' }}</td>
                                    <td class="px-5 py-3">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase {{ $u['privilege'] === 'Super Admin' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $u['privilege'] }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        @if($u['fingerprint_count'] > 0)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded-full text-[10px] font-black border border-emerald-100">
                                            <i class="fas fa-fingerprint"></i> {{ $u['fingerprint_count'] }}
                                        </span>
                                        @else
                                        <span class="text-gray-200">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        @if($u['exists_in_system'])
                                        <span class="text-emerald-500 text-[10px] font-bold flex items-center justify-center gap-1"><i class="fas fa-check-circle"></i> SYNCED</span>
                                        @else
                                        <span class="text-orange-500 text-[10px] font-bold flex items-center justify-center gap-1"><i class="fas fa-cloud-download-alt"></i> MISSING</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <div class="flex justify-end gap-1">
                                            <button type="button" onclick="triggerAction('{{ route('devices.users.enroll') }}', {uid: '{{ $u['uid'] }}', user_id: '{{ $u['user_id'] }}', device_id: '{{ $selectedDevice->id }}'}, 'Enroll new fingerprint for {{ $u['name'] }}?')" class="w-7 h-7 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all shadow-sm">
                                                <i class="fas fa-fingerprint text-[10px]"></i>
                                            </button>
                                            <button type="button" onclick="triggerAction('{{ route('devices.users.delete') }}', {uid: '{{ $u['uid'] }}', user_id: '{{ $u['user_id'] }}', device_id: '{{ $selectedDevice->id }}'}, 'Delete user {{ $u['name'] }} from DEVICE?')" class="w-7 h-7 flex items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all shadow-sm">
                                                <i class="fas fa-trash text-[10px]"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-20 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center text-gray-300">
                                                <i class="fas fa-users-slash text-2xl"></i>
                                            </div>
                                            <h5 class="font-bold text-gray-500">No User Data Available</h5>
                                            @if(config('app.env') === 'production')
                                            <p class="text-xs text-gray-400 max-w-xs mx-auto">Cloud Mode: Biometric user templates must be fetched manually. Click the 'Refresh User List' button above to request data from the Bridge.</p>
                                            @else
                                            <p class="text-xs text-gray-400">Please connect the device to list users.</p>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tab: Push Center (System to Device) -->
        <div id="content-push-center" class="tab-pane hidden">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <form method="POST" action="{{ route('devices.users.push') }}" class="ajax-form">
                    @csrf
                    <input type="hidden" name="device_id" value="{{ $selectedDevice->id }}">
                    
                    <div class="p-6 border-b border-gray-50 bg-gray-50/30">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <h4 class="font-bold text-gray-800 text-lg">Push System Employees</h4>
                                <p class="text-xs text-gray-500">Select employees from the system to upload their profiles to the biometric device.</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <select id="deptFilter" class="px-3 py-2 border border-gray-200 rounded-xl text-xs bg-white focus:ring-2 focus:ring-purple-500 outline-none">
                                    <option value="">All Departments</option>
                                    @foreach($departments as $dept)
                                    <option value="{{ $dept->name }}">{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl text-sm font-bold hover:shadow-lg transition-all flex items-center gap-2">
                                    <i class="fas fa-cloud-upload-alt"></i> Push Selected
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm" id="systemEmployeesTable">
                            <thead class="bg-gray-50/50">
                                <tr class="text-left">
                                    <th class="px-6 py-4 w-10 border-b border-gray-100"><input type="checkbox" id="selectAllSystem" class="rounded border-gray-300"></th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase border-b border-gray-100">Code</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase border-b border-gray-100">Full Name</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase border-b border-gray-100">Department</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase border-b border-gray-100 text-center">Bio ID</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase border-b border-gray-100 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 bg-white">
                            @php $deviceBioIds = collect($deviceUsers)->pluck('user_id')->toArray(); @endphp
                            @forelse($systemEmployees as $emp)
                            @php $isOnDevice = in_array($emp->biometric_id, $deviceBioIds); @endphp
                            <tr class="hover:bg-purple-50/20 transition-colors system-employee-row {{ $isOnDevice ? 'opacity-50' : '' }}">
                                <td class="px-6 py-4">
                                    @if(!$isOnDevice && !empty($emp->biometric_id))
                                    <input type="checkbox" name="employee_ids[]" value="{{ $emp->id }}" class="system-checkbox rounded border-gray-300">
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-gray-500">{{ $emp->employee_code }}</td>
                                <td class="px-6 py-4 font-semibold text-gray-800">{{ $emp->full_name }}</td>
                                <td class="px-6 py-4 text-gray-500 dept-cell">{{ $emp->department->name ?? '—' }}</td>
                                <td class="px-6 py-4 text-center font-mono font-bold text-blue-600">{{ $emp->biometric_id ?: 'N/A' }}</td>
                                <td class="px-6 py-4 text-center">
                                    @if($isOnDevice)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-100 text-gray-600 text-[10px] font-bold rounded-md uppercase tracking-tight">
                                            <i class="fas fa-check"></i> Already on Device
                                        </span>
                                    @elseif(empty($emp->biometric_id))
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-50 text-red-500 text-[10px] font-bold rounded-md uppercase border border-red-100">
                                            <i class="fas fa-times-circle"></i> Missing Bio ID
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-purple-50 text-purple-600 text-[10px] font-bold rounded-md uppercase border border-purple-100">
                                            <i class="fas fa-clock"></i> Ready to Push
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="px-6 py-12 text-center text-gray-400 font-medium">No employees found in system.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tab: Logs -->
        <div id="content-logs" class="tab-pane hidden">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center max-w-2xl mx-auto">
                <div class="w-20 h-20 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center text-3xl mx-auto mb-6 shadow-inner">
                    <i class="fas fa-cloud-download-alt"></i>
                </div>
                <h4 class="text-xl font-black text-gray-900 mb-2">Sync Attendance Logs</h4>
                <p class="text-gray-500 mb-8 leading-relaxed">Download all raw punch records from the device and automatically process them into daily attendance summaries for reporting.</p>
                
                <form method="POST" action="{{ route('devices.sync', $selectedDevice->id) }}" class="ajax-form">
                    @csrf
                    <button type="submit" class="px-8 py-4 bg-blue-600 text-white rounded-2xl font-bold hover:bg-blue-700 shadow-xl shadow-blue-100 transition-all flex items-center justify-center gap-3 mx-auto">
                        <i class="fas fa-play-circle text-xl"></i> Start Synchronization Process
                    </button>
                </form>

                <div class="mt-12 pt-8 border-t border-gray-50 grid grid-cols-2 gap-4">
                    <div class="p-4 bg-gray-50 rounded-xl">
                        <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Last Sync</p>
                        <p class="text-sm font-bold text-gray-800">{{ $selectedDevice->last_sync ? \Carbon\Carbon::parse($selectedDevice->last_sync)->diffForHumans() : 'Never' }}</p>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl">
                        <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Total Logs Count</p>
                        <p class="text-sm font-bold text-gray-800">{{ $selectedDevice->attendance_logs_count ?? 0 }} Records</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Advanced -->
        <div id="content-advanced" class="tab-pane hidden">
            <div class="bg-red-50/30 rounded-2xl border border-red-100 p-8">
                <div class="flex items-center gap-3 mb-8 pb-4 border-b border-red-100">
                    <div class="w-10 h-10 bg-red-100 text-red-600 rounded-xl flex items-center justify-center text-lg shadow-sm">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div>
                        <h4 class="font-black text-red-900">Advanced Maintenance</h4>
                        <p class="text-xs text-red-600">DANGER ZONE: These actions directly affect the hardware memory.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Basic Ops -->
                    <div class="space-y-4">
                        <h5 class="text-xs font-black text-gray-400 uppercase tracking-widest px-2">General Controls</h5>
                        
                        <form method="POST" action="{{ route('devices.command', $selectedDevice->id) }}" class="ajax-form">
                            @csrf <input type="hidden" name="command" value="test">
                            <button class="w-full px-5 py-4 bg-white border border-gray-200 rounded-2xl flex items-center justify-between group hover:border-blue-500 transition-all shadow-sm">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center group-hover:bg-blue-500 group-hover:text-white transition-all"><i class="fas fa-signal"></i></div>
                                    <div class="text-left"><p class="text-sm font-bold text-gray-800">Test Connection</p><p class="text-[10px] text-gray-400">Ping device to verify connectivity</p></div>
                                </div>
                                <i class="fas fa-chevron-right text-gray-200 group-hover:text-blue-200"></i>
                            </button>
                        </form>

                        <form method="POST" action="{{ route('devices.command', $selectedDevice->id) }}" class="ajax-form" data-confirm="Sync device time with server time?">
                            @csrf <input type="hidden" name="command" value="sync-time">
                            <button class="w-full px-5 py-4 bg-white border border-gray-200 rounded-2xl flex items-center justify-between group hover:border-emerald-500 transition-all shadow-sm">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center group-hover:bg-emerald-500 group-hover:text-white transition-all"><i class="fas fa-clock"></i></div>
                                    <div class="text-left"><p class="text-sm font-bold text-gray-800">Synchronize Time</p><p class="text-[10px] text-gray-400">Match device clock with server</p></div>
                                </div>
                                <i class="fas fa-chevron-right text-gray-200 group-hover:text-emerald-200"></i>
                            </button>
                        </form>

                        <form method="POST" action="{{ route('devices.command', $selectedDevice->id) }}" class="ajax-form" data-confirm="Are you sure you want to reboot the hardware?">
                            @csrf <input type="hidden" name="command" value="reboot">
                            <button class="w-full px-5 py-4 bg-white border border-gray-200 rounded-2xl flex items-center justify-between group hover:border-orange-500 transition-all shadow-sm">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-orange-50 text-orange-500 flex items-center justify-center group-hover:bg-orange-500 group-hover:text-white transition-all"><i class="fas fa-power-off"></i></div>
                                    <div class="text-left"><p class="text-sm font-bold text-gray-800">Reboot Device</p><p class="text-[10px] text-gray-400">Restart the biometric machine</p></div>
                                </div>
                                <i class="fas fa-chevron-right text-gray-200 group-hover:text-orange-200"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Dangerous Ops -->
                    <div class="space-y-4">
                        <h5 class="text-xs font-black text-red-400 uppercase tracking-widest px-2">Dangerous Operations</h5>
                        
                        <form method="POST" action="{{ route('devices.command', $selectedDevice->id) }}" class="ajax-form" data-confirm="DANGER: This will PERMANENTLY delete all punch logs from the machine memory. Continue?">
                            @csrf <input type="hidden" name="command" value="clear-logs">
                            <button class="w-full px-5 py-4 bg-white border border-red-100 rounded-2xl flex items-center justify-between group hover:bg-red-600 transition-all shadow-sm">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-red-50 text-red-500 flex items-center justify-center group-hover:bg-white group-hover:text-red-600 transition-all"><i class="fas fa-eraser"></i></div>
                                    <div class="text-left"><p class="text-sm font-bold text-gray-800 group-hover:text-white">Clear Device Logs</p><p class="text-[10px] text-red-300 group-hover:text-red-100">Wipe machine attendance memory</p></div>
                                </div>
                                <i class="fas fa-exclamation-triangle text-red-200 group-hover:text-white"></i>
                            </button>
                        </form>

                        <form method="POST" action="{{ route('devices.command', $selectedDevice->id) }}" class="ajax-form" data-confirm="EXTREME DANGER: This will delete ALL users, cards, and fingerprints from the machine. Recovery is impossible without re-syncing. Continue?">
                            @csrf <input type="hidden" name="command" value="clear-users">
                            <button class="w-full px-5 py-4 bg-red-600 rounded-2xl flex items-center justify-between group hover:bg-red-700 transition-all shadow-xl shadow-red-100">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-white/20 text-white flex items-center justify-center"><i class="fas fa-trash-alt"></i></div>
                                    <div class="text-left"><p class="text-sm font-bold text-white">Factory Reset Users</p><p class="text-[10px] text-red-100">Delete all biometric templates</p></div>
                                </div>
                                <i class="fas fa-bomb text-white opacity-40"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts for Redesign -->
    <script>
        // Tab Switching Logic
        function switchTab(tabId) {
            // Update buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('border-blue-600', 'text-blue-600');
                btn.classList.add('border-transparent', 'text-gray-400');
            });
            document.getElementById('tab-' + tabId).classList.add('border-blue-600', 'text-blue-600');
            document.getElementById('tab-' + tabId).classList.remove('border-transparent', 'text-gray-400');

            // Update content
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.add('hidden'));
            document.getElementById('content-' + tabId).classList.remove('hidden');

            // Store preference
            localStorage.setItem('active_device_tab', tabId);
        }

        // Initialize Tab
        document.addEventListener('DOMContentLoaded', () => {
            const savedTab = localStorage.getItem('active_device_tab') || 'user-sync';
            switchTab(savedTab);
        });

        // Search Filters
        document.getElementById('deviceUserSearch')?.addEventListener('keyup', function(e) {
            const term = e.target.value.toLowerCase();
            document.querySelectorAll('.device-user-row').forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });

        document.getElementById('deptFilter')?.addEventListener('change', function(e) {
            const dept = e.target.value.toLowerCase();
            document.querySelectorAll('.system-employee-row').forEach(row => {
                const rowDept = row.querySelector('.dept-cell').innerText.toLowerCase();
                row.style.display = (!dept || rowDept === dept) ? '' : 'none';
            });
        });

        // Select All
        document.getElementById('selectAllDevice')?.addEventListener('change', function(e) {
            document.querySelectorAll('.device-checkbox').forEach(cb => cb.checked = e.target.checked);
        });
        document.getElementById('selectAllSystem')?.addEventListener('change', function(e) {
            document.querySelectorAll('.system-checkbox').forEach(cb => cb.checked = e.target.checked);
        });

        // AJAX Form Handler (Universal)
        document.querySelectorAll('.ajax-form').forEach(form => {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const confirmMsg = this.getAttribute('data-confirm');
                if (confirmMsg) {
                    const res = await Swal.fire({
                        title: 'Are you sure?',
                        text: confirmMsg,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, proceed!',
                        customClass: { confirmButton: 'bg-red-600', cancelButton: 'bg-gray-100 text-gray-800' }
                    });
                    if (!res.isConfirmed) return;
                }

                const btn = this.querySelector('button');
                const originalHtml = btn ? btn.innerHTML : '';
                if(btn) {
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
                    btn.disabled = true;
                }

                try {
                    const response = await fetch(this.action, {
                        method: 'POST',
                        body: new FormData(this),
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    });
                    const result = await response.json();
                    
                    if(result.success) {
                        Swal.fire({ icon: 'success', title: 'Task Complete', text: result.message, timer: 1500, showConfirmButton: false })
                            .then(() => window.location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Action Failed', text: result.message });
                    }
                } catch(err) {
                    Swal.fire({ icon: 'error', title: 'System Error', text: 'Connection lost or server error.' });
                } finally {
                    if (btn) { btn.innerHTML = originalHtml; btn.disabled = false; }
                }
            });
        });

        // Helper for single actions
        async function triggerAction(url, data, confirmText) {
            const res = await Swal.fire({
                title: 'Confirm Action',
                text: confirmText,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Confirm'
            });
            if (!res.isConfirmed) return;

            Swal.fire({ title: 'Processing...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            for(let key in data) formData.append(key, data[key]);

            try {
                const response = await fetch(url, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                const result = await response.json();
                if(result.success) {
                    Swal.fire('Success!', result.message, 'success').then(() => window.location.reload());
                } else {
                    Swal.fire('Failed', result.message, 'error');
                }
            } catch(e) {
                Swal.fire('Error', 'Network or server error', 'error');
            }
        }
    </script>

    <style>
        .tab-btn.border-blue-600 { color: #2563eb; }
        .tab-btn.text-gray-400 { border-color: transparent; }
        .tab-btn:hover { color: #1e40af; }
    </style>
@endif

@endsection
