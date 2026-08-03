<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CollectFeeRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'student_id' => 'required|exists:users,id',
            'session_id' => 'required|exists:school_sessions,id',
            'class_id'   => 'required|exists:school_classes,id',
            'amount'     => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_mode' => 'required|string|in:Cash,UPI,Cheque,Card,Bank Transfer,Online,Other',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];
    }
}
