<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CollectFeeRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        $paymentModes = array_filter(array_map('trim', explode(',', setting('finance_payment_modes', 'Cash,UPI,Cheque,Card,Bank Transfer,Online,Other'))));
        $referenceRequiredModes = array_filter(array_map('trim', explode(',', setting('finance_require_reference_for_modes', ''))));

        return [
            'student_id' => 'required|exists:users,id',
            'session_id' => 'required|exists:school_sessions,id',
            'class_id'   => 'required|exists:school_classes,id',
            'amount'     => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_mode' => ['required', 'string', Rule::in($paymentModes)],
            'reference_number' => [
                in_array($this->input('payment_mode'), $referenceRequiredModes) ? 'required' : 'nullable',
                'string',
                'max:255',
            ],
            'notes' => 'nullable|string',
        ];
    }
}
