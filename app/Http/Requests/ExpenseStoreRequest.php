<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseStoreRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'category' => 'required|string|max:255',
            'title'    => 'required|string|max:255',
            'amount'   => 'required|numeric|min:0.01',
            'date'     => 'required|date',
            'payment_mode' => 'required|string|in:Cash,UPI,Cheque,Card,Bank Transfer,Online,Other',
            'reference_number' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ];
    }
}
