<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'channel',
        'message_template',
        'created_by',
    ];

    public function session()
    {
        return $this->belongsTo(SchoolSession::class, 'session_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients()
    {
        return $this->hasMany(FeeReminderRecipient::class, 'fee_reminder_id');
    }
}
