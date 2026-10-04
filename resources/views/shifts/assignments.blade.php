@extends('layouts.app')
@section('title', 'Shift Assignments')
@section('content')
<div class="mb-6 flex justify-between items-end">
    <div>
        <p class="text-sm text-gray-500">Assign multiple shifts to employees for flexible or rotating schedules.</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('shifts.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
            <i class="fas fa-list mr-1"></i> Manage Shifts
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Assignment Form --}}
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl border shadow-sm p-6 sticky top-6">
            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-user-plus text-blue-500"></i>
                New Assignment
            </h3>
            <form method="POST" action="{{ route('shifts.assignments.store') }}">
                @csrf
                <div class="space-y-4">
                    {{-- Multi-Select Shifts --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Select Shifts *</label>
                        <div class="grid grid-cols-1 gap-2 max-h-48 overflow-y-auto p-2 border rounded-lg bg-gray-50">
                            @foreach($shifts as $s)
                            <label class="flex items-center gap-2 p-2 hover:bg-white rounded cursor-pointer transition-colors">
                                <input type="checkbox" name="shift_ids[]" value="{{ $s->id }}" class="rounded text-blue-600">
                                <span class="text-sm font-medium text-gray-700">{{ $s->name }}</span>
                                <span class="text-[10px] text-gray-400 ml-auto">{{ date('H:i', strtotime($s->start_time)) }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Effective Dates --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">From *</label>
                            <input type="date" name="effective_from" required value="{{ now()->toDateString() }}" class="w-full px-3 py-2 border rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">To</label>
                            <input type="date" name="effective_to" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="Optional">
                        </div>
                    </div>

                    <hr class="border-gray-100">

                    {{-- Bulk Employee Selection Info --}}
                    <div class="bg-blue-50 p-3 rounded-lg border border-blue-100">
                        <p class="text-[11px] text-blue-600 leading-relaxed">
                            <i class="fas fa-info-circle mr-1"></i>
                            Select employees from the table on the right, then click "Assign Selected".
                        </p>
                    </div>

                    <button type="submit" id="submitBtn" disabled class="w-full py-3 bg-blue-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-blue-200 disabled:opacity-50 disabled:shadow-none transition-all hover:bg-blue-700">
                        Assign to Selected Employees
                    </button>
                    
                    {{-- Hidden container for selected employee IDs --}}
                    <div id="selectedEmployeesContainer"></div>
                </div>
            </form>
        </div>
    </div>

    {{-- Employee Table --}}
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
            {{-- Table Filter Header --}}
            <div class="p-4 border-b bg-gray-50/50 flex flex-wrap gap-3 items-center justify-between">
                <form class="flex gap-2 flex-1 max-w-md">
                    <div class="relative flex-1">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search employees..." class="w-full pl-9 pr-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-100">
                    </div>
                    <select name="department_id" onchange="this.form.submit()" class="px-3 py-2 border rounded-lg text-sm bg-white">
                        <option value="">All Departments</option>
                        @foreach($departments as $d)
                        <option value="{{ $d->id }}" {{ request('department_id')==$d->id?'selected':'' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </form>
                <div class="text-xs text-gray-500 font-medium">
                    <span id="selectedCount">0</span> employees selected
                </div>
            </div>

            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left w-10">
                            <input type="checkbox" id="selectAll" class="rounded text-blue-600">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Employee</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Active Shifts</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($employees as $emp)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-4 py-3">
                            <input type="checkbox" name="emp_check" value="{{ $emp->id }}" class="emp-checkbox rounded text-blue-600">
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-800">{{ $emp->full_name }}</div>
                            <div class="text-[10px] text-gray-400">{{ $emp->department->name ?? 'No Dept' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @forelse($emp->shifts as $s)
                                <div class="group relative flex items-center gap-1.5 px-2 py-1 bg-gray-100 text-gray-600 rounded-lg text-[10px] font-bold border border-gray-200">
                                    <i class="fas fa-clock text-blue-400"></i>
                                    {{ $s->name }}
                                    <form method="POST" action="{{ route('shifts.assignments.destroy', [$emp, $s]) }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="hover:text-red-500 transition-colors">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                                @empty
                                <span class="text-gray-400 italic text-[10px]">No shift assigned (Using Default)</span>
                                @endforelse
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.emp-checkbox');
        const submitBtn = document.getElementById('submitBtn');
        const selectedCount = document.getElementById('selectedCount');
        const container = document.getElementById('selectedEmployeesContainer');

        function updateSelection() {
            let count = 0;
            container.innerHTML = '';
            checkboxes.forEach(cb => {
                if(cb.checked) {
                    count++;
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'employee_ids[]';
                    input.value = cb.value;
                    container.appendChild(input);
                }
            });
            selectedCount.textContent = count;
            submitBtn.disabled = count === 0;
        }

        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateSelection();
        });

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateSelection);
        });
    });
</script>
@endsection
