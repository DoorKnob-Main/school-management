<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BiometricDevice;
use App\Models\BiometricDeviceUserMapping;
use App\Models\BiometricAuditLog;
use App\Services\BiometricDeviceService;
use App\Services\BiometricSyncService;
use Illuminate\Support\Facades\Auth;

class BiometricDeviceController extends Controller
{
    protected $deviceService;
    protected $syncService;

    public function __construct(
        BiometricDeviceService $deviceService,
        BiometricSyncService $syncService
    ) {
        $this->middleware(['auth', 'biometric.enabled']);
        $this->deviceService = $deviceService;
        $this->syncService = $syncService;
    }

    /**
     * Display a listing of biometric devices.
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user->isAdminOrSuperAdmin()) {
            abort(403, 'Unauthorized access to Biometric Devices.');
        }

        $devices = BiometricDevice::withCount(['userMappings', 'punchLogs'])->get();
        $auditLogs = BiometricAuditLog::with('user')->latest()->take(20)->get();

        return view('biometric.devices.index', compact('devices', 'auditLogs'));
    }

    /**
     * Store a newly created device.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdminOrSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $request->merge([
            'is_active' => $request->boolean('is_active'),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'device_identifier' => 'required|string|max:100|unique:biometric_devices,device_identifier',
            'ip_address' => 'required|string|max:100',
            'port' => 'required|integer|min:1|max:65535',
            'machine_number' => 'required|integer|min:1',
            'communication_password' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['communication_password'] = $validated['communication_password'] ?: '0';

        $device = BiometricDevice::create($validated);

        BiometricAuditLog::log('device_created', [
            'device_id' => $device->id,
            'name' => $device->name,
            'ip' => $device->ip_address,
        ]);

        return redirect()->route('biometric.devices.index')
            ->with('success', 'Biometric device added successfully.');
    }

    /**
     * Update an existing device.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isAdminOrSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $device = BiometricDevice::findOrFail($id);

        $request->merge([
            'is_active' => $request->boolean('is_active'),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'device_identifier' => 'required|string|max:100|unique:biometric_devices,device_identifier,' . $device->id,
            'ip_address' => 'required|string|max:100',
            'port' => 'required|integer|min:1|max:65535',
            'machine_number' => 'required|integer|min:1',
            'communication_password' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        if (!empty($validated['communication_password'])) {
            $device->communication_password = $validated['communication_password'];
        }
        unset($validated['communication_password']);

        $device->update($validated);

        BiometricAuditLog::log('device_updated', [
            'device_id' => $device->id,
            'name' => $device->name,
        ]);

        return redirect()->route('biometric.devices.index')
            ->with('success', 'Biometric device updated successfully.');
    }

    /**
     * Delete a device.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user->isAdminOrSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $device = BiometricDevice::findOrFail($id);

        BiometricAuditLog::log('device_deleted', [
            'device_id' => $device->id,
            'name' => $device->name,
            'ip' => $device->ip_address,
        ]);

        $device->delete();

        return redirect()->route('biometric.devices.index')
            ->with('success', 'Biometric device removed.');
    }

    /**
     * Test connection to device.
     */
    public function testConnection($id)
    {
        @set_time_limit(60);
        $device = BiometricDevice::findOrFail($id);
        $result = $this->deviceService->testConnection($device);

        return response()->json($result);
    }

    /**
     * Trigger manual sync for a device.
     */
    public function syncNow($id)
    {
        @set_time_limit(120);
        $device = BiometricDevice::findOrFail($id);
        $result = $this->syncService->syncDevice($device);

        if ($result['success']) {
            return redirect()->back()->with('success', "Device {$device->name} synced successfully. Inserted {$result['inserted_count']} punches, {$result['duplicate_count']} duplicates skipped.");
        }

        return redirect()->back()->with('error', "Failed to sync {$device->name}: " . $result['message']);
    }

    /**
     * Sync all devices.
     */
    public function syncAll()
    {
        @set_time_limit(180);
        $results = $this->syncService->syncAllDevices();
        $totalInserted = 0;
        $totalDuplicates = 0;

        foreach ($results as $res) {
            $totalInserted += $res['inserted_count'] ?? 0;
            $totalDuplicates += $res['duplicate_count'] ?? 0;
        }

        return redirect()->back()->with('success', "Synchronized all active devices. {$totalInserted} new punches recorded, {$totalDuplicates} duplicates skipped.");
    }

    /**
     * Sync device clock.
     */
    public function syncTime($id)
    {
        @set_time_limit(60);
        $device = BiometricDevice::findOrFail($id);
        $result = $this->deviceService->syncDeviceTime($device);

        if ($result['success']) {
            return redirect()->back()->with('success', "Clock synced successfully for {$device->name}.");
        }

        return redirect()->back()->with('error', "Failed to sync clock for {$device->name}: " . $result['message']);
    }

