@extends('layouts.app')
@section('title', 'Reports')
@section('content')
<div class="bg-white rounded-xl border shadow-sm p-5 mb-6 no-print">
    <form class="flex flex-wrap gap-3 items-end" method="GET" id="reportForm">
        <div><label class="block text-xs font-medium text-gray-500 mb-1">Report Type</label>
            <select name="type" class="px-3 py-2 border rounded-lg text-sm" onchange="this.form.submit()">
                <option value="daily" {{ $type=='daily'?'selected':'' }}>Daily</option>
                <option value="monthly" {{ $type=='monthly'?'selected':'' }}>Monthly Summary</option>
                <option value="personal" {{ $type=='personal'?'selected':'' }}>Personal Report</option>
                <option value="department" {{ $type=='department'?'selected':'' }}>By Department</option>
            </select>
        </div>
        <div><label class="block text-xs font-medium text-gray-500 mb-1">From</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="px-3 py-2 border rounded-lg text-sm"></div>
        <div><label class="block text-xs font-medium text-gray-500 mb-1">To</label><input type="date" name="date_to" value="{{ $dateTo }}" class="px-3 py-2 border rounded-lg text-sm"></div>
        
        @if($type === 'personal')
        <div><label class="block text-xs font-medium text-gray-500 mb-1">Employee</label>
            <select name="employee_id" class="px-3 py-2 border rounded-lg text-sm">
                <option value="">Select Employee</option>
                @foreach($employees as $e)
                    <option value="{{ $e->id }}" {{ $employeeId==$e->id?'selected':'' }}>{{ $e->full_name }}</option>
                @endforeach
            </select>
        </div>
        @else
        <div><label class="block text-xs font-medium text-gray-500 mb-1">Branch</label>
            <select name="branch_id" class="px-3 py-2 border rounded-lg text-sm" onchange="this.form.submit()">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ $branchId==$b->id?'selected':'' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="block text-xs font-medium text-gray-500 mb-1">Department</label>
            <select name="department_id" class="px-3 py-2 border rounded-lg text-sm" onchange="this.form.submit()">
                <option value="">All Departments</option>
                @foreach($departments as $d)
                    <option value="{{ $d->id }}" {{ $deptId==$d->id?'selected':'' }}>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm"><i class="fas fa-chart-bar mr-1"></i> Generate Report</button>
        
        @if($type === 'personal' && $employeeId && count($data) > 0)
        <button type="button" onclick="window.print()" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm"><i class="fas fa-print mr-1"></i> Print Form</button>
        @endif

        <div class="flex gap-2 ml-auto">
            @php
                $startD = \Carbon\Carbon::parse($dateFrom);
                $endD = \Carbon\Carbon::parse($dateTo);
                $monthsDiff = $startD->diffInMonths($endD);
            @endphp
            
            <a href="{{ route('exports.report', array_merge(request()->all(), ['format' => 'csv'])) }}" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm"><i class="fas fa-file-csv mr-1"></i> Export CSV</a>
            
            @if($monthsDiff >= 1)
            <a href="{{ route('exports.report', array_merge(request()->all(), ['format' => 'excel_split'])) }}" class="px-4 py-2 bg-emerald-700 text-white rounded-lg text-sm" title="Export to Excel (Each month in a separate sheet)"><i class="fas fa-file-excel mr-1"></i> Excel (Monthly Sheets)</a>
            @else
            <a href="{{ route('exports.report', array_merge(request()->all(), ['format' => 'excel_split'])) }}" class="px-4 py-2 bg-emerald-700 text-white rounded-lg text-sm"><i class="fas fa-file-excel mr-1"></i> Export Excel</a>
            @endif

            <a href="{{ route('exports.report', array_merge(request()->all(), ['format' => 'pdf'])) }}" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm"><i class="fas fa-file-pdf mr-1"></i> Export PDF</a>
            <a href="#" onclick="event.preventDefault(); document.getElementById('bulkActions').classList.toggle('hidden')" class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm"><i class="fas fa-layer-group mr-1"></i> Bulk</a>
        </div>
    </form>
    
    <div id="bulkActions" class="mt-4 pt-4 border-t flex gap-2 hidden">
        <a href="{{ route('exports.bulk-reports', request()->all()) }}" class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm"><i class="fas fa-file-archive mr-1"></i> Bulk PDF Export</a>
        <form method="POST" action="{{ route('exports.email-reports') }}">
            @csrf
            <input type="hidden" name="date_from" value="{{ $dateFrom }}">
            <input type="hidden" name="date_to" value="{{ $dateTo }}">
            <button type="submit" onclick="return confirm('Send email reports to all employees?')" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm"><i class="fas fa-envelope mr-1"></i> Bulk Email</button>
        </form>
    </div>
