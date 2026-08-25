<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\BiometricDevice;
use App\Models\BiometricDeviceUserMapping;
use App\Models\BiometricPunchLog;
use App\Models\Attendance;
use App\Services\BiometricSyncService;
use App\Services\BiometricAttendanceProcessor;
use Carbon\Carbon;

class BiometricPunchFixTest extends TestCase
{
    use RefreshDatabase;

    protected $syncService;
    protected $processor;
    protected $device;
    protected $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->syncService = app(BiometricSyncService::class);
        $this->processor = app(BiometricAttendanceProcessor::class);

        $this->device = BiometricDevice::create([
            'name' => 'Main Gate M50 Terminal',
            'device_identifier' => 'DEV_GATE_01',
            'ip_address' => '192.168.1.201',
            'port' => 5005,
            'machine_number' => 1,
            'status' => 'online',
            'serial_number' => 'TEST123456',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    /** @test */
    public function it_extracts_raw_enroll_no_when_user_id_is_zero_string()
    {
        // Simulate raw logs returned from bridge where user_id is "0" but raw_enroll_no is 5
        $rawLogs = [
            [
                'device_id' => 1,
                'user_id' => '0',
                'raw_enroll_no' => $this->student->id,
                'timestamp' => Carbon::today()->setTime(8, 15, 0)->toDateTimeString(),
                'verify_type' => 1,
                'sensor_no' => 1,
            ]
        ];

        $stats = $this->syncService->storePunchLogs($this->device, $rawLogs, 'TEST_BATCH_1');

        $this->assertEquals(1, $stats['inserted']);

        $punchLog = BiometricPunchLog::where('device_id', $this->device->id)->first();
        $this->assertNotNull($punchLog);
        $this->assertEquals($this->student->id, $punchLog->device_user_id);
        $this->assertEquals($this->student->id, $punchLog->student_id);
    }

    /** @test */
    public function it_self_heals_legacy_user_zero_punch_logs_during_sync()
    {
        // Insert a legacy bad log with device_user_id = 0
        $legacyLog = BiometricPunchLog::create([
            'device_id' => $this->device->id,
            'device_user_id' => 0,
            'student_id' => null,
            'punch_time' => Carbon::today()->setTime(8, 10, 0)->toDateTimeString(),
            'verify_type' => '1',
            'sensor_no' => 1,
            'raw_payload' => json_encode([
                'user_id' => '0',
                'raw_enroll_no' => $this->student->id,
                'timestamp' => Carbon::today()->setTime(8, 10, 0)->toDateTimeString(),
            ]),
            'is_processed' => 0,
            'sync_batch' => 'LEGACY_BATCH',
        ]);

        // Run storePunchLogs with new sync batch
        $this->syncService->storePunchLogs($this->device, [], 'HEAL_BATCH');

        $legacyLog->refresh();
        $this->assertEquals($this->student->id, $legacyLog->device_user_id);
        $this->assertEquals($this->student->id, $legacyLog->student_id);
    }

    /** @test */
    public function it_ignores_rapid_double_scans_within_5_minutes_and_prevents_false_early_leave()
    {
        // Student punches at 08:15:00 and again at 08:15:15 (15 seconds later double-scan)
        BiometricPunchLog::create([
            'device_id' => $this->device->id,
            'device_user_id' => $this->student->id,
            'student_id' => $this->student->id,
            'punch_time' => Carbon::today()->setTime(8, 15, 0)->toDateTimeString(),
            'verify_type' => '1',
            'sensor_no' => 1,
            'is_processed' => 0,
        ]);

        BiometricPunchLog::create([
            'device_id' => $this->device->id,
            'device_user_id' => $this->student->id,
            'student_id' => $this->student->id,
            'punch_time' => Carbon::today()->setTime(8, 15, 15)->toDateTimeString(),
            'verify_type' => '1',
            'sensor_no' => 1,
            'is_processed' => 0,
        ]);

        $this->processor->processDate(Carbon::today()->toDateString());

        $attendance = Attendance::where('student_id', $this->student->id)
            ->whereDate('created_at', Carbon::today()->toDateString())
            ->first();

        $this->assertNotNull($attendance);
        $this->assertEquals('on', $attendance->status);
        $this->assertEquals(0, $attendance->early_leave_minutes);
        $this->assertNull($attendance->out_time);
    }

    /** @test */
    public function it_records_valid_checkout_when_second_punch_is_at_least_5_minutes_later()
    {
        // Entry punch at 08:15
        BiometricPunchLog::create([
            'device_id' => $this->device->id,
            'device_user_id' => $this->student->id,
            'student_id' => $this->student->id,
            'punch_time' => Carbon::today()->setTime(8, 15, 0)->toDateTimeString(),
            'verify_type' => '1',
            'sensor_no' => 1,
            'is_processed' => 0,
        ]);

        // Exit punch at 14:30
        BiometricPunchLog::create([
            'device_id' => $this->device->id,
            'device_user_id' => $this->student->id,
            'student_id' => $this->student->id,
            'punch_time' => Carbon::today()->setTime(14, 30, 0)->toDateTimeString(),
            'verify_type' => '1',
            'sensor_no' => 1,
            'is_processed' => 0,
        ]);

        $this->processor->processDate(Carbon::today()->toDateString());

        $attendance = Attendance::where('student_id', $this->student->id)
            ->whereDate('created_at', Carbon::today()->toDateString())
            ->first();

        $this->assertNotNull($attendance);
        $this->assertEquals('on', $attendance->status);
        $this->assertNotNull($attendance->out_time);
        $this->assertEquals(0, $attendance->early_leave_minutes);
    }

    /** @test */
    public function it_deduplicates_duplicate_raw_logs_in_same_batch()
    {
        $duplicateLogs = [
            [
                'device_id' => 1,
                'user_id' => (string)$this->student->id,
                'raw_enroll_no' => $this->student->id,
                'timestamp' => '2026-08-20 14:12:08',
                'verify_type' => 407,
                'sensor_no' => 1,
            ],
            [
                'device_id' => 1,
                'user_id' => (string)$this->student->id,
                'raw_enroll_no' => $this->student->id,
                'timestamp' => '2026-08-20 14:12:08',
                'verify_type' => 407,
                'sensor_no' => 1,
            ],
        ];

        $stats = $this->syncService->storePunchLogs($this->device, $duplicateLogs, 'DUP_BATCH');

        $this->assertEquals(1, $stats['inserted']);
        $this->assertEquals(1, $stats['duplicates']);
    }
}