    /**
     * Unlock door relay.
     */
    public function unlockDoor(Request $request, $id)
    {
        $device = BiometricDevice::findOrFail($id);
        $delaySec = (int)$request->input('delay_seconds', 5);
        $result = $this->deviceService->unlockDoor($device, $delaySec);

        if ($result['success']) {
            return redirect()->back()->with('success', "Door unlocked for {$delaySec} seconds on {$device->name}.");
        }

        return redirect()->back()->with('error', "Failed to unlock door on {$device->name}: " . $result['message']);
    }

    /**
     * Clear log buffer on device.
     */
    public function clearLogs($id)
    {
        $device = BiometricDevice::findOrFail($id);
        $result = $this->deviceService->clearDeviceLogs($device);

        if ($result['success']) {
            return redirect()->back()->with('success', "Log buffer cleared on {$device->name}.");
        }

        return redirect()->back()->with('error', "Failed to clear logs on {$device->name}: " . $result['message']);
    }

    /**
     * Inject simulated punches for testing / demonstration.
     */
    public function simulatePunches(Request $request, $id)
    {
        $device = BiometricDevice::findOrFail($id);
        $userId = (int)$request->input('user_id', 1);
        $timestamp = $request->input('timestamp', now()->toDateTimeString());
        $verifyType = (int)$request->input('verify_type', 1);

        $punches = [
            [
                'device_user_id' => $userId,
                'timestamp' => $timestamp,
                'verify_type' => $verifyType,
                'sensor_no' => 1,
            ]
        ];

        $stats = $this->syncService->injectSimulatedPunches($device, $punches);

        return redirect()->back()->with('success', "Simulated punch injected for User ID #{$userId} at {$timestamp}. Attendance updated.");
    }

    /**
     * Fetch enrolled users directly from biometric device hardware.
     */
    public function fetchDeviceUsers($id)
    {
        $user = Auth::user();
        if (!$user->isAdminOrSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $device = BiometricDevice::findOrFail($id);
        $res = $this->deviceService->fetchEnrolledUsers($device);

        // Get local mappings for this device to match hardware user IDs to student profiles
        $mappings = BiometricDeviceUserMapping::with('student')
            ->where('device_id', $device->id)
            ->get()
            ->keyBy('device_user_id');

        $usersList = [];

        if (isset($res['users']) && is_array($res['users'])) {
            foreach ($res['users'] as $u) {
                $deviceUserId = (int)($u['device_user_id'] ?? $u['user_id'] ?? $u['pin'] ?? 0);
                $localMapping = $mappings->get($deviceUserId);
                
                $studentName = null;
                if ($localMapping && $localMapping->student) {
                    $studentName = $localMapping->student->first_name . ' ' . $localMapping->student->last_name;
                }

                $usersList[] = [
                    'device_user_id' => $deviceUserId,
                    'name' => $u['name'] ?? ($studentName ?: "Machine User #{$deviceUserId}"),
                    'card_no' => $u['card_no'] ?? $u['card_number'] ?? 0,
                    'privilege' => $u['privilege'] ?? 0,
                    'student_id' => $localMapping ? $localMapping->student_id : null,
                    'student_name' => $studentName,
                    'is_mapped' => !empty($localMapping),
                ];
            }
        }

        // Also append any local mappings that might not have been returned in live probe
        foreach ($mappings as $deviceUserId => $mapping) {
            $exists = false;
            foreach ($usersList as $ul) {
                if ($ul['device_user_id'] == $deviceUserId) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists && $mapping->student) {
                $usersList[] = [
                    'device_user_id' => $deviceUserId,
                    'name' => $mapping->student->first_name . ' ' . $mapping->student->last_name,
                    'card_no' => 0,
                    'privilege' => 0,
                    'student_id' => $mapping->student_id,
                    'student_name' => $mapping->student->first_name . ' ' . $mapping->student->last_name,
                    'is_mapped' => true,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'device_name' => $device->name,
            'count' => count($usersList),
            'users' => $usersList,
            'hardware_status' => $res['success'] ? 'online' : 'offline',
            'message' => $res['success'] ? 'Users fetched from device hardware.' : ($res['message'] ?? 'Device offline; showing local mappings.')
        ]);
    }

    /**
     * Delete an enrolled user directly from biometric device hardware and remove local mapping.
     */
    public function deleteDeviceUser(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isAdminOrSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $device = BiometricDevice::findOrFail($id);
        $deviceUserId = (int)$request->input('device_user_id');

        if (!$deviceUserId) {
            return response()->json(['success' => false, 'message' => 'Invalid Machine User ID.'], 422);
        }

        // Attempt deletion from physical hardware
        $res = $this->deviceService->deleteUser($device, $deviceUserId);

        // Delete local mapping if exists
        BiometricDeviceUserMapping::where('device_id', $device->id)
            ->where('device_user_id', $deviceUserId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "User ID #{$deviceUserId} deleted from device {$device->name} and local mappings updated.",
            'hardware_result' => $res
        ]);
    }
}
