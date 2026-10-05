<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Employee;
use App\Services\AttendanceProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IclockController extends Controller
{
    /**
     * Handshake when device connects
     */
    public function handshake(Request $request)
    {
        $sn = $request->query('SN', '');

        // This is the expected format that ADMS devices understand
        $response = "GET OPTION FROM: {$sn}\n"
                  ."ErrorDelay=60\n"
                  ."Delay=30\n"
                  ."TransTimes=00:00;14:00\n"
                  ."TransInterval=1\n"
                  ."TransFlag=1111000000\n"
                  ."Realtime=1\n"
                  .'Encrypt=0';

        // Update or Auto-create device
        $device = Device::where('serial_number', $sn)->orWhere('ip_address', 'like', "%{$sn}%")->first();
        if ($device) {
            $device->update([
                'last_ping' => now(),
                'is_online' => true,
            ]);
        } else {
            Device::create([
                'name' => 'New ADMS Device ('.$sn.')',
                'serial_number' => $sn,
                'ip_address' => 'Cloud',
                'port' => 443,
                'is_online' => true,
                'last_ping' => now(),
                'communication_type' => 'TCP/IP',
            ]);
        }

        return response($response, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Receive pushed data (Attendance, Users, etc.)
     */
    public function receiveData(Request $request)
    {
        $sn = $request->query('SN', '');
        $table = $request->query('table', '');
        $tableName = $request->query('tablename', '');

        if ($table === 'ATTLOG' || $table === 'rtlog') {
            $body = $request->getContent();
            $lines = explode("\n", $body);

            // Find or Auto-create device
            $device = Device::where('serial_number', $sn)
                ->orWhere('ip_address', 'like', "%{$sn}%")
                ->first();

            if (! $device) {
                $device = Device::create([
                    'name' => 'New ADMS Device ('.$sn.')',
                    'serial_number' => $sn,
                    'ip_address' => 'Cloud',
                    'port' => 443,
                    'is_online' => true,
                    'last_ping' => now(),
                    'communication_type' => 'TCP/IP',
                ]);
            }

            $deviceId = $device->id;

            $added = 0;
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                // Default traditional format or Key-Value format
                $userId = null;
                $punchTime = null;
                $statusNum = null;
                $deviceLogIndex = null;

                if (strpos($line, '=') !== false) {
                    // Key-value pairs format (SenseFace 4)
                    $fields = explode("\t", $line);
                    foreach ($fields as $field) {
                        $kv = explode('=', trim($field), 2);
                        if (count($kv) == 2) {
                            $key = strtolower(trim($kv[0]));
                            $val = trim($kv[1]);
                            if ($key == 'pin') {
                                $userId = $val;
                            }
                            if ($key == 'time') {
                                $punchTime = $val;
                            }
                            if ($key == 'status' || $key == 'event') {
                                $statusNum = $val;
                            }
                            if ($key == 'index') {
                                $deviceLogIndex = $val;
                            }
                        }
                    }
                } else {
                    // Traditional format (ATTLOG)
                    $parts = preg_split('/\s+/', $line);
                    if (count($parts) >= 3) {
                        $userId = $parts[0];
                        $punchTime = $parts[1].' '.$parts[2];
                        $statusNum = $parts[3] ?? null;
                    }
                }

                if ($userId && $punchTime) {
                    $employee = Employee::where('employee_code', $userId)
                        ->orWhere('biometric_id', $userId)
                        ->first();

                    // Idempotency check
                    $query = AttendanceLog::where('device_id', $deviceId);
                    if ($deviceLogIndex) {
                        $query->where('device_log_index', $deviceLogIndex);
                    } else {
                        if ($employee) {
                            $query->where('employee_id', $employee->id);
                        } else {
                            $query->where('unmapped_bio_id', $userId);
                        }
                        $query->where('punch_time', $punchTime);
                    }

                    $exists = $query->exists();

                    if (! $exists) {
                        $log = AttendanceLog::create([
                            'employee_id' => $employee ? $employee->id : null,
                            'unmapped_bio_id' => $employee ? null : $userId,
                            'device_id' => $deviceId,
                            'device_log_index' => $deviceLogIndex,
                            'punch_time' => $punchTime,
                            'punch_type' => 'unknown',
                            'source' => 'device',
                            'is_ignored' => false,
                            'raw_data' => $line,
                        ]);

                        app(AttendanceProcessor::class)->process($log);

                        $added++;
                    }
                }
            }
            Log::info("ADMS Sync from {$sn}: Saved {$added} logs.");

            if ($added > 0) {
                app(\App\Services\AttendanceService::class)->processUnprocessedLogs();
            }

            if ($device) {
                $device->update([
                    'last_sync' => now(),
                    'is_online' => true,
                ]);
            }
        } elseif ($table === 'USER' || $table === 'USERINFO' || ($table === 'tabledata' && $tableName === 'user')) {
            $body = $request->getContent();
            $lines = explode("\n", $body);
            $added = 0;

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                $pin = null;
                $name = null;

                if (strpos($line, '=') !== false) {
                    // Key-value format (SenseFace 4)
                    $fields = explode("\t", $line);
                    foreach ($fields as $field) {
                        $kv = explode('=', trim($field), 2);
                        if (count($kv) == 2) {
                            $key = strtolower(trim($kv[0]));
                            $val = trim($kv[1]);
                            if ($key == 'pin') {
                                $pin = $val;
                            }
                            if ($key == 'name') {
                                $name = $val;
                            }
                        }
                    }
                } else {
                    // Traditional format
                    $parts = explode("\t", $line);
                    if (count($parts) >= 1) {
                        $pin = $parts[0];
                        $name = $parts[1] ?? null;
                    }
                }

                if (empty(trim($name))) {
                    $name = "User {$pin}";
                }

                if ($pin) {
                    $emp = Employee::where('biometric_id', $pin)
                        ->orWhere('employee_code', $pin)
                        ->first();
                    if (! $emp) {
                        Employee::create([
                            'employee_code' => $pin,
                            'biometric_id' => $pin,
                            'full_name' => $name,
                            'join_date' => now(),
                            'status' => 'active',
                        ]);
                        $added++;
                    }
                }
            }
            Log::info("ADMS Sync from {$sn}: Saved {$added} users.");
        }

        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Handle ADMS device registration
     */
    public function registry(Request $request)
    {
        $sn = $request->query('SN', '');

        $device = Device::where('serial_number', $sn)
            ->orWhere('ip_address', 'like', "%{$sn}%")
            ->first();

        if (! $device) {
            $device = Device::create([
                'name' => 'ADMS Device ('.$sn.')',
                'serial_number' => $sn,
                'ip_address' => $request->ip() ?? 'Cloud',
                'port' => 443,
                'is_online' => true,
                'last_ping' => now(),
                'communication_type' => 'TCP/IP',
            ]);
        } else {
            $device->update([
                'last_ping' => now(),
                'is_online' => true,
                'ip_address' => $request->ip() ?? $device->ip_address,
            ]);
        }

        // Use a stable registry code for the device
        $registryCode = "1234567890-{$sn}";

        return response("RegistryCode={$registryCode}", 200)
            ->header('Content-Type', 'application/push;charset=UTF-8');
    }

    /**
     * Handle ADMS push check
     */
    public function push(Request $request)
    {
        $sn = $request->query('SN', '');

        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Handle ADMS ping check
     */
    public function ping(Request $request)
    {
        $sn = $request->query('SN', '');

        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Respond to getrequest (Commands)
     */
    public function getRequest(Request $request)
    {
        $sn = $request->query('SN', '');

        // If a sync was requested from the dashboard/browser
        if (Cache::pull("sync_users_{$sn}")) {
            Log::info("ADMS: Sending DATA QUERY USERINFO to {$sn}");

            return response("C:1:DATA QUERY USERINFO\n", 200)->header('Content-Type', 'text/plain');
        }

        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Receive command result
     */
    public function deviceCmd(Request $request)
    {
        $sn = $request->query('SN', '');
        Log::info("ADMS CMD Return from {$sn}: ".$request->getContent());

        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Manual Trigger to Request Users
     */
    public function triggerSync(Request $request)
    {
        $sn = $request->query('SN', '');
        if (empty($sn)) {
            return 'Weka SN (Mfano: /iclock/trigger?SN=CLDF...)';
        }

        Cache::put("sync_users_{$sn}", true, 300);

        return "Amri imetumwa! Mashine (SN: {$sn}) italeta wafanyakazi ndani ya dakika 1 ijayo.";
    }
}
