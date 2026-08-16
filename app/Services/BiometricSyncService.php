<?php

namespace App\Services;

use App\Models\BiometricDevice;
use App\Models\BiometricDeviceUserMapping;
use App\Models\BiometricPunchLog;
use App\Models\BiometricAuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BiometricSyncService
{
    protected $deviceService;
    protected $attendanceProcessor;

    public function __construct(
        BiometricDeviceService $deviceService,
        BiometricAttendanceProcessor $attendanceProcessor
    ) {
        $this->deviceService = $deviceService;
        $this->attendanceProcessor = $attendanceProcessor;
    }

    /**
     * Synchronize all active devices.
     */
    public function syncAllDevices(): array
    {
        $devices = BiometricDevice::active()->get();
        $results = [];

        foreach ($devices as $device) {
            $results[$device->id] = $this->syncDevice($device);
        }

        return $results;
    }

    /**
     * Synchronize a specific biometric device.
     */
    public function syncDevice(BiometricDevice $device): array
    {
        $batchId = 'SYNC_' . $device->id . '_' . time();
        $startTime = microtime(true);

        $fetchResult = $this->deviceService->fetchAttendanceLogs($device);

        if (!$fetchResult['success']) {
            BiometricAuditLog::log('sync_failed', [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'error' => $fetchResult['message'],
            ]);

            return [
                'success' => false,
                'device_id' => $device->id,
                'device_name' => $device->name,
                'message' => $fetchResult['message'],
                'fetched_count' => 0,
                'inserted_count' => 0,
                'duplicate_count' => 0,
                'unmapped_count' => 0,
            ];
        }

        $logs = $fetchResult['logs'];
        $stats = $this->storePunchLogs($device, $logs, $batchId);

        // Update device sync timestamp
        $device->update([
            'last_sync_at' => now(),
            'last_connected_at' => now(),
            'status' => 'online',
            'last_error' => null,
        ]);

        // Automatically process daily attendance for any modified dates
        $processedDates = $stats['affected_dates'] ?? [Carbon::today()->toDateString()];
        $attendanceStats = [];
        foreach ($processedDates as $date) {
            $attendanceStats[$date] = $this->attendanceProcessor->processDate($date);
        }

        $elapsed = round((microtime(true) - $startTime) * 1000);

        BiometricAuditLog::log('sync_success', [
            'device_id' => $device->id,
            'device_name' => $device->name,
            'batch_id' => $batchId,
            'fetched' => count($logs),
            'inserted' => $stats['inserted'],
            'duplicates' => $stats['duplicates'],
            'unmapped' => $stats['unmapped'],
            'duration_ms' => $elapsed,
        ]);

        return [
            'success' => true,
            'device_id' => $device->id,
            'device_name' => $device->name,
            'batch_id' => $batchId,
            'fetched_count' => count($logs),
            'inserted_count' => $stats['inserted'],
            'duplicate_count' => $stats['duplicates'],
            'unmapped_count' => $stats['unmapped'],
            'affected_dates' => $processedDates,
            'attendance_processed' => $attendanceStats,
            'duration_ms' => $elapsed,
        ];
    }

    /**
     * Store raw punch logs into database with deduplication and student mapping resolution.
     */
    public function storePunchLogs(BiometricDevice $device, array $rawLogs, string $batchId): array
    {
        $inserted = 0;
        $duplicates = 0;
        $unmapped = 0;
        $affectedDates = [];

        // Preload mapping cache for this device
        $mappings = BiometricDeviceUserMapping::where('device_id', $device->id)
            ->pluck('student_id', 'device_user_id')
            ->toArray();

        // Preload existing punches lookup cache to avoid N+1 DB queries per log
        $existingPunches = BiometricPunchLog::where('device_id', $device->id)
            ->select('id', 'device_user_id', 'punch_time', 'verify_type', 'student_id')
            ->get()
            ->keyBy(function ($item) {
                return $item->device_user_id . '_' . $item->punch_time;
            });

        // Preload student user cache for fast fallback
        $allStudents = User::where('role', 'student')->pluck('id', 'id')->toArray();

        $recordsToInsert = [];

        foreach ($rawLogs as $logItem) {
            $deviceUserId = (int)($logItem['user_id'] ?? $logItem['raw_enroll_no'] ?? 0);
            $rawTimestamp = $logItem['timestamp'] ?? now()->toDateTimeString();

            try {
                $punchTime = Carbon::parse($rawTimestamp);
            } catch (\Exception $e) {
                continue;
            }

            $dateStr = $punchTime->toDateString();
            $verifyType = (string)($logItem['verify_type'] ?? '1');
            $sensorNo = (int)($logItem['sensor_no'] ?? 1);

            // Resolve student ID
            $studentId = $mappings[$deviceUserId] ?? null;

            // Fallback: If not mapped explicitly, check if a student exists whose ID matches directly
            if (!$studentId && isset($allStudents[$deviceUserId])) {
                $studentId = $deviceUserId;
                // Auto-record mapping
                BiometricDeviceUserMapping::updateOrCreate(
                    ['device_id' => $device->id, 'device_user_id' => $deviceUserId],
                    [
                        'student_id' => $studentId,
                        'enrollment_status' => 'enrolled',
                        'enrolled_at' => now(),
                    ]
                );
                $mappings[$deviceUserId] = $studentId;
            }

            if (!$studentId) {
                $unmapped++;
            }

            // Fast in-memory deduplication check
            $lookupKey = $deviceUserId . '_' . $punchTime->toDateTimeString();
            if (isset($existingPunches[$lookupKey])) {
                $duplicates++;
                continue;
            }

            $recordsToInsert[] = [
                'device_id' => $device->id,
                'device_user_id' => $deviceUserId,
                'student_id' => $studentId,
                'punch_time' => $punchTime->toDateTimeString(),
                'verify_type' => $verifyType,
                'sensor_no' => $sensorNo,
                'raw_payload' => json_encode($logItem),
                'is_processed' => 0,
                'sync_batch' => $batchId,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ];

            // Mark this date as requiring recalculation
            $affectedDates[$dateStr] = true;
            $inserted++;
        }

        // Batch insert newly discovered punches
        if (!empty($recordsToInsert)) {
            foreach (array_chunk($recordsToInsert, 100) as $chunk) {
                BiometricPunchLog::insert($chunk);
            }
        }

        // If no new punches were inserted, only recalculate today's attendance
        $finalDates = !empty($affectedDates) ? array_keys($affectedDates) : [Carbon::today()->toDateString()];

        return [
            'inserted' => $inserted,
            'duplicates' => $duplicates,
            'unmapped' => $unmapped,
            'affected_dates' => $finalDates,
        ];
    }

    /**
     * Inject simulated punch logs (useful for manual punch testing, simulator, and automated test cases).
     */
    public function injectSimulatedPunches(BiometricDevice $device, array $punches): array
    {
        $batchId = 'SIM_' . $device->id . '_' . time();
        $formattedLogs = [];

        foreach ($punches as $p) {
            $formattedLogs[] = [
                'device_id' => $device->machine_number ?: 1,
                'user_id' => (int)($p['user_id'] ?? $p['device_user_id']),
                'timestamp' => $p['timestamp'] ?? Carbon::now()->toDateTimeString(),
                'verify_type' => (int)($p['verify_type'] ?? 1),
                'sensor_no' => (int)($p['sensor_no'] ?? 1),
            ];
        }

        $stats = $this->storePunchLogs($device, $formattedLogs, $batchId);

        // Process attendance
        foreach ($stats['affected_dates'] as $date) {
            $this->attendanceProcessor->processDate($date);
        }

        return $stats;
    }
}
