<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    protected $fillable = [
        'license_key', 'organization_name', 'client_name', 'client_email', 'bound_domain', 'bound_ip',
        'machine_id', 'max_employees', 'max_devices', 'plan', 'status', 'activated_at',
        'expires_at', 'last_heartbeat', 'activation_response',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_heartbeat' => 'datetime',
    ];

    public function isActive(): bool
    {
        return $this->status === 'active' && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
