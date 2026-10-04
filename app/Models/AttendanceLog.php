<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    protected $fillable = ['employee_id', 'device_id', 'punch_time', 'punch_type', 'source', 'raw_data', 'is_processed'];

    protected $casts = ['punch_time' => 'datetime', 'is_processed' => 'boolean'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
