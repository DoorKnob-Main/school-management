<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BiometricDevice;
use App\Models\BiometricDeviceUserMapping;
use App\Models\BiometricPunchLog;
use App\Models\User;
use App\Services\BiometricDeviceService;
use Illuminate\Support\Facades\Auth;

class BiometricEnrollmentController extends Controller
{
    protected $deviceService;

    public function __construct(BiometricDeviceService $deviceService)
    {
        $this->middleware(['auth', 'biometric.enabled']);
        $this->deviceService = $deviceService;
    }

    /**
     * Display student enrollment management view.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdminOrSuperAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $devices = BiometricDevice::active()->get();
        $selectedDeviceId = $request->input('device_id', $devices->first() ? $devices->first()->id : null);

        $mappingsQuery = BiometricDeviceUserMapping::with(['student', 'device']);
        if ($selectedDeviceId) {
            $mappingsQuery->where('device_id', $selectedDeviceId);
        }

        $mappings = $mappingsQuery->paginate(20);
        $students = User::where('role', 'student')->get();

        return view('biometric.enrollment.index', compact('devices', 'selectedDeviceId', 'mappings', 'students'));
    }

    /**
     * Enroll a student to a biometric machine.
     */
    public function enroll(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdminOrSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'device_id' => 'required|exists:biometric_devices,id',
            'device_user_id' => 'required|integer|min:1',
            'card_number' => 'nullable|integer',
            'user_password' => 'nullable|integer',
            'privilege' => 'nullable|integer|in:0,1',
        ]);

        $student = User::findOrFail($validated['student_id']);
        $device = BiometricDevice::findOrFail($validated['device_id']);
        $deviceUserId = (int)$validated['device_user_id'];
        $cardNo = (int)($validated['card_number'] ?? 0);
        $pwd = (int)($validated['user_password'] ?? 1234);
        $privilege = (int)($validated['privilege'] ?? 0);

        // Check if device_user_id is already assigned to a DIFFERENT student on this device
        $existing = BiometricDeviceUserMapping::where('device_id', $device->id)
            ->where('device_user_id', $deviceUserId)
            ->first();

        if ($existing && $existing->student_id != $student->id) {
            return redirect()->back()->with('error', "Machine User ID #{$deviceUserId} is already mapped to another student (ID: {$existing->student_id}).");
        }

        $studentName = trim($student->first_name . ' ' . $student->last_name);

        // Try pushing to physical device
        $enrollResult = $this->deviceService->enrollUser($device, $deviceUserId, $studentName, $cardNo, $pwd, $privilege);

        // Save local mapping
        $mapping = BiometricDeviceUserMapping::updateOrCreate(
            ['device_id' => $device->id, 'device_user_id' => $deviceUserId],
            [
                'student_id' => $student->id,
                'enrollment_status' => $enrollResult['success'] ? 'enrolled' : 'pending',
                'enrolled_at' => now(),
            ]
        );

        // Resolve any past unmapped punch logs for this device_user_id
        BiometricPunchLog::where('device_id', $device->id)
            ->where('device_user_id', $deviceUserId)
            ->whereNull('student_id')
            ->update(['student_id' => $student->id]);

        return redirect()->back()->with('success', "Student {$studentName} enrolled to {$device->name} with Machine User ID #{$deviceUserId}.");
    }

    /**
     * Unenroll/delete user from biometric device.
     */
    public function unenroll($id)
    {
        $user = Auth::user();
        if (!$user->isAdminOrSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $mapping = BiometricDeviceUserMapping::findOrFail($id);
        $device = $mapping->device;

        if ($device) {
            $this->deviceService->deleteUser($device, $mapping->device_user_id);
        }

        $mapping->delete();

        return redirect()->back()->with('success', 'Student enrollment mapping removed.');
    }

    /**
     * View unmapped biometric punches (machine user IDs not yet linked to students).
     */
    public function unmapped()
    {
        $user = Auth::user();
        if (!$user->isAdminOrSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        // Find punch logs where student_id is null, group by device and device_user_id
        $unmappedPunches = BiometricPunchLog::with('device')
            ->whereNull('student_id')
            ->selectRaw('device_id, device_user_id, count(*) as punch_count, min(punch_time) as first_seen, max(punch_time) as last_seen')
            ->groupBy('device_id', 'device_user_id')
            ->paginate(20);

        $students = User::where('role', 'student')->get();

        return view('biometric.enrollment.unmapped', compact('unmappedPunches', 'students'));
    }

    /**
     * Map an unmapped machine user ID to a student.
     */
    public function mapUser(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdminOrSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'device_id' => 'required|exists:biometric_devices,id',
            'device_user_id' => 'required|integer',
            'student_id' => 'required|exists:users,id',
        ]);

        $device = BiometricDevice::findOrFail($validated['device_id']);
        $student = User::findOrFail($validated['student_id']);

        // Create or update mapping
        BiometricDeviceUserMapping::updateOrCreate(
            ['device_id' => $device->id, 'device_user_id' => $validated['device_user_id']],
            [
                'student_id' => $student->id,
                'enrollment_status' => 'enrolled',
                'enrolled_at' => now(),
            ]
        );

        // Update all unmapped logs
        $updatedCount = BiometricPunchLog::where('device_id', $device->id)
            ->where('device_user_id', $validated['device_user_id'])
            ->whereNull('student_id')
            ->update(['student_id' => $student->id]);

        // Trigger attendance processor for any newly resolved punches
        app(\App\Services\BiometricAttendanceProcessor::class)->processDate(now()->toDateString());

        return redirect()->back()->with('success', "Mapped Machine User ID #{$validated['device_user_id']} to {$student->first_name} {$student->last_name}. {$updatedCount} punch records updated.");
    }
}
