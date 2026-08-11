<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Repositories\AssignedTeacherRepository;

trait AssignedTeacherCheck {
    /**
     * @param  \Illuminate\Http\Request $request
     * @param int $current_school_session_id
     * @return string
    */
    public function checkIfLoggedInUserIsAssignedTeacher(Request $request, $current_school_session_id) {
        if (Auth::user() && Auth::user()->isAdminOrSuperAdmin()) {
            return;
        }

        $assignedTeacherRepository = new AssignedTeacherRepository();

        $semester_id = $request->semester_id ?? $request->query('semester_id', 0);
        $class_id = $request->class_id ?? $request->query('class_id', 0);
        $section_id = $request->section_id ?? $request->query('section_id', 0);
        $course_id = $request->course_id ?? $request->query('course_id', 0);

        $assignedTeacher = $assignedTeacherRepository->getAssignedTeacher($current_school_session_id, $semester_id, $class_id, $section_id, $course_id);
        
        if($assignedTeacher === null || $assignedTeacher->teacher_id != Auth::user()->id) {
            abort(404);
        }
    }
}