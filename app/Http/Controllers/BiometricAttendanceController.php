<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\BiometricPunchLog;
use App\Models\BiometricAuditLog;
use App\Models\SchoolSession;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\User;
use App\Services\BiometricAttendanceProcessor;
use App\Services\SettingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class BiometricAttendanceController extends Controller
{
    protected $processor;
    protected $settingService;

    public function __construct(
        BiometricAttendanceProcessor $processor,
        SettingService $settingService
    ) {
        $this->middleware(['auth', 'biometric.enabled']);
        $this->processor = $processor;
        $this->settingService = $settingService;
    }

    /**
     * Display biometric dashboard.
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        if ($user->effective_role === 'student') {
            return redirect()->route('student.attendance.show', ['id' => $user->id]);
        }

        $date = $request->input('date', Carbon::today()->toDateString());
        $devices = BiometricDevice::all();
        $totalDevices = $devices->count();
        $onlineDevices = $devices->where('status', 'online')->count();

        $punchesTodayCount = BiometricPunchLog::whereDate('punch_time', $date)->count();

        // Process / Fetch attendance for selected date
        $attendanceStats = $this->processor->processDate($date);

        // Fetch detailed attendance list for date (daily school attendance)
        $attendances = Attendance::with(['student', 'schoolClass', 'section'])
            ->whereDate('created_at', $date)
            ->where(function($q) {
                $q->where('course_id', 0)->orWhereNull('course_id');
            })
            ->paginate(25);

        $recentPunches = BiometricPunchLog::with(['device', 'student'])
            ->whereDate('punch_time', $date)
            ->latest('punch_time')
            ->take(15)
            ->get();

        $timingSettings = $this->processor->getSchoolTimingSettings();

        return view('biometric.dashboard', compact(
            'date',
            'devices',
            'totalDevices',
            'onlineDevices',
            'punchesTodayCount',
            'attendanceStats',
            'attendances',
            'recentPunches',
            'timingSettings'
        ));
    }

    /**
     * Display today's / specific date's attendance table.
     */
    public function today(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');
        $statusFilter = $request->input('status');

        $query = Attendance::with(['student', 'schoolClass', 'section'])
            ->whereDate('created_at', $date)
            ->where(function($q) {
                $q->where('course_id', 0)->orWhereNull('course_id');
            });

        if ($classId) {
            $query->where('class_id', $classId);
        }
        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }
        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $attendances = $query->paginate(30);
        $classes = SchoolClass::all();

        return view('biometric.attendance.today', compact('date', 'attendances', 'classes', 'classId', 'sectionId', 'statusFilter'));
    }

    /**
     * Display searchable raw biometric punch logs.
     */
    public function punchLogs(Request $request)
    {
        $deviceId = $request->input('device_id');
        $studentId = $request->input('student_id');
        $date = $request->input('date');
        $verifyType = $request->input('verify_type');

        $query = BiometricPunchLog::with(['device', 'student'])->latest('punch_time');

        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }
        if ($studentId) {
            $query->where('student_id', $studentId);
        }
        if ($date) {
            $query->whereDate('punch_time', $date);
        }
        if ($verifyType) {
            $query->where('verify_type', $verifyType);
        }

        $logs = $query->paginate(30);
        $devices = BiometricDevice::all();
        $students = User::where('role', 'student')->get();

        return view('biometric.punches.index', compact('logs', 'devices', 'students', 'deviceId', 'studentId', 'date', 'verifyType'));
    }

    /**
     * Manually correct an attendance record.
     */
    public function correctAttendance(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdminOrSuperAdmin()) {
            abort(403, 'Unauthorized action. Only Admin can correct biometric attendance.');
        }

        $validated = $request->validate([
            'attendance_id' => 'required|exists:attendances,id',
            'status' => 'required|string|in:on,off,present,absent,late,on_leave,half_day',
            'in_time' => 'nullable|date',
            'out_time' => 'nullable|date',
            'correction_reason' => 'required|string|max:255',
        ]);

        $attendance = Attendance::findOrFail($validated['attendance_id']);

        $attendance->update([
            'status' => $validated['status'],
            'in_time' => !empty($validated['in_time']) ? Carbon::parse($validated['in_time']) : $attendance->in_time,
            'out_time' => !empty($validated['out_time']) ? Carbon::parse($validated['out_time']) : $attendance->out_time,
            'is_corrected' => true,
            'corrected_by' => $user->id,
            'correction_reason' => $validated['correction_reason'],
            'attendance_source' => 'corrected',
        ]);

        BiometricAuditLog::log('attendance_corrected', [
            'attendance_id' => $attendance->id,
            'student_id' => $attendance->student_id,
            'new_status' => $validated['status'],
            'reason' => $validated['correction_reason'],
            'corrected_by' => $user->id,
        ]);

        return redirect()->back()->with('success', 'Attendance record corrected successfully.');
    }

    /**
     * Display biometric settings management view.
     */
    public function settings()
    {
        $user = Auth::user();
        if (!$user->isAdminOrSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $timing = $this->processor->getSchoolTimingSettings();

        return view('biometric.settings.index', compact('timing'));
    }

    /**
     * Update biometric school timing and attendance rules.
     */
    public function updateSettings(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdminOrSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'school_start_time' => 'required|string',
            'school_end_time' => 'required|string',
            'present_window_start' => 'required|string',
            'present_window_end' => 'required|string',
            'late_window_start' => 'required|string',
            'late_window_end' => 'required|string',
            'school_leave_time' => 'required|string',
            'early_leave_threshold' => 'required|string',
            'auto_absent_after' => 'required|string',
            'use_first_punch_as_in' => 'nullable|boolean',
            'use_last_punch_as_out' => 'nullable|boolean',
            'working_days' => 'nullable|array',
        ]);

        $settingsData = [
            'school_start_time' => $validated['school_start_time'],
            'school_end_time' => $validated['school_end_time'],
            'present_window_start' => $validated['present_window_start'],
            'present_window_end' => $validated['present_window_end'],
            'late_window_start' => $validated['late_window_start'],
            'late_window_end' => $validated['late_window_end'],
            'school_leave_time' => $validated['school_leave_time'],
            'early_leave_threshold' => $validated['early_leave_threshold'],
            'auto_absent_after' => $validated['auto_absent_after'],
            'use_first_punch_as_in' => $request->has('use_first_punch_as_in') ? '1' : '0',
            'use_last_punch_as_out' => $request->has('use_last_punch_as_out') ? '1' : '0',
            'working_days' => json_encode($request->input('working_days', ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"])),
        ];

        // If Super Admin, allow toggling biometric_attendance_enabled
        if ($user->isSuperAdmin() && $request->has('biometric_attendance_enabled_submitted')) {
            $settingsData['biometric_attendance_enabled'] = $request->has('biometric_attendance_enabled') ? '1' : '0';
        }

        $this->settingService->updateBatch($settingsData, array_fill_keys(array_keys($settingsData), 'attendance'));

        BiometricAuditLog::log('attendance_settings_updated', $settingsData);

        return redirect()->route('biometric.settings.index')
            ->with('success', 'Biometric attendance settings saved successfully.');
    }

    /**
     * Recalculate attendance for a specific date.
     */
    public function recalculate(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdminOrSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $date = $request->input('date', Carbon::today()->toDateString());
        $stats = $this->processor->processDate($date);

        return redirect()->back()->with('success', "Attendance recalculated for {$date}. Present: {$stats['present']}, Late: {$stats['late']}, Absent: {$stats['absent']}.");
    }
}
