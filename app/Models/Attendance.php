<?php

namespace App\Models;

use App\Models\User;
use App\Models\Course;
use App\Models\Section;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'student_id',
        'class_id',
        'section_id',
        'session_id',
        'course_id',
        'status',
        'in_time',
        'out_time',
        'attendance_source',
        'late_minutes',
        'early_leave_minutes',
        'is_corrected',
        'corrected_by',
        'correction_reason',
        'remarks',
    ];

    protected $casts = [
        'in_time' => 'datetime',
        'out_time' => 'datetime',
        'is_corrected' => 'boolean',
        'late_minutes' => 'integer',
        'early_leave_minutes' => 'integer',
        'student_id' => 'integer',
        'class_id' => 'integer',
        'section_id' => 'integer',
        'session_id' => 'integer',
        'course_id' => 'integer',
    ];

    /**
     * Get the user who corrected this attendance record.
     */
    public function correctedBy()
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }

    /**
     * Get the student for attendances.
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the schoolClass for attendance.
     */
    public function schoolClass() {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Get the section for attendance.
     */
    public function section() {
        return $this->belongsTo(Section::class, 'section_id');
    }

    /**
     * Get the course for attendance.
     */
    public function course() {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
