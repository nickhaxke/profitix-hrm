<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Employee;
use App\Models\License;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BiometricApiController extends Controller
{
    public function syncLogs(Request $request)
    {
        try {
            // 1. Dynamic Security Check (License Key Validation)
            $token = $request->header('X-Bridge-Token');
            $license = License::where('license_key', $token)->first();

            if (! $license || $license->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized Bridge',
                    'details' => 'Invalid or inactive License Key. Please check your Bridge Token settings.',
                ], 401);
            }

            $logs = $request->input('logs', []);
            $deviceId = $request->input('device_id');

            if (! $deviceId) {
                return response()->json(['success' => false, 'error' => 'Missing device_id in request'], 400);
            }

            // Fetch the device early to avoid "null" errors later
            $device = Device::find($deviceId);
            if (! $device) {
                return response()->json([
                    'success' => false,
                    'error' => 'Device ID Not Found',
                    'details' => "The Bridge is sending ID: $deviceId, but no device with this ID exists in the Cloud database. Please check your settings.json and the Devices table in CPanel.",
                ], 200);
            }

            // Heartbeat & Status Update
            $deviceStatus = $request->input('device_status', 'offline');
            $pendingCmd = $device->pending_command;

            $device->update([
                'is_online' => ($deviceStatus === 'online'),
                'last_sync' => now(),
                'pending_command' => $pendingCmd ? null : $device->pending_command,
            ]);

            if (empty($logs)) {
                return response()->json([
                    'success' => true,
                    'command' => $pendingCmd,
                    'message' => 'Heartbeat OK',
                ]);
            }

            $insertedCount = 0;
            $processedDates = [];

            // Get employees mapping using the correct column 'biometric_id'
            $employees = Employee::whereNotNull('biometric_id')
                ->pluck('id', 'biometric_id')
                ->toArray();

            DB::beginTransaction();

            foreach ($logs as $log) {
                $bioId = (string) $log['user_id'];
                $employeeId = $employees[$bioId] ?? null;

                if (! $employeeId) {
                    \Log::warning("Cloud Sync: Skipping unknown biometric ID: {$bioId}");

                    continue;
                }

                // Map ZK status (0=Check-In, 1=Check-Out, 4=Overtime-In, 5=Overtime-Out)
                $status = $log['status'] ?? 0;
                $punchType = in_array($status, [0, 4]) ? 'in' : (in_array($status, [1, 5]) ? 'out' : 'unknown');

                $exists = AttendanceLog::where('employee_id', $employeeId)
                    ->where('punch_time', $log['timestamp'])
                    ->exists();

                if (! $exists) {
                    AttendanceLog::create([
                        'employee_id' => $employeeId,
                        'device_id' => $deviceId,
                        'punch_time' => $log['timestamp'],
                        'punch_type' => $punchType,
                        'source' => 'device',
                        'is_processed' => false,
                    ]);
                    $insertedCount++;

                    $date = Carbon::parse($log['timestamp'])->toDateString();
                    $processedDates[$date] = true;
                }
            }

            DB::commit();

            if ($insertedCount > 0) {
                $service = new AttendanceService;
                foreach (array_keys($processedDates) as $date) {
                    $service->processDate($date);
                }
            }

            return response()->json([
                'success' => true,
                'inserted' => $insertedCount,
                'command' => $pendingCmd ?? null,
                'message' => "Synced $insertedCount new logs successfully",
            ]);

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'success' => false,
                'error' => 'Cloud Logic Error',
                'details' => $e->getMessage(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine(),
            ], 200);
        }
    }

    public function syncUsers(Request $request)
    {
        $token = $request->header('X-Bridge-Token');
        $license = License::where('license_key', $token)->first();

        if (! $license || $license->status !== 'active') {
            return response()->json(['success' => false, 'error' => 'Unauthorized Bridge'], 401);
        }

        $request->validate([
            'device_id' => 'required',
            'users' => 'required|array',
        ]);

        $device = Device::findOrFail($request->device_id);

        // Store in cache for 24 hours
        Cache::put("device_users_{$device->id}", $request->users, now()->addHours(24));

        \Log::info("User list updated for Device {$device->name}. Total: ".count($request->users));

        return response()->json([
            'success' => true,
            'message' => 'User list updated in cloud.',
        ]);
    }
}
