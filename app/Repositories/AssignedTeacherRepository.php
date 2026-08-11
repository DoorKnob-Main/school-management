<?php

namespace App\Repositories;

use App\Models\Semester;
use App\Models\AssignedTeacher;
use App\Interfaces\AssignedTeacherInterface;

class AssignedTeacherRepository implements AssignedTeacherInterface {

    public function assign($request) {
        try {
            AssignedTeacher::create($request);
        } catch (\Exception $e) {
            throw new \Exception('Failed to assign teacher. '.$e->getMessage());
        }
    }

    public function getTeacherCourses($session_id, $teacher_id, $semester_id) {
        if($semester_id == 0) {
            $semester_id = Semester::where('session_id', $session_id)
            ->first()->id;
        }
        return AssignedTeacher::with(['course', 'schoolClass', 'section'])->where('session_id', $session_id)
                        ->where('teacher_id', $teacher_id)
                        ->where('semester_id', $semester_id)
                        ->get(); 
    }

    public function getAssignedTeacher($session_id, $semester_id, $class_id, $section_id, $course_id) {
        $query = AssignedTeacher::where('session_id', $session_id);

        if ($semester_id > 0) {
            $query->where('semester_id', $semester_id);
        }
        if ($class_id > 0) {
            $query->where('class_id', $class_id);
        }
        if ($section_id > 0) {
            $query->where('section_id', $section_id);
        }
        if ($course_id > 0) {
            $query->where('course_id', $course_id);
        }

        return $query->first(); 
    }
}