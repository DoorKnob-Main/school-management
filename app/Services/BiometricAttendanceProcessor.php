<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\BiometricPunchLog;
use App\Models\StudentLeave;
use App\Models\Promotion;
use App\Models\SchoolSession;
use App\Models\AcademicSetting;
use App\Models\User;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BiometricAttendanceProcessor
{
    protected $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Process biometric attendance for all students on a given date.
     */
    public function processDate(string $date): array
    {
        $dateObj = Carbon::parse($date);
        $academicSetting = AcademicSetting::latest()->first();
        $currentSession = SchoolSession::orderBy('id', 'desc')->first();
        $sessionId = $currentSession ? $currentSession->id : 1;

        $timing = $this->getSchoolTimingSettings();

        // Check if date is a working day
        $dayName = $dateObj->format('l');
        $workingDays = json_decode($timing['working_days'] ?? '["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"]', true) ?: ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];

        $isWorkingDay = in_array($dayName, $workingDays);

        // Check for school calendar holiday event
        $isHoliday = !$isWorkingDay || Event::whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();

        // Get all active students enrolled in current session
        $students = User::where('role', 'student')->get();

        $stats = [
            'total_students' => $students->count(),
            'present' => 0,
            'late' => 0,
            'absent' => 0,
            'on_leave' => 0,
            'holiday' => 0,
            'missing_checkout' => 0,
            'early_leave' => 0,
        ];

        foreach ($students as $student) {
            $studentResult = $this->processStudentAttendance($student, $dateObj, $timing, $sessionId, $isHoliday);
            if (isset($stats[$studentResult['status']])) {
                $stats[$studentResult['status']]++;
            }
            if (!empty($studentResult['missing_checkout'])) {
                $stats['missing_checkout']++;
            }
            if (!empty($studentResult['early_leave'])) {
                $stats['early_leave']++;
            }
        }

        // Mark processed raw logs for that date
        BiometricPunchLog::whereDate('punch_time', $date)->update(['is_processed' => true]);

        return $stats;
    }

    /**
     * Process biometric attendance for a single student on a given date.
     */
    public function processStudentAttendance(User $student, Carbon $dateObj, array $timing, int $sessionId, bool $isHoliday = false): array
    {
        $dateStr = $dateObj->toDateString();

        // Find student class & section from Promotion or Academic info
        $promotion = Promotion::where('student_id', $student->id)
            ->where('session_id', $sessionId)
            ->first();

        $classId = $promotion ? $promotion->class_id : ($student->academic_info->class_id ?? 0);
        $sectionId = $promotion ? $promotion->section_id : ($student->academic_info->section_id ?? 0);
        $courseId = 0; // 0 for section-based attendance or default

        // 1. Check if student has approved leave
        $approvedLeave = StudentLeave::where('student_id', $student->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $dateStr)
            ->whereDate('end_date', '>=', $dateStr)
            ->first();

        // 2. Fetch all raw biometric punches for this student on this day
        $punches = BiometricPunchLog::where('student_id', $student->id)
            ->whereDate('punch_time', $dateStr)
            ->orderBy('punch_time', 'asc')
            ->get();

        // Existing manual attendance check: Don't overwrite if manual correction exists
        $existingAttendance = Attendance::where('student_id', $student->id)
            ->where('session_id', $sessionId)
            ->where(function($q) {
                $q->where('course_id', 0)->orWhereNull('course_id');
            })
            ->whereDate('created_at', $dateStr)
            ->first();

        if ($existingAttendance && $existingAttendance->is_corrected) {
            return [
                'status' => $existingAttendance->status,
                'in_time' => $existingAttendance->in_time,
                'out_time' => $existingAttendance->out_time,
                'is_corrected' => true,
            ];
        }

        // Default calculations
        $firstPunch = $punches->first();
        $firstPunchTime = $firstPunch ? Carbon::parse($firstPunch->punch_time) : null;

        // Filter last punch: must be at least 5 minutes after first punch to count as checkout
        $validExitPunch = null;
        if ($punches->count() > 1 && $firstPunchTime) {
            $candidateLast = $punches->last();
            $candidateLastTime = Carbon::parse($candidateLast->punch_time);
            if ($firstPunchTime->diffInMinutes($candidateLastTime) >= 5) {
                $validExitPunch = $candidateLast;
            }
        }

        $inTime = $firstPunch ? $firstPunch->punch_time : null;
        $outTime = $validExitPunch ? $validExitPunch->punch_time : null;

        $lateMinutes = 0;
        $earlyLeaveMinutes = 0;
        $missingCheckout = false;
        $isEarlyLeave = false;
        $status = 'off'; // default absent in ERP schema ('on' = present, 'off' = absent)

        if ($isHoliday && $punches->isEmpty()) {
            $status = 'holiday';
        } elseif ($approvedLeave) {
            $status = 'on_leave';
        } elseif ($firstPunch) {
            // Parse configured thresholds for the date
            $startTime = Carbon::parse($dateStr . ' ' . ($timing['school_start_time'] ?? '08:30:00'));
            $presentUntil = Carbon::parse($dateStr . ' ' . ($timing['present_window_end'] ?? '08:45:00'));
            $lateUntil = Carbon::parse($dateStr . ' ' . ($timing['late_window_end'] ?? '09:15:00'));
            $leaveTime = Carbon::parse($dateStr . ' ' . ($timing['school_leave_time'] ?? '14:30:00'));
            $earlyThreshold = Carbon::parse($dateStr . ' ' . ($timing['early_leave_threshold'] ?? '14:15:00'));

            if ($firstPunchTime->lte($presentUntil)) {
                $status = 'present';
            } elseif ($firstPunchTime->lte($lateUntil)) {
                $status = 'late';
                $lateMinutes = max(0, $firstPunchTime->diffInMinutes($startTime));
            } else {
                $status = 'late';
                $lateMinutes = max(0, $firstPunchTime->diffInMinutes($startTime));
            }

            // Calculate checkout and early leaving
            if ($validExitPunch) {
                $exitTime = Carbon::parse($validExitPunch->punch_time);
                if ($exitTime->lt($earlyThreshold)) {
                    $isEarlyLeave = true;
                    $earlyLeaveMinutes = max(0, $leaveTime->diffInMinutes($exitTime));
                }
            } else {
                // No valid exit punch yet: Check if school day is already over
                $now = Carbon::now();
                if ($dateObj->isPast() || $now->gt($leaveTime)) {
                    $missingCheckout = true;
                }
            }
        } else {
            // No punches
            $autoAbsentCutoff = Carbon::parse($dateStr . ' ' . ($timing['auto_absent_after'] ?? '09:15:00'));
            $now = Carbon::now();

            if ($dateObj->isPast() || $now->gte($autoAbsentCutoff)) {
                $status = 'absent';
            } else {
                $status = 'pending'; // day not ended yet
            }
        }

        // Map status cleanly to existing ERP status values:
        // 'present' / 'late' -> map to 'on' or specific string
        // 'absent' -> 'off'
        // 'on_leave' -> 'on_leave'
        // 'holiday' -> 'holiday'
        $erpStatus = $status;
        if ($status === 'present') {
            $erpStatus = 'on';
        } elseif ($status === 'absent') {
            $erpStatus = 'off';
        }

        // Upsert daily attendance record
        $attendanceData = [
            'student_id' => $student->id,
            'class_id' => $classId,
            'section_id' => $sectionId,
            'course_id' => $courseId,
            'session_id' => $sessionId,
            'status' => $erpStatus,
            'in_time' => $inTime,
            'out_time' => $outTime,
            'attendance_source' => 'biometric',
            'late_minutes' => $lateMinutes,
            'early_leave_minutes' => $earlyLeaveMinutes,
            'is_corrected' => false,
            'remarks' => $status === 'late' ? "Late by {$lateMinutes} min" : ($isEarlyLeave ? "Left early by {$earlyLeaveMinutes} min" : null),
        ];

        if ($existingAttendance) {
            $existingAttendance->update($attendanceData);
        } else {
            $attendanceData['created_at'] = Carbon::parse($dateStr . ' 08:30:00');
            $attendanceData['updated_at'] = Carbon::now();
            Attendance::create($attendanceData);
        }

        return [
            'status' => $status,
            'in_time' => $inTime,
            'out_time' => $outTime,
            'late_minutes' => $lateMinutes,
            'early_leave_minutes' => $earlyLeaveMinutes,
            'missing_checkout' => $missingCheckout,
            'early_leave' => $isEarlyLeave,
        ];
    }

    /**
     * Get all school attendance timing settings with robust defaults.
     */
    public function getSchoolTimingSettings(): array
    {
        return [
            'biometric_attendance_enabled' => $this->settingService->get('biometric_attendance_enabled', '1'),
            'school_start_time' => $this->settingService->get('school_start_time', '08:30'),
            'school_end_time' => $this->settingService->get('school_end_time', '14:30'),
            'present_window_start' => $this->settingService->get('present_window_start', '08:00'),
            'present_window_end' => $this->settingService->get('present_window_end', '08:45'),
            'late_window_start' => $this->settingService->get('late_window_start', '08:46'),
            'late_window_end' => $this->settingService->get('late_window_end', '09:15'),
            'school_leave_time' => $this->settingService->get('school_leave_time', '14:30'),
            'early_leave_threshold' => $this->settingService->get('early_leave_threshold', '14:15'),
            'auto_absent_after' => $this->settingService->get('auto_absent_after', '09:15'),
            'use_first_punch_as_in' => $this->settingService->get('use_first_punch_as_in', '1'),
            'use_last_punch_as_out' => $this->settingService->get('use_last_punch_as_out', '1'),
            'multiple_punches_mode' => $this->settingService->get('multiple_punches_mode', 'first_last'),
            'working_days' => $this->settingService->get('working_days', '["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"]'),
        ];
    }
}
