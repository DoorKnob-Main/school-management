<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Routine extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'start',
        'end',
        'weekday',
        'class_id',
        'section_id',
        'course_id',
        'session_id',
    ];

    /**
     * Get the schoolClass.
     */
    public function schoolClass() {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Get the section.
     */
    public function section() {
        return $this->belongsTo(Section::class, 'section_id');
    }

    /**
     * Get the course.
     */
    public function course() {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Get the assigned teacher for this routine.
     */
    public function assignedTeacher() {
        return $this->hasOne(AssignedTeacher::class, 'course_id', 'course_id')
            ->where('session_id', $this->session_id)
            ->where('class_id', $this->class_id)
            ->where(function($q) {
                $q->where('section_id', $this->section_id)
                  ->orWhere('section_id', 0)
                  ->orWhereNull('section_id');
            });
    }

    /**
     * Accessor for teacher model
     */
    public function getTeacherAttribute()
    {
        if ($this->relationLoaded('teacher')) {
            return $this->getRelation('teacher');
        }

        if ($this->relationLoaded('assignedTeacher') && $this->assignedTeacher) {
            return $this->assignedTeacher->teacher;
        }

        $assigned = AssignedTeacher::with('teacher')
            ->where('session_id', $this->session_id)
            ->where('course_id', $this->course_id)
            ->where('class_id', $this->class_id)
            ->where(function($q) {
                $q->where('section_id', $this->section_id)
                  ->orWhere('section_id', 0)
                  ->orWhereNull('section_id');
            })
            ->first();

        return $assigned ? $assigned->teacher : null;
    }
}
