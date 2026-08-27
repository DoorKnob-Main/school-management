<?php

namespace App\Repositories;

use App\Models\Routine;
use App\Interfaces\RoutineInterface;

class RoutineRepository implements RoutineInterface {
    public function saveRoutine($request)
    {
        try{
            Routine::create([
                'start'         => $request['start'],
                'end'           => $request['end'],
                'weekday'       => $request['weekday'],
                'session_id'    => $request['session_id'],
                'class_id'      => $request['class_id'],
                'section_id'    => $request['section_id'],
                'course_id'     => $request['course_id'],
            ]);
        } catch (\Exception $e) {
            throw new \Exception('Failed to save routine. '.$e->getMessage());
        }
    }

    public function getAll($class_id, $section_id, $session_id) {
        $routines = Routine::with(['course', 'schoolClass', 'section'])
                ->where('session_id', $session_id)
                ->where('class_id', $class_id)
                ->where('section_id', $section_id)
                ->get();

        // Eager load assigned teachers for courses in this class & session
        $assignedTeachers = \App\Models\AssignedTeacher::with('teacher')
            ->where('session_id', $session_id)
            ->where('class_id', $class_id)
            ->get();

        $teacherMap = [];
        foreach ($assignedTeachers as $at) {
            if ($at->teacher) {
                $teacherMap[$at->course_id . '_' . $at->section_id] = $at->teacher;
                if (!isset($teacherMap[$at->course_id])) {
                    $teacherMap[$at->course_id] = $at->teacher;
                }
            }
        }

        foreach ($routines as $routine) {
            $teacher = $teacherMap[$routine->course_id . '_' . $routine->section_id] 
                ?? $teacherMap[$routine->course_id] 
                ?? null;
            $routine->setRelation('teacher', $teacher);
        }

        return $routines;
    }
}