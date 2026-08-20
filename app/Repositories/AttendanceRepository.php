<?php

namespace App\Repositories;

use Carbon\Carbon;
use App\Models\Attendance;
use App\Interfaces\AttendanceInterface;

class AttendanceRepository implements AttendanceInterface {
    /**
     * Statuses a teacher may set from the manual attendance screen.
     * These are the SAME strings the biometric pipeline writes
     * (BiometricAttendanceProcessor): 'on' = present, 'off' = absent.
     * Keeping one vocabulary means every reader sees consistent values.
     */
    public const MANUAL_STATUSES = ['on', 'off', 'late', 'on_leave'];

    public function saveAttendance($request) {
        try {
            // Upsert one row per student for the selected date so re-taking or
            // back-dating attendance updates the existing record instead of
            // creating duplicates.
            $date = !empty($request['attendance_date'])
                ? Carbon::parse($request['attendance_date'])->toDateString()
                : Carbon::today()->toDateString();
            $isToday = $date === Carbon::today()->toDateString();

            foreach ($request['student_ids'] as $student_id) {
                $status = $request['status'][$student_id] ?? 'off';
                if (!in_array($status, self::MANUAL_STATUSES, true)) {
                    $status = 'off';
                }

                $attendance = Attendance::where('student_id', $student_id)
                    ->where('session_id', $request['session_id'])
                    ->where('class_id', $request['class_id'])
                    ->where('section_id', $request['section_id'])
                    ->where('course_id', $request['course_id'])
                    ->whereDate('created_at', '=', $date)
                    ->first();

                if ($attendance) {
                    $attendance->status = $status;
                    $attendance->attendance_source = 'manual';
                    $attendance->save();
                } else {
                    $attendance = new Attendance();
                    $attendance->status = $status;
                    $attendance->class_id = $request['class_id'];
                    $attendance->student_id = $student_id;
                    $attendance->section_id = $request['section_id'];
                    $attendance->course_id = $request['course_id'];
                    $attendance->session_id = $request['session_id'];
                    $attendance->attendance_source = 'manual';
                    // Keep the real timestamp for today; back-dated rows are
                    // stamped at the chosen date (current time-of-day).
                    $attendance->created_at = $isToday
                        ? Carbon::now()
                        : Carbon::parse($date . ' ' . Carbon::now()->format('H:i:s'));
                    $attendance->updated_at = Carbon::now();
                    $attendance->save();
                }
            }
        } catch (\Exception $e) {
            throw new \Exception('Failed to save attendance. '.$e->getMessage());
        }
    }

    public function getSectionAttendance($class_id, $section_id, $session_id, $date = null) {
        try {
            $date = $date ?: Carbon::today()->toDateString();
            return Attendance::with('student')
                            ->where('class_id', $class_id)
                            ->where('section_id', $section_id)
                            ->where('session_id', $session_id)
                            ->whereDate('created_at', '=', $date)
                            ->get();
        } catch (\Exception $e) {
            throw new \Exception('Failed to get attendances. '.$e->getMessage());
        }
    }

    public function getCourseAttendance($class_id, $course_id, $session_id, $date = null) {
        try {
            $date = $date ?: Carbon::today()->toDateString();
            return Attendance::with('student')
                            ->where('class_id', $class_id)
                            ->where('course_id', $course_id)
                            ->where('session_id', $session_id)
                            ->whereDate('created_at', '=', $date)
                            ->get();
        } catch (\Exception $e) {
            throw new \Exception('Failed to get attendances. '.$e->getMessage());
        }
    }

    public function getStudentAttendance($session_id, $student_id) {
        try {
            return Attendance::with(['section','course'])
                            ->where('student_id', $student_id)
                            ->where('session_id', $session_id)
                            ->orderBy('created_at', 'desc')
                            ->get();
        } catch (\Exception $e) {
            throw new \Exception('Failed to get attendances. '.$e->getMessage());
        }
    }
}