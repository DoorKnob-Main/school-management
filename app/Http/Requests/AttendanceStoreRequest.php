<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user()->can('take attendances');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'course_id'             => 'integer',
            'class_id'              => 'integer',
            'section_id'            => 'integer',
            'attendance_date'       => 'nullable|date|before_or_equal:today',
            'student_ids'           => 'required|array|min:1',
            'student_ids.*'         => 'integer',
            'status'                => 'nullable|array',
            'status.*'              => 'in:on,off,late,on_leave',
            'session_id'            => 'required',
        ];
    }
}
