<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeReminderRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_reminder_id',
        'student_id',
        'phone_used',
        'due_amount',
        'status',
        'provider_response',
    ];

    public function reminder()
    {
        return $this->belongsTo(FeeReminder::class, 'fee_reminder_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
