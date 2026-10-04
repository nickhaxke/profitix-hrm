<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicHoliday extends Model
{
    protected $fillable = ['name', 'holiday_date', 'is_recurring'];

    protected $casts = ['holiday_date' => 'date', 'is_recurring' => 'boolean'];
}
