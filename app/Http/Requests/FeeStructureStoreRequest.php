<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FeeStructureStoreRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'session_id' => 'required|exists:school_sessions,id',
            'class_id' => 'nullable|exists:school_classes,id',
            'total_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'installments' => 'nullable|array',
            'installments.*.name' => 'required_with:installments|string|max:255',
            'installments.*.amount' => 'required_with:installments|numeric|min:0',
            'installments.*.due_date' => 'nullable|date',
        ];
    }
}
