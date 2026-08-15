<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BiometricPunchLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'device_user_id',
        'student_id',
        'punch_time',
        'verify_type',
        'sensor_no',
        'raw_payload',
        'is_processed',
        'sync_batch',
    ];

    protected $casts = [
        'punch_time' => 'datetime',
        'is_processed' => 'boolean',
        'raw_payload' => 'array',
        'device_id' => 'integer',
        'device_user_id' => 'integer',
        'student_id' => 'integer',
        'sensor_no' => 'integer',
    ];

    public function device()
    {
        return $this->belongsTo(BiometricDevice::class, 'device_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function scopeProcessed($query)
    {
        return $query->where('is_processed', true);
    }

    public function scopeUnprocessed($query)
    {
        return $query->where('is_processed', false);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('punch_time', $date);
    }

    public function getVerifyTypeLabelAttribute(): string
    {
        $types = [
            '1' => 'Fingerprint',
            '2' => 'Password',
            '3' => 'RFID Card',
            '4' => 'Face Recognition',
            '5' => 'Fingerprint + Card',
        ];

        return $types[(string)$this->verify_type] ?? 'Biometric (' . $this->verify_type . ')';
    }
}
