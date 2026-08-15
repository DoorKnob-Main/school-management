<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BiometricDeviceUserMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'device_id',
        'device_user_id',
        'enrollment_status',
        'enrolled_at',
    ];

    protected $casts = [
        'student_id' => 'integer',
        'device_id' => 'integer',
        'device_user_id' => 'integer',
        'enrolled_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function device()
    {
        return $this->belongsTo(BiometricDevice::class, 'device_id');
    }
}
