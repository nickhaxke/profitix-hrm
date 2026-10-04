<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSummary extends Model
{
    protected $fillable = ['employee_id', 'summary_date', 'shift_id', 'check_in_time', 'check_out_time', 'total_hours', 'overtime_hours', 'late_minutes', 'early_out_minutes', 'status', 'is_late', 'is_overnight', 'remarks'];

    protected $casts = ['summary_date' => 'date', 'is_late' => 'boolean', 'is_overnight' => 'boolean'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
