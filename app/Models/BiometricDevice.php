<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BiometricDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'device_identifier',
        'ip_address',
        'port',
        'machine_number',
        'communication_password',
        'location',
        'description',
        'is_active',
        'status',
        'last_connected_at',
        'last_sync_at',
        'last_error',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'port' => 'integer',
        'machine_number' => 'integer',
        'last_connected_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    protected $hidden = [
        'communication_password',
    ];

    public function userMappings()
    {
        return $this->hasMany(BiometricDeviceUserMapping::class, 'device_id');
    }

    public function punchLogs()
    {
        return $this->hasMany(BiometricPunchLog::class, 'device_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
