<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FeeReminderSendRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'session_id' => 'required|exists:school_sessions,id',
            'channel' => 'required|string|in:SMS,WhatsApp,Both',
            'message_template' => 'required|string',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:users,id',
        ];
    }
}
