<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = ['name', 'start_time', 'end_time', 'crosses_midnight', 'grace_minutes', 'late_threshold_minutes', 'required_hours', 'half_day_hours', 'is_default', 'is_active'];

    protected $casts = [
        'crosses_midnight' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'grace_minutes' => 'integer',
        'late_threshold_minutes' => 'integer',
        'required_hours' => 'float',
        'half_day_hours' => 'float',
    ];
}
