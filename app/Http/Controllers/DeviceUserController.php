<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Device;
use App\Models\Employee;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;

class DeviceUserController extends Controller
{
    public function index(Request $request)
    {
        $devices = Device::where('is_active', true)->get();
        $deviceUsers = [];
        $selectedDevice = null;
        $error = null;
        $systemEmployees = collect([]);

        if ($request->has('device_id')) {
            $selectedDevice = Device::findOrFail($request->device_id);

            // HYBRID CHECK
            if (config('app.env') === 'local') {
                $pythonPath = env('PYTHON_SCRIPT_PATH', base_path('python/device_sync.py'));
                $python = env('PYTHON_PATH', 'python');

                $cmd = escapeshellarg($python).' '.escapeshellarg($pythonPath).' --device-id '.(int) $selectedDevice->id.' --list-users --json';

                $process = Process::run($cmd);

                if ($process->successful()) {
                    $output = $process->output();
                    $result = json_decode(trim($output), true);
                    if ($result && isset($result['success']) && $result['success']) {
                        $rawUsers = $result['users'] ?? [];

                        // Fetch existing system employees to cross-reference
                        $existingBiometricIds = Employee::pluck('biometric_id')->toArray();

                        foreach ($rawUsers as $u) {
                            $deviceUsers[] = [
                                'uid' => $u['uid'],
                                'user_id' => $u['user_id'],
                                'name' => $u['name'],
                                'privilege' => $u['privilege'] == 14 ? 'Super Admin' : 'Normal User',
                                'card' => $u['card'],
                                'fingerprint_count' => $u['fingerprint_count'],
                                'exists_in_system' => in_array($u['user_id'], $existingBiometricIds),
                            ];
                        }
                    } else {
                        $error = $result['message'] ?? 'Failed to parse device data. Raw output: '.$output;
                    }
                } else {
                    $error = 'Failed to execute Python script: '.($process->errorOutput() ?: $process->output());
                }
            } else {
                // CLOUD MODE
                if (! $selectedDevice->is_online) {
                    $error = 'Bridge is offline. Please ensure the Profitix Sync Bridge EXE is running at the office.';
                } else {
                    // Try to fetch from cache (synced via BiometricApiController)
                    $rawUsers = Cache::get("device_users_{$selectedDevice->id}", []);

                    if (empty($rawUsers)) {
                        // Optional: you could add a notice that user list needs to be refreshed
                    } else {
                        $existingBiometricIds = Employee::pluck('biometric_id')->toArray();

                        foreach ($rawUsers as $u) {
                            $deviceUsers[] = [
                                'uid' => $u['uid'],
                                'user_id' => $u['user_id'],
                                'name' => $u['name'],
                                'privilege' => $u['privilege'] == 14 ? 'Super Admin' : 'Normal User',
                                'card' => $u['card'] ?? '—',
                                'fingerprint_count' => $u['fingerprint_count'] ?? 0,
                                'exists_in_system' => in_array($u['user_id'], $existingBiometricIds),
                            ];
                        }
                    }
                }
            }

            $systemEmployees = Employee::with('department')->get();
        }

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('devices.users', compact('devices', 'deviceUsers', 'selectedDevice', 'error', 'systemEmployees', 'departments'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'selected_users' => 'required|array',
        ]);

        $importedCount = 0;
        $skippedCount = 0;

        try {
            foreach ($request->selected_users as $bioId => $name) {
                $name = ! empty(trim($name)) ? trim($name) : 'Device User '.$bioId;

                // Check if exists
                $exists = Employee::where('biometric_id', $bioId)->exists();

                if (! $exists) {
                    // Generate employee code
                    $maxId = Employee::max('id') ?? 0;
                    $empCode = 'EMP'.str_pad($maxId + 1, 4, '0', STR_PAD_LEFT);

                    Employee::create([
                        'employee_code' => $empCode,
                        'full_name' => $name,
                        'biometric_id' => $bioId,
                        'join_date' => now()->toDateString(),
                        'status' => 'active',
                    ]);
                    $importedCount++;
                } else {
                    $skippedCount++;
                }
            }
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import failed. Database error occurred.',
                ]);
            }

            return redirect()->back()->with('error', 'Import failed. Database error occurred.');
        }

        AuditService::log("Imported {$importedCount} employees from device ID {$request->device_id}");

        $msg = "Import complete! Imported: {$importedCount}. Skipped: {$skippedCount}.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'imported' => $importedCount,
                'skipped' => $skippedCount,
                'message' => $msg,
            ]);
        }

        return redirect()->route('devices.users', ['device_id' => $request->device_id])
            ->with('success', $msg);
    }

    public function pushToDevice(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        $device = Device::findOrFail($request->device_id);
        $employees = Employee::whereIn('id', $request->employee_ids)->get();
        $successCount = 0;
        $errors = [];

        // HYBRID LOGIC
        if (config('app.env') === 'local') {
            $pythonPath = env('PYTHON_SCRIPT_PATH', base_path('python/device_sync.py'));
            $python = env('PYTHON_PATH', 'python');

            foreach ($employees as $emp) {
                $name = $emp->full_name;
                $bioId = $emp->biometric_id;

                if (empty($bioId)) {
                    $errors[] = "Employee {$name} skipped: Missing Biometric ID.";

                    continue;
                }

                $cmd = escapeshellarg($python).' '.escapeshellarg($pythonPath).
                       ' --device-id '.(int) $device->id.
                       ' --push-user --user-id '.escapeshellarg($bioId).
                       ' --name '.escapeshellarg($name).
                       ' --json';

                $process = Process::run($cmd);

                if ($process->successful()) {
                    $output = $process->output();
                    $result = json_decode(trim($output), true);
                    if ($result && isset($result['success']) && $result['success']) {
                        $successCount++;
                    } else {
                        $errors[] = "Failed to push {$name}: ".($result['message'] ?? $output);
                    }
                } else {
                    $errors[] = "Failed to execute python for {$name}: ".($process->errorOutput() ?: $process->output());
                }
            }
        } else {
            // CLOUD MODE: Queue the first one or batch (simple version: queue the last one for now or rethink)
            // For simplicity, we just queue the first selected user as a JSON command.
            // In a better version, we'd have a command queue table.
            $emp = $employees->first();
            if ($emp) {
                $command = json_encode([
                    'action' => 'push_user',
                    'user_id' => $emp->biometric_id,
                    'name' => $emp->full_name,
                ]);
                $device->update(['pending_command' => $command]);
                $successCount = 1;
                $msg = "Push command for {$emp->full_name} queued. Bridge will process it shortly.";
            }
        }

        AuditService::log("Pushed/Queued {$successCount} employees to device ID {$device->id}");

        $msg = $msg ?? "Pushed {$successCount} employees to {$device->name}.";
        $hasErrors = count($errors) > 0;
        $fullMsg = $hasErrors ? $msg.' Some failed: '.implode(' | ', $errors) : $msg;

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => ! $hasErrors,
                'pushed' => $successCount,
                'errors' => $errors,
                'message' => $fullMsg,
            ]);
        }

        if ($hasErrors) {
            return redirect()->back()->with('error', $fullMsg);
        }

        return redirect()->back()->with('success', $msg);
    }

    public function deleteFromDevice(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'uid' => 'required|integer',
            'user_id' => 'required|string',
        ]);

        $device = Device::findOrFail($request->device_id);

        if (config('app.env') === 'local') {
            $pythonPath = env('PYTHON_SCRIPT_PATH', base_path('python/device_sync.py'));
            $python = env('PYTHON_PATH', 'python');

            $cmd = escapeshellarg($python).' '.escapeshellarg($pythonPath).
                   ' --device-id '.(int) $device->id.
                   ' --delete-user --uid '.(int) $request->uid.
                   ' --user-id '.escapeshellarg($request->user_id).
                   ' --json';

            $process = Process::run($cmd);

            if ($process->successful()) {
                $output = $process->output();
                $result = json_decode(trim($output), true);
                if ($result && isset($result['success']) && $result['success']) {
                    AuditService::log("Deleted user ID {$request->user_id} from device {$device->name}");
                    $msg = $result['message'] ?? 'User deleted from device.';
                    if ($request->wantsJson() || $request->ajax()) {
                        return response()->json(['success' => true, 'message' => $msg]);
                    }

                    return redirect()->back()->with('success', $msg);
                }
                $error = $result['message'] ?? $output;
            } else {
                $error = $process->errorOutput() ?: $process->output();
            }
        } else {
            // CLOUD MODE: Queue command
            $command = json_encode([
                'action' => 'delete_user',
                'uid' => $request->uid,
                'user_id' => $request->user_id,
            ]);
            $device->update(['pending_command' => $command]);
            $msg = "Delete command for user {$request->user_id} queued.";
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => $msg]);
            }

            return redirect()->back()->with('success', $msg);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => 'Failed to delete user: '.$error]);
        }

        return redirect()->back()->with('error', 'Failed to delete user: '.$error);
    }

    public function enrollFingerprint(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'uid' => 'required|integer',
            'user_id' => 'required|string',
        ]);

        $device = Device::findOrFail($request->device_id);

        if (config('app.env') === 'local') {
            $pythonPath = env('PYTHON_SCRIPT_PATH', base_path('python/device_sync.py'));
            $python = env('PYTHON_PATH', 'python');

            $cmd = escapeshellarg($python).' '.escapeshellarg($pythonPath).
                   ' --device-id '.(int) $device->id.
                   ' --enroll-user --uid '.(int) $request->uid.
                   ' --user-id '.escapeshellarg($request->user_id).
                   ' --json';

            $process = Process::timeout(180)->run($cmd);

            if ($process->successful()) {
                $output = $process->output();
                $result = json_decode(trim($output), true);
                if ($result && isset($result['success']) && $result['success']) {
                    $msg = $result['message'] ?? 'Device entered enrollment mode.';
                    if ($request->wantsJson() || $request->ajax()) {
                        return response()->json(['success' => true, 'message' => $msg]);
                    }

                    return redirect()->back()->with('success', $msg);
                }
                $error = $result['message'] ?? $output;
            } else {
                $error = $process->errorOutput() ?: $process->output();
            }
        } else {
            // CLOUD MODE: Queue command
            $command = json_encode([
                'action' => 'enroll_user',
                'uid' => $request->uid,
                'user_id' => $request->user_id,
            ]);
            $device->update(['pending_command' => $command]);
            $msg = "Enrollment command for user {$request->user_id} queued. Please go to the device to place finger.";
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => $msg]);
            }

            return redirect()->back()->with('success', $msg);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => 'Failed to trigger enrollment: '.$error]);
        }

        return redirect()->back()->with('error', 'Failed to trigger enrollment: '.$error);
    }
}
