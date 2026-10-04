<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\LeaveType;
use App\Models\License;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Default admin user
        User::create([
            'name' => 'System Admin',
            'email' => 'admin@profitix.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Default license (pending activation)
        License::create([
            'license_key' => 'PRFTX-TRIAL-00000-00000-00000',
            'client_name' => 'Trial Installation',
            'plan' => 'starter',
            'status' => 'active',
            'max_employees' => 10,
            'activated_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        // Default branch
        $branch = Branch::create(['name' => 'Head Office']);

        // Default departments
        Department::create(['name' => 'Administration', 'branch_id' => $branch->id]);
        Department::create(['name' => 'Human Resources', 'branch_id' => $branch->id]);
        Department::create(['name' => 'IT', 'branch_id' => $branch->id]);
        Department::create(['name' => 'Finance', 'branch_id' => $branch->id]);
        Department::create(['name' => 'Operations', 'branch_id' => $branch->id]);

        // Default shifts
        Shift::create([
            'name' => 'Morning Shift',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'grace_minutes' => 15,
            'late_threshold_minutes' => 30,
            'required_hours' => 8,
            'is_default' => true,
        ]);

        Shift::create([
            'name' => 'Night Shift',
            'start_time' => '20:00',
            'end_time' => '06:00',
            'crosses_midnight' => true,
            'grace_minutes' => 15,
            'late_threshold_minutes' => 30,
            'required_hours' => 8,
        ]);

        // Default leave types
        LeaveType::create(['name' => 'Annual Leave', 'max_days_per_year' => 21, 'is_paid' => true]);
        LeaveType::create(['name' => 'Sick Leave', 'max_days_per_year' => 14, 'is_paid' => true]);
        LeaveType::create(['name' => 'Maternity Leave', 'max_days_per_year' => 84, 'is_paid' => true]);
        LeaveType::create(['name' => 'Unpaid Leave', 'max_days_per_year' => 30, 'is_paid' => false]);

        // Default settings
        Setting::create(['key' => 'company_name', 'value' => 'My Company', 'group' => 'company']);
        Setting::create(['key' => 'company_address', 'value' => '', 'group' => 'company']);
        Setting::create(['key' => 'company_phone', 'value' => '', 'group' => 'company']);
        Setting::create(['key' => 'timezone', 'value' => 'Africa/Nairobi', 'group' => 'general']);
        Setting::create(['key' => 'date_format', 'value' => 'Y-m-d', 'group' => 'general']);
        Setting::create(['key' => 'work_start_time', 'value' => '08:00:00', 'group' => 'attendance']);
        Setting::create(['key' => 'work_end_time', 'value' => '17:00:00', 'group' => 'attendance']);
        Setting::create(['key' => 'grace_minutes', 'value' => '15', 'group' => 'attendance']);
    }
}
