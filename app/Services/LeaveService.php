<?php

namespace App\Services;

use App\Models\LeaveType;
use App\Models\StudentLeave;
use App\Models\BiometricAuditLog;
use App\Models\User;
use Carbon\Carbon;

class LeaveService
{
    /**
     * Get all active leave types.
     */
    public function getActiveLeaveTypes()
    {
        return LeaveType::where('is_active', true)->get();
    }

    /**
     * Apply for student leave.
     */
    public function applyLeave(array $data, int $studentId): StudentLeave
    {
        $leave = StudentLeave::create([
            'student_id' => $studentId,
            'leave_type_id' => $data['leave_type_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        BiometricAuditLog::log('student_leave_applied', [
            'leave_id' => $leave->id,
            'student_id' => $studentId,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ]);

        return $leave;
    }

    /**
     * Approve student leave.
     */
    public function approveLeave(StudentLeave $leave, int $approverId): bool
    {
        $leave->update([
            'status' => 'approved',
            'approved_by' => $approverId,
            'rejection_reason' => null,
        ]);

        BiometricAuditLog::log('student_leave_approved', [
            'leave_id' => $leave->id,
            'student_id' => $leave->student_id,
            'approved_by' => $approverId,
        ]);

        // Trigger attendance recalculation for affected dates
        app(BiometricAttendanceProcessor::class)->processDate($leave->start_date->toDateString());
        if ($leave->end_date->gt($leave->start_date)) {
            $period = Carbon::parse($leave->start_date)->toPeriod($leave->end_date);
            foreach ($period as $date) {
                app(BiometricAttendanceProcessor::class)->processDate($date->toDateString());
            }
        }

        return true;
    }

    /**
     * Reject student leave.
     */
    public function rejectLeave(StudentLeave $leave, int $rejectorId, ?string $reason = null): bool
    {
        $leave->update([
            'status' => 'rejected',
            'approved_by' => $rejectorId,
            'rejection_reason' => $reason,
        ]);

        BiometricAuditLog::log('student_leave_rejected', [
            'leave_id' => $leave->id,
            'student_id' => $leave->student_id,
            'rejected_by' => $rejectorId,
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * Check if a student is on approved leave on a given date.
     */
    public function isStudentOnLeave(int $studentId, string $date): bool
    {
        return StudentLeave::activeOnDate($date)
            ->where('student_id', $studentId)
            ->exists();
    }
}
