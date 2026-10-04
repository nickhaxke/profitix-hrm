<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ \App\Models\Setting::getValue('company_name', 'Profitix HRM') }}</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚡</text></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        brand: { 50:'#f4f9f0', 100:'#e5f3dc', 200:'#cbe7bb', 300:'#a8d58f', 400:'#8cc63f', 500:'#75b32e', 600:'#5c9124', 700:'#48711e', 800:'#3c5a1b', 900:'#334a19' },
                        sidebar: { bg: '#222d32', hover: '#2c3b41', active: '#75b32e' },
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-link.active { background: linear-gradient(135deg, #8cc63f 0%, #75b32e 100%); }
        .sidebar-link:hover:not(.active) { background: #2c3b41; }
        .stat-card { transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .glass { background: rgba(255,255,255,0.8); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="flex min-h-screen">
        {{-- Sidebar Overlay for Mobile --}}
        <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-30 hidden md:hidden" onclick="toggleSidebar()"></div>

        {{-- Sidebar --}}
        <aside class="w-64 bg-sidebar-bg text-white flex flex-col fixed h-full z-40 transition-transform duration-300 transform -translate-x-full md:translate-x-0" id="sidebar">
            <div class="p-5 border-b border-white/10">
                <div class="flex items-center gap-3">
                    @if(\App\Models\Setting::getValue('company_logo'))
                        <img src="{{ asset('storage/' . \App\Models\Setting::getValue('company_logo')) }}" alt="Logo" class="w-10 h-10 object-contain bg-white rounded-xl p-1">
                    @else
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-lg font-bold">{{ substr(\App\Models\Setting::getValue('company_name', 'Profitix'), 0, 1) }}</div>
                    @endif
                    <div>
                        <h1 class="font-bold text-lg leading-tight">{{ \App\Models\Setting::getValue('company_name', 'Profitix') }}</h1>
                        <span class="text-xs text-brand-300">{{ \App\Models\Setting::getValue('company_slogan', 'HRM System') }}</span>
                    </div>
                </div>
            </div>
            <nav class="flex-1 py-4 overflow-y-auto">
                @if(auth()->user() && auth()->user()->role === 'superadmin')
                <div class="px-4 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Super Admin</div>
                <a href="{{ route('admin.licenses.index') }}" class="sidebar-link flex items-center gap-3 px-5 py-2.5 mx-2 rounded-lg text-sm {{ request()->routeIs('admin.licenses.*') ? 'active' : 'text-gray-300' }}">
                    <i class="fas fa-key w-5"></i> Licenses
                </a>
                @else
                <div class="px-4 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Main</div>
                <a href="{{ route('dashboard') }}" class="sidebar-link flex items-center gap-3 px-5 py-2.5 mx-2 rounded-lg text-sm {{ request()->routeIs('dashboard') ? 'active' : 'text-gray-300' }}">
                    <i class="fas fa-tachometer-alt w-5"></i> Dashboard
                </a>

                @if(in_array(auth()->user()->role, ['admin', 'hr']))
                <div class="px-4 mt-5 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Organization</div>
                <a href="{{ route('employees.index') }}" class="sidebar-link flex items-center gap-3 px-5 py-2.5 mx-2 rounded-lg text-sm {{ request()->routeIs('employees.*') ? 'active' : 'text-gray-300' }}">
                    <i class="fas fa-users w-5"></i> Employees
                </a>
                <a href="{{ route('departments.index') }}" class="sidebar-link flex items-center gap-3 px-5 py-2.5 mx-2 rounded-lg text-sm {{ request()->routeIs('departments.*') ? 'active' : 'text-gray-300' }}">
                    <i class="fas fa-building w-5"></i> Departments
                </a>
                <a href="{{ route('branches.index') }}" class="sidebar-link flex items-center gap-3 px-5 py-2.5 mx-2 rounded-lg text-sm {{ request()->routeIs('branches.*') ? 'active' : 'text-gray-300' }}">
                    <i class="fas fa-code-branch w-5"></i> Branches
                </a>
                <a href="{{ route('users.index') }}" class="sidebar-link flex items-center gap-3 px-5 py-2.5 mx-2 rounded-lg text-sm {{ request()->routeIs('users.*') ? 'active' : 'text-gray-300' }}">
                    <i class="fas fa-user-shield w-5"></i> System Users
                </a>
                @endif

                <div class="px-4 mt-5 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Attendance</div>
                <a href="{{ route('attendance.logs') }}" class="sidebar-link flex items-center gap-3 px-5 py-2.5 mx-2 rounded-lg text-sm {{ request()->routeIs('attendance.logs') ? 'active' : 'text-gray-300' }}">
                    <i class="fas fa-list-alt w-5"></i> Raw Logs
                </a>
                <a href="{{ route('attendance.summary') }}" class="sidebar-link flex items-center gap-3 px-5 py-2.5 mx-2 rounded-lg text-sm {{ request()->routeIs('attendance.summary') ? 'active' : 'text-gray-300' }}">
                    <i class="fas fa-calendar-check w-5"></i> Daily Summary
                </a>
                @if(in_array(auth()->user()->role, ['admin', 'hr']))
                <a href="{{ route('shifts.index') }}" class="sidebar-link flex items-center gap-3 px-5 py-2.5 mx-2 rounded-lg text-sm {{ request()->routeIs('shifts.*') ? 'active' : 'text-gray-300' }}">
                    <i class="fas fa-clock w-5"></i> Shifts
                </a>
                @endif

                <div class="px-4 mt-5 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">HR</div>
                <a href="{{ route('leave.index') }}" class="sidebar-link flex items-center gap-3 px-5 py-2.5 mx-2 rounded-lg text-sm {{ request()->routeIs('leave.*') ? 'active' : 'text-gray-300' }}">
                    <i class="fas fa-calendar-minus w-5"></i> Leave
                </a>
                @if(in_array(auth()->user()->role, ['admin', 'hr']))
                <a href="{{ route('holidays.index') }}" class="sidebar-link flex items-center gap-3 px-5 py-2.5 mx-2 rounded-lg text-sm {{ request()->routeIs('holidays.*') ? 'active' : 'text-gray-300' }}">
                    <i class="fas fa-umbrella-beach w-5"></i> Holidays
                </a>
                @endif
                <a href="{{ route('reports.index') }}" class="sidebar-link flex items-center gap-3 px-5 py-2.5 mx-2 rounded-lg text-sm {{ request()->routeIs('reports.*') ? 'active' : 'text-gray-300' }}">
                    <i class="fas fa-chart-bar w-5"></i> Reports
                </a>

                @if(auth()->user()->role === 'admin')
                <div class="px-4 mt-5 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">System</div>
                <a href="{{ route('devices.index') }}" class="sidebar-link flex items-center gap-3 px-5 py-2.5 mx-2 rounded-lg text-sm {{ request()->routeIs('devices.index') ? 'active' : 'text-gray-300' }}">
                    <i class="fas fa-microchip w-5"></i> Devices
                </a>
                <a href="{{ route('devices.users') }}" class="sidebar-link flex items-center gap-3 px-5 py-2.5 mx-2 rounded-lg text-sm {{ request()->routeIs('devices.users') ? 'active' : 'text-gray-300' }}">
                    <i class="fas fa-users-cog w-5"></i> Device Users
                </a>
                <a href="{{ route('audit.index') }}" class="sidebar-link flex items-center gap-3 px-5 py-2.5 mx-2 rounded-lg text-sm {{ request()->routeIs('audit.*') ? 'active' : 'text-gray-300' }}">
                    <i class="fas fa-history w-5"></i> Audit Logs
                </a>
                <a href="{{ route('backups.index') }}" class="sidebar-link flex items-center gap-3 px-5 py-2.5 mx-2 rounded-lg text-sm {{ request()->routeIs('backups.*') ? 'active' : 'text-gray-300' }}">
                    <i class="fas fa-database w-5"></i> Backups
                </a>
                <a href="{{ route('settings.index') }}" class="sidebar-link flex items-center gap-3 px-5 py-2.5 mx-2 rounded-lg text-sm {{ request()->routeIs('settings.*') ? 'active' : 'text-gray-300' }}">
                    <i class="fas fa-cog w-5"></i> Settings
                </a>
                @endif
                @endif
            </nav>
            <div class="p-4 border-t border-white/10">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-brand-600 flex items-center justify-center text-sm font-bold">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium truncate">{{ auth()->user()->name ?? 'User' }}</div>
                        <div class="text-xs text-gray-400">{{ ucfirst(auth()->user()->role ?? 'admin') }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-white" title="Logout">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Main Content --}}
        <main class="flex-1 w-full md:ml-64 transition-all duration-300">
            <header class="bg-white border-b px-4 sm:px-6 py-4 flex items-center justify-between sticky top-0 z-20 shadow-sm">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" class="md:hidden text-gray-500 hover:text-brand-500 transition">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h2 class="text-xl font-semibold text-gray-800">@yield('title', 'Dashboard')</h2>
                </div>
                <div class="text-sm text-gray-500 hidden sm:block">{{ now()->format('l, F j, Y') }}</div>
            </header>

            <div class="p-6">
                {{-- Flash messages --}}
                @if(session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-center gap-2">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                </div>
                @endif
                @if(session('error'))
                <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                </div>
                @endif
                @if($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg">
                    <ul class="list-disc pl-5">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }
    </script>
</body>
</html>
