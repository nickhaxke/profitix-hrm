@extends('layouts.app')
@section('title', isset($employee) ? 'Edit Employee' : 'Add Employee')
@section('content')
<div class="max-w-3xl">
    <div class="bg-white rounded-xl border shadow-sm p-6">
        <form method="POST" action="{{ isset($employee) ? route('employees.update', $employee) : route('employees.store') }}">
            @csrf
            @if(isset($employee)) @method('PUT') @endif
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Employee Code *</label><input type="text" name="employee_code" value="{{ old('employee_code', $employee->employee_code ?? '') }}" required class="w-full px-3 py-2 border rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label><input type="text" name="full_name" value="{{ old('full_name', $employee->full_name ?? '') }}" required class="w-full px-3 py-2 border rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Email</label><input type="email" name="email" value="{{ old('email', $employee->email ?? '') }}" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Phone</label><input type="text" name="phone" value="{{ old('phone', $employee->phone ?? '') }}" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Department</label><select name="department_id" class="w-full px-3 py-2 border rounded-lg text-sm"><option value="">Select</option>@foreach($departments as $d)<option value="{{ $d->id }}" {{ old('department_id', $employee->department_id ?? '')==$d->id?'selected':'' }}>{{ $d->name }}</option>@endforeach</select></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Position</label><input type="text" name="position" value="{{ old('position', $employee->position ?? '') }}" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Join Date *</label><input type="date" name="join_date" value="{{ old('join_date', isset($employee) ? $employee->join_date->format('Y-m-d') : date('Y-m-d')) }}" required class="w-full px-3 py-2 border rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Biometric ID</label><input type="text" name="biometric_id" value="{{ old('biometric_id', $employee->biometric_id ?? '') }}" class="w-full px-3 py-2 border rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Gender</label><select name="gender" class="w-full px-3 py-2 border rounded-lg text-sm"><option value="">Select</option><option value="male" {{ old('gender', $employee->gender ?? '')=='male'?'selected':'' }}>Male</option><option value="female" {{ old('gender', $employee->gender ?? '')=='female'?'selected':'' }}>Female</option></select></div>
                @if(isset($employee))<div><label class="block text-sm font-medium text-gray-700 mb-1">Status</label><select name="status" class="w-full px-3 py-2 border rounded-lg text-sm"><option value="active" {{ $employee->status=='active'?'selected':'' }}>Active</option><option value="inactive" {{ $employee->status=='inactive'?'selected':'' }}>Inactive</option></select></div>@endif
            </div>
            <div class="mt-4"><label class="block text-sm font-medium text-gray-700 mb-1">Address</label><textarea name="address" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm">{{ old('address', $employee->address ?? '') }}</textarea></div>
            <div class="mt-6 flex gap-3">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700"><i class="fas fa-save mr-1"></i> Save</button>
                <a href="{{ route('employees.index') }}" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
