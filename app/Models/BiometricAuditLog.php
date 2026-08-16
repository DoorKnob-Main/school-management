<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BiometricAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'details',
        'ip_address',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'details' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function log(string $action, array $details = [], ?int $userId = null): self
    {
        return self::create([
            'user_id' => $userId ?? (auth()->check() ? auth()->id() : null),
            'action' => $action,
            'details' => $details,
            'ip_address' => request()->ip() ?? '127.0.0.1',
        ]);
    }
}
