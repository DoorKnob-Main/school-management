<?php

namespace App\Services;

use App\Models\BiometricDevice;
use App\Models\BiometricAuditLog;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BiometricDeviceService
{
    protected $testerExePath;

    public function __construct()
    {
        $this->testerExePath = base_path('bin/m50_bridge/M50DeviceTester.exe');
    }

    /**
     * Test connection to a biometric device.
     */
    public function testConnection(BiometricDevice $device): array
    {
        $ip = $device->ip_address;
        $port = $device->port ?: 5005;
        $password = $device->communication_password ?: '0';
        $machineNo = $device->machine_number ?: 1;

        $result = $this->executeCommand('ping', [$ip, $port]);

        if (isset($result['reachable']) && $result['reachable']) {
            // Also try SDK handshake
            $sdkResult = $this->executeCommand('connect', [$ip, $port, $password, $machineNo]);
            if (isset($sdkResult['status']) && $sdkResult['status'] === 'success') {
                $device->update([
                    'status' => 'online',
                    'last_connected_at' => now(),
                    'last_error' => null,
                ]);

                BiometricAuditLog::log('device_ping_success', [
                    'device_id' => $device->id,
                    'ip' => $ip,
                    'port' => $port,
                ]);

                return [
                    'success' => true,
                    'message' => 'Device is ONLINE and responding to M50 SDK protocol.',
                    'data' => $sdkResult,
                ];
            }
        }

        $errorMsg = $result['error'] ?? 'Connection timed out or target machine unreachable.';
        $device->update([
            'status' => 'offline',
            'last_error' => $errorMsg,
        ]);

        return [
            'success' => false,
            'message' => 'Device unreachable: ' . $errorMsg,
            'data' => $result,
        ];
    }

    /**
     * Retrieve system info and capacity statistics from device.
     */
    public function getSystemInfo(BiometricDevice $device): array
    {
        $ip = $device->ip_address;
        $port = $device->port ?: 5005;
        $password = $device->communication_password ?: '0';
        $machineNo = $device->machine_number ?: 1;

        return $this->executeCommand('sysinfo', [$ip, $port, $password, $machineNo]);
    }

    /**
     * Download attendance logs from device.
     */
    public function fetchAttendanceLogs(BiometricDevice $device): array
    {
        $ip = $device->ip_address;
        $port = $device->port ?: 5005;
        $password = $device->communication_password ?: '0';
        $machineNo = $device->machine_number ?: 1;

        $result = $this->executeCommand('getlogs', [$ip, $port, $password, $machineNo]);

        if (isset($result['status']) && $result['status'] === 'success') {
            $device->update([
                'status' => 'online',
                'last_connected_at' => now(),
                'last_sync_at' => now(),
                'last_error' => null,
            ]);

            return [
                'success' => true,
                'count' => $result['count'] ?? count($result['logs'] ?? []),
                'logs' => $result['logs'] ?? [],
            ];
        }

        $device->update([
            'status' => 'error',
            'last_error' => $result['message'] ?? 'Failed to download logs',
        ]);

        return [
            'success' => false,
            'message' => $result['message'] ?? 'Failed to download logs from device',
            'logs' => [],
        ];
    }

    /**
     * Fetch enrolled users from device.
     */
    public function fetchEnrolledUsers(BiometricDevice $device): array
    {
        $ip = $device->ip_address;
        $port = $device->port ?: 5005;
        $password = $device->communication_password ?: '0';
        $machineNo = $device->machine_number ?: 1;

        $result = $this->executeCommand('getusers', [$ip, $port, $password, $machineNo]);

        if (isset($result['status']) && $result['status'] === 'success') {
            return [
                'success' => true,
                'count' => $result['count'] ?? count($result['users'] ?? []),
                'users' => $result['users'] ?? [],
            ];
        }

        return [
            'success' => false,
            'message' => $result['message'] ?? 'Failed to fetch users from device',
            'users' => [],
        ];
    }

    /**
     * Enroll a student user onto the biometric device.
     * Follows the required SDK order: SetEnrollData1 FIRST, then SetUserName1.
     */
    public function enrollUser(BiometricDevice $device, int $deviceUserId, string $userName, int $cardNo = 0, int $userPassword = 1234, int $privilege = 0): array
    {
        $ip = $device->ip_address;
        $port = $device->port ?: 5005;
        $commPassword = $device->communication_password ?: '0';
        $machineNo = $device->machine_number ?: 1;

        $safeName = escapeshellarg($userName);

        $result = $this->executeCommand('adduser', [
            $deviceUserId,
            $safeName,
            $cardNo,
            $userPassword,
            $privilege,
            $ip,
            $port,
            $commPassword,
            $machineNo,
        ]);

        $success = isset($result['status']) && $result['status'] === 'success';

        BiometricAuditLog::log('device_user_enrolled', [
            'device_id' => $device->id,
            'device_user_id' => $deviceUserId,
            'name' => $userName,
            'card_no' => $cardNo,
            'success' => $success,
        ]);

        return [
            'success' => $success,
            'message' => $success ? 'User successfully enrolled on device.' : ($result['message'] ?? 'Failed to enroll user on device'),
            'data' => $result,
        ];
    }

    /**
     * Delete an enrolled user from the device.
     */
    public function deleteUser(BiometricDevice $device, int $deviceUserId): array
    {
        $ip = $device->ip_address;
        $port = $device->port ?: 5005;
        $commPassword = $device->communication_password ?: '0';
        $machineNo = $device->machine_number ?: 1;

        $result = $this->executeCommand('deleteuser', [
            $deviceUserId,
            $ip,
            $port,
            $commPassword,
            $machineNo,
        ]);

        $success = isset($result['status']) && $result['status'] === 'success';

        BiometricAuditLog::log('device_user_deleted', [
            'device_id' => $device->id,
            'device_user_id' => $deviceUserId,
            'success' => $success,
        ]);

        return [
            'success' => $success,
            'message' => $success ? 'User removed from device.' : ($result['message'] ?? 'Failed to remove user from device'),
            'data' => $result,
        ];
    }

    /**
     * Synchronize device clock with server/PC time.
     */
    public function syncDeviceTime(BiometricDevice $device): array
    {
        $ip = $device->ip_address;
        $port = $device->port ?: 5005;
        $commPassword = $device->communication_password ?: '0';
        $machineNo = $device->machine_number ?: 1;

        $result = $this->executeCommand('synctime', [
            $ip,
            $port,
            $commPassword,
            $machineNo,
        ]);

        $success = isset($result['status']) && $result['status'] === 'success';

        BiometricAuditLog::log('device_time_synced', [
            'device_id' => $device->id,
            'success' => $success,
        ]);

        return [
            'success' => $success,
            'message' => $success ? 'Device clock synchronized.' : ($result['message'] ?? 'Failed to sync clock'),
            'data' => $result,
        ];
    }

    /**
     * Trigger door unlock relay on device.
     */
    public function unlockDoor(BiometricDevice $device, int $delaySeconds = 5): array
    {
        $ip = $device->ip_address;
        $port = $device->port ?: 5005;
        $commPassword = $device->communication_password ?: '0';
        $machineNo = $device->machine_number ?: 1;

        $result = $this->executeCommand('unlockdoor', [
            $delaySeconds,
            $ip,
            $port,
            $commPassword,
            $machineNo,
        ]);

        $success = isset($result['status']) && $result['status'] === 'success';

        BiometricAuditLog::log('door_unlocked', [
            'device_id' => $device->id,
            'delay_seconds' => $delaySeconds,
            'success' => $success,
        ]);

        return [
            'success' => $success,
            'message' => $success ? "Door unlocked for {$delaySeconds} seconds." : ($result['message'] ?? 'Failed to unlock door'),
            'data' => $result,
        ];
    }

    /**
     * Clear general attendance logs from machine memory (Non-automatic, Admin only).
     */
    public function clearDeviceLogs(BiometricDevice $device): array
    {
        $ip = $device->ip_address;
        $port = $device->port ?: 5005;
        $commPassword = $device->communication_password ?: '0';
        $machineNo = $device->machine_number ?: 1;

        $result = $this->executeCommand('clearlogs', [
            $ip,
            $port,
            $commPassword,
            $machineNo,
        ]);

        $success = isset($result['status']) && $result['status'] === 'success';

        BiometricAuditLog::log('device_logs_cleared', [
            'device_id' => $device->id,
            'success' => $success,
        ]);

        return [
            'success' => $success,
            'message' => $success ? 'Device log buffer cleared.' : ($result['message'] ?? 'Failed to clear logs'),
            'data' => $result,
        ];
    }

    /**
     * Execute CLI command or handle fallback.
     */
    protected function executeCommand(string $command, array $args = []): array
    {
        // Extract IP and Port for fast socket preflight
        $ip = null;
        $port = 5005;

        if (in_array($command, ['ping', 'connect', 'sysinfo', 'getlogs', 'getusers', 'clearlogs', 'synctime'])) {
            $ip = $args[0] ?? null;
            $port = (int)($args[1] ?? 5005);
        } elseif (in_array($command, ['deleteuser', 'unlockdoor'])) {
            $ip = $args[1] ?? null;
            $port = (int)($args[2] ?? 5005);
        } elseif ($command === 'adduser') {
            $ip = $args[5] ?? null;
            $port = (int)($args[6] ?? 5005);
        }

        // Fast socket preflight check (2 seconds max) to prevent PHP execution timeouts on offline devices
        if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
            $ping = $this->socketPing($ip, $port, 2);
            if (!$ping['reachable']) {
                return [
                    'status' => 'error',
                    'reachable' => false,
                    'message' => "Device at {$ip}:{$port} is offline or unreachable (Socket timed out after 2s).",
                ];
            }
        }

        if (file_exists($this->testerExePath)) {
            $argString = implode(' ', array_map(function ($arg) {
                return (is_string($arg) && strpos($arg, ' ') !== false && $arg[0] !== '"' && $arg[0] !== "'") 
                    ? escapeshellarg($arg) 
                    : $arg;
            }, $args));

            $cmd = "\"{$this->testerExePath}\" {$command} {$argString} --json";

            try {
                $output = shell_exec($cmd . ' 2>&1');
                if ($output) {
                    $jsonStart = strpos($output, '{');
                    if ($jsonStart !== false) {
                        $jsonStr = substr($output, $jsonStart);
                        $decoded = json_decode($jsonStr, true);
                        if (is_array($decoded)) {
                            return $decoded;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('M50DeviceTester execution error: ' . $e->getMessage());
            }
        }

        // Direct Socket fallback for TCP Ping if binary execution unavailable
        if ($command === 'ping' && $ip) {
            return $this->socketPing($ip, $port);
        }

        return [
            'status' => 'error',
            'message' => 'Command failed or device bridge returned invalid response',
        ];
    }

    /**
     * Pure PHP TCP socket check.
     */
    protected function socketPing(string $ip, int $port, int $timeoutSec = 3): array
    {
        $startTime = microtime(true);
        $fp = @fsockopen($ip, $port, $errno, $errstr, $timeoutSec);
        $elapsed = round((microtime(true) - $startTime) * 1000);

        if ($fp) {
            fclose($fp);
            return [
                'status' => 'success',
                'ip' => $ip,
                'port' => $port,
                'reachable' => true,
                'response_time_ms' => $elapsed,
                'error' => null,
            ];
        }

        return [
            'status' => 'error',
            'ip' => $ip,
            'port' => $port,
            'reachable' => false,
            'response_time_ms' => $elapsed,
            'error' => $errstr ?: 'Connection refused / unreachable',
        ];
    }
}
