<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HospitalShiftsSeeder extends Seeder
{
    public function run()
    {
        $shifts = [
            [
                'name' => 'Pharmacy/Lab/Reception - Morning',
                'start_time' => '07:30:00',
                'end_time' => '18:45:00',
                'grace_minutes' => 15,
                'required_hours' => 11.25,
                'half_day_hours' => 5.5,
                'crosses_midnight' => 0,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pharmacy/Lab/Reception - Night',
                'start_time' => '18:45:00',
                'end_time' => '07:30:00',
                'grace_minutes' => 15,
                'required_hours' => 12.75,
                'half_day_hours' => 6,
                'crosses_midnight' => 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Doctors - Morning',
                'start_time' => '07:30:00',
                'end_time' => '19:00:00',
                'grace_minutes' => 15,
                'required_hours' => 11.5,
                'half_day_hours' => 5.5,
                'crosses_midnight' => 0,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Doctors - Night',
                'start_time' => '18:45:00',
                'end_time' => '07:30:00',
                'grace_minutes' => 15,
                'required_hours' => 12.75,
                'half_day_hours' => 6,
                'crosses_midnight' => 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Nurses - Standard Shift',
                'start_time' => '07:30:00',
                'end_time' => '16:30:00',
                'grace_minutes' => 15,
                'required_hours' => 9,
                'half_day_hours' => 4.5,
                'crosses_midnight' => 0,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($shifts as $shift) {
            DB::table('shifts')->updateOrInsert(
                ['name' => $shift['name']],
                $shift
            );
        }
    }
}