</div>

@if($type === 'daily')
<div class="bg-white rounded-xl border shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50"><tr>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Employee</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Dept</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">In</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Out</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Hours</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
        </tr></thead>
        <tbody class="divide-y">
        @forelse($data as $r)
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-2 text-sm font-mono">{{ $r->summary_date->format('Y-m-d') }}</td>
            <td class="px-4 py-2 text-sm font-medium">{{ $r->employee->full_name }}</td>
            <td class="px-4 py-2 text-sm text-gray-500">{{ $r->employee->department->name ?? '-' }}</td>
            <td class="px-4 py-2 text-sm font-mono text-green-600">{{ $r->check_in_time ?? '-' }}</td>
            <td class="px-4 py-2 text-sm font-mono text-red-600">{{ $r->check_out_time ?? '-' }}</td>
            <td class="px-4 py-2 text-sm font-semibold">{{ number_format($r->total_hours, 1) }}h</td>
            <td class="px-4 py-2"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $r->status==='present'?'bg-green-100 text-green-800':($r->status==='late'?'bg-amber-100 text-amber-800':($r->status==='absent'?'bg-red-100 text-red-800':($r->status==='leave'?'bg-blue-100 text-blue-800':($r->status==='holiday'?'bg-indigo-100 text-indigo-800':'bg-gray-100')))) }}">{{ ucfirst(str_replace('_',' ',$r->status)) }}</span></td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-12 text-center text-gray-400">No data. Process attendance first.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

@elseif($type === 'department')
<div class="bg-white rounded-xl border shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50"><tr>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Department</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Employees</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Present</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Late</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Absent</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">On Leave</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Holiday</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Avg Hours</th>
        </tr></thead>
        <tbody class="divide-y">
        @foreach($data as $r)
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm font-semibold">{{ $r['department'] }}</td>
            <td class="px-4 py-3 text-sm">{{ $r['employees'] }}</td>
            <td class="px-4 py-3 text-sm text-green-600 font-semibold">{{ $r['present'] }}</td>
            <td class="px-4 py-3 text-sm text-amber-600">{{ $r['late'] }}</td>
            <td class="px-4 py-3 text-sm text-red-600">{{ $r['absent'] }}</td>
            <td class="px-4 py-3 text-sm text-blue-600">{{ $r['on_leave'] }}</td>
            <td class="px-4 py-3 text-sm text-indigo-600">{{ $r['holiday'] }}</td>
            <td class="px-4 py-3 text-sm">{{ $r['avg_hours'] }}h</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>

@elseif($type === 'monthly')
<div class="bg-white rounded-xl border shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50"><tr>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Employee</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Dept</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Present</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Late</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Absent</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">On Leave</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Holiday</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Total Hrs</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Overtime</th>
        </tr></thead>
        <tbody class="divide-y">
        @foreach($data as $r)
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm font-semibold">{{ $r['employee'] }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ $r['department'] }}</td>
            <td class="px-4 py-3 text-sm text-green-600 font-semibold">{{ $r['present'] }}</td>
            <td class="px-4 py-3 text-sm text-amber-600">{{ $r['late'] }}</td>
            <td class="px-4 py-3 text-sm text-red-600">{{ $r['absent'] }}</td>
            <td class="px-4 py-3 text-sm text-blue-600">{{ $r['on_leave'] }}</td>
            <td class="px-4 py-3 text-sm text-indigo-600">{{ $r['holiday'] }}</td>
            <td class="px-4 py-3 text-sm font-semibold">{{ $r['total_hours'] }}h</td>
            <td class="px-4 py-3 text-sm text-blue-600">{{ $r['overtime'] }}h</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>

