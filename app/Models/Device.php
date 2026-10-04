<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
        'name', 'ip_address', 'port', 'serial_number', 'device_model',
        'location', 'branch_id', 'communication_type', 'is_online',
        'is_active', 'status', 'last_sync', 'last_ping', 'pending_command',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'is_active' => 'boolean',
        'last_sync' => 'datetime',
        'last_ping' => 'datetime',
    ];

    /**
     * BRIDGE STATUS: Checks if the Python Bridge is currently running and syncing.
     */
    public function getBridgeOnlineAttribute()
    {
        if (! $this->last_sync) {
            return false;
        }

        return $this->last_sync->diffInMinutes(now()) <= 5;
    }

    /**
     * HARDWARE STATUS: Checks if the actual biometric machine is connected to the bridge.
     */
    public function getHardwareOnlineAttribute()
    {
        return (bool) $this->is_online;
    }

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function syncLogs()
    {
        return $this->hasMany(DeviceSyncLog::class);
    }
}
