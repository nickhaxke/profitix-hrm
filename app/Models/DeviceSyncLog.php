<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceSyncLog extends Model
{
    protected $fillable = ['device_id', 'sync_type', 'records_count', 'status', 'error_message', 'started_at', 'completed_at'];

    protected $casts = ['started_at' => 'datetime', 'completed_at' => 'datetime'];
}