@elseif($type === 'personal')
    @if($employeeId && count($data) > 0)
    @php $employee = $data->first()->employee; @endphp
    <div class="print-container">
        {{-- Print Only Header --}}
        <div class="print-header hidden mb-8 text-center border-b-2 border-gray-900 pb-6">
            <h1 class="text-3xl font-black tracking-tighter uppercase mb-1">{{ config('app.name', 'PROFITIX HRM') }}</h1>
            <p class="text-sm font-medium text-gray-600">Employee Monthly Attendance & Overtime Record</p>
            <div class="flex justify-between mt-8 text-left border-t border-gray-100 pt-4">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-bold">Employee Details</p>
                    <p class="text-lg font-bold">{{ $employee->full_name }}</p>
                    <p class="text-sm">{{ $employee->employee_code }} | {{ $employee->department->name ?? '-' }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500 uppercase font-bold">Report Period</p>
                    <p class="text-lg font-bold">{{ Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} - {{ Carbon\Carbon::parse($dateTo)->format('M d, Y') }}</p>
                    <p class="text-xs text-gray-400 mt-1 italic">Generated on: {{ now()->format('Y-m-d H:i') }}</p>
                </div>
            </div>
        </div>

        {{-- Dashboard Summary Cards (No-Print) --}}
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6 no-print">
            <div class="bg-blue-600 text-white p-4 rounded-2xl shadow-sm">
                <p class="text-xs opacity-75 font-semibold uppercase">Present Days</p>
                <p class="text-3xl font-bold">{{ $data->whereIn('status', ['present', 'late', 'missing_checkout'])->count() }}</p>
            </div>
            <div class="bg-indigo-600 text-white p-4 rounded-2xl shadow-sm">
                <p class="text-xs opacity-75 font-semibold uppercase">Total Hours</p>
                <p class="text-3xl font-bold">{{ number_format($data->sum('total_hours'), 1) }}h</p>
            </div>
            <div class="bg-green-600 text-white p-4 rounded-2xl shadow-sm">
                <p class="text-xs opacity-75 font-semibold uppercase">Overtime (OT)</p>
                <p class="text-3xl font-bold text-green-100">{{ number_format($data->sum('overtime_hours'), 1) }}h</p>
            </div>
            <div class="bg-amber-500 text-white p-4 rounded-2xl shadow-sm">
                <p class="text-xs opacity-75 font-semibold uppercase">Late Days</p>
                <p class="text-3xl font-bold">{{ $data->where('is_late', true)->count() }}</p>
            </div>
            <div class="bg-red-600 text-white p-4 rounded-2xl shadow-sm">
                <p class="text-xs opacity-75 font-semibold uppercase">Absent Days</p>
                <p class="text-3xl font-bold">{{ $data->where('status', 'absent')->count() }}</p>
            </div>
            <div class="bg-teal-600 text-white p-4 rounded-2xl shadow-sm">
                <p class="text-xs opacity-75 font-semibold uppercase">On Leave</p>
                <p class="text-3xl font-bold">{{ $data->where('status', 'leave')->count() }}</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border shadow-sm overflow-hidden print-border">
            <table class="w-full border-collapse">
                <thead class="bg-gray-900 text-white"><tr>
                    <th class="text-left px-4 py-4 text-xs font-bold uppercase tracking-wider">Date</th>
                    <th class="text-left px-4 py-4 text-xs font-bold uppercase tracking-wider">Shift Type</th>
                    <th class="text-left px-4 py-4 text-xs font-bold uppercase tracking-wider">Punch In</th>
                    <th class="text-left px-4 py-4 text-xs font-bold uppercase tracking-wider">Punch Out</th>
                    <th class="text-left px-4 py-4 text-xs font-bold uppercase tracking-wider">Work Hrs</th>
                    <th class="text-left px-4 py-4 text-xs font-bold uppercase tracking-wider">Overtime</th>
                    <th class="text-left px-4 py-4 text-xs font-bold uppercase tracking-wider text-right">Status</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                @foreach($data as $r)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-sm font-bold text-gray-700">{{ $r->summary_date->format('D, M d') }}</td>
                    <td class="px-4 py-3 text-xs text-gray-500 uppercase tracking-tighter">{{ $r->shift->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-sm font-mono text-green-600 font-bold">{{ $r->check_in_time ? date('h:i A', strtotime($r->check_in_time)) : '--:--' }}</td>
                    <td class="px-4 py-3 text-sm font-mono text-red-600 font-bold">{{ $r->check_out_time ? date('h:i A', strtotime($r->check_out_time)) : '--:--' }}</td>
                    <td class="px-4 py-3 text-sm font-black text-gray-800">{{ number_format($r->total_hours, 1) }}h</td>
                    <td class="px-4 py-3 text-sm font-bold text-blue-600 bg-blue-50/50">
                        @if($r->overtime_hours > 0) +{{ number_format($r->overtime_hours, 1) }}h @else - @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase {{ $r->status==='present'?'bg-green-100 text-green-800':($r->status==='late'?'bg-amber-100 text-amber-800':($r->status==='absent'?'bg-red-100 text-red-800':($r->status==='leave'?'bg-blue-100 text-blue-800':($r->status==='holiday'?'bg-indigo-100 text-indigo-800':'bg-gray-100')))) }}">
                            {{ str_replace('_',' ',$r->status) }}
                        </span>
                    </td>
                </tr>
                @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                    <tr class="font-black text-gray-900">
                        <td colspan="4" class="px-4 py-4 text-right uppercase text-xs">Monthly Totals:</td>
                        <td class="px-4 py-4 text-lg">{{ number_format($data->sum('total_hours'), 1) }}h</td>
                        <td class="px-4 py-4 text-lg text-blue-700 font-black">+{{ number_format($data->sum('overtime_hours'), 1) }}h OT</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-16 hidden print-block">
            <div class="flex justify-between px-10">
                <div class="text-center w-64">
                    <div class="border-b-2 border-gray-900 h-16"></div>
                    <p class="text-xs font-bold uppercase mt-2">Employee's Acknowledgement</p>
                    <p class="text-[10px] text-gray-400 mt-1">Date: ________________</p>
                </div>
                <div class="text-center w-64">
                    <div class="border-b-2 border-gray-900 h-16"></div>
                    <p class="text-xs font-bold uppercase mt-2">Authorized HR Signature</p>
                    <p class="text-[10px] text-gray-400 mt-1">Official Stamp</p>
                </div>
            </div>
            <div class="mt-12 text-center">
                <p class="text-[9px] text-gray-400 uppercase tracking-widest italic">This is a system-generated document from {{ config('app.name') }}</p>
            </div>
        </div>
    </div>
    @elseif($employeeId)
    <div class="bg-white p-12 text-center rounded-xl border border-dashed text-gray-400">
        No attendance data found for this employee in the selected period.
    </div>
    @else
    <div class="bg-white p-12 text-center rounded-xl border border-dashed text-gray-400">
        Please select an employee to generate a personal report.
    </div>
    @endif
@endif

<style>
@media print {
    /* Hide everything that is NOT the report container */
    nav, aside, footer, header, .navbar, .sidebar, .sidebar-wrapper, .main-header, .no-print, .btn, .alert, .breadcrumb, #reportForm, .navbar-custom, .main-sidebar {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
        overflow: hidden !important;
    }
    
    /* Reset main layout containers to be transparent/invisible */
    body, .wrapper, .main-content, .content-wrapper, .content {
        background: transparent !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        width: 100% !important;
    }

    /* Target the actual report box to fill the page */
    .print-container {
        display: block !important;
        width: 100% !important;
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
    }

    .print-header { display: block !important; }
    .print-block { display: block !important; }

    table { width: 100% !important; border-collapse: collapse !important; border: 2px solid #000 !important; }
    th { background-color: #000 !important; color: white !important; -webkit-print-color-adjust: exact; padding: 10px !important; }
    td, th { border: 1px solid #000 !important; padding: 8px !important; }
    
    @page { margin: 1cm; size: portrait; }
}
</style>
@endsection
