<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;

class DeviceController extends Controller
{
    public function index()
    {
        $devices = Device::withCount('attendanceLogs')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('devices.index', compact('devices'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'ip_address' => 'required|string|max:45',
            'port' => 'required|integer|min:1|max:65535',
            'serial_number' => 'nullable|string',
            'device_model' => 'nullable|string',
            'location' => 'nullable|string',
        ]);

        Device::create($validated);

        return redirect()->route('devices.index')->with('success', 'Device added successfully.');
    }

    public function update(Request $request, Device $device)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'ip_address' => 'required|string|max:45',
            'port' => 'required|integer|min:1|max:65535',
            'serial_number' => 'nullable|string',
            'device_model' => 'nullable|string',
            'location' => 'nullable|string',
        ]);

        $device->update($validated);

        return redirect()->route('devices.index')->with('success', 'Device updated successfully.');
    }

    public function destroy(Device $device)
    {
        $device->delete();

        return redirect()->route('devices.index')->with('success', 'Device deleted.');
    }

    public function testConnection(Device $device)
    {
        $success = false;
        $message = '';

        // For Localhost, try direct connection. For Cloud, check Bridge status.
        if (config('app.env') === 'local') {
            $python = env('PYTHON_PATH', 'python');
            $script = base_path('python/device_sync.py');
            $cmd = escapeshellarg($python).' '.escapeshellarg($script).' --device-id '.(int) $device->id.' --command test --json';

            $process = Process::run($cmd);
            if ($process->successful()) {
                $success = true;
                $message = "Local Connection Successful to {$device->name}.";
            } else {
                $message = 'Local connection failed.';
            }
        } else {
            if ($device->is_online) {
                $success = true;
                $message = "Bridge is connected to {$device->name}.";
            } else {
                $message = 'Cannot connect. Check if device is powered on.';
            }
        }

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => $success,
                'message' => $message,
            ]);
        }

        return redirect()->back()->with($success ? 'success' : 'error', $message);
    }

    /**
     * SYNC: Works for both Local (direct python) and Cloud (queue for Bridge)
     */
    public function sync(Request $request, Device $device)
    {
        set_time_limit(300);

        // IF LOCAL: Run python directly
        if (config('app.env') === 'local') {
            $python = env('PYTHON_PATH', 'python3');
            $script = base_path('python/device_sync.py');
            $cmd = $python.' '.escapeshellarg($script).' --device-id '.(int) $device->id.' --json 2>&1';

            try {
                $output = shell_exec($cmd);
                $result = json_decode($output, true);

                if ($result && isset($result['success']) && $result['success']) {
                    $service = new AttendanceService;
                    $service->processUnprocessedLogs();

                    return redirect()->back()->with('success', "Device {$device->name} synced successfully. ".($result['total_records'] ?? 0).' records found.');
                }

                $errorMessage = $result['message'] ?? $output ?? 'No output from python script.';

                return redirect()->back()->with('error', 'Sync failed: '.$errorMessage);
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Execution error: '.$e->getMessage());
            }
        }

        // IF CLOUD (or fallback): Use Bridge Queue
        $device->update(['pending_command' => 'full_sync']);
        $msg = 'Sync command queued. Bridge will process it shortly.';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return redirect()->back()->with('success', $msg);
    }

    public function syncAll()
    {
        set_time_limit(600);

        if (config('app.env') === 'local') {
            $python = env('PYTHON_PATH', 'python3');
            $script = base_path('python/device_sync.py');
            $cmd = $python.' '.escapeshellarg($script).' --all --json 2>&1';

            try {
                $output = shell_exec($cmd);
                $result = json_decode($output, true);

                if ($result && isset($result['success']) && $result['success']) {
                    $service = new AttendanceService;
                    $service->processUnprocessedLogs();

                    return redirect()->back()->with('success', 'All active devices synced. Total records: '.($result['total_records'] ?? 0));
                }

                $errorMessage = $result['message'] ?? $output ?? 'No output from python script.';

                return redirect()->back()->with('error', 'Global sync failed: '.$errorMessage);
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Execution error: '.$e->getMessage());
            }
        }

        return redirect()->back()->with('error', 'Feature only available on localhost.');
    }

    public function executeCommand(Request $request, Device $device)
    {
        $command = $request->input('command');

        // IF LOCAL: Execute immediately
        if (config('app.env') === 'local') {
            $python = env('PYTHON_PATH', 'python');
            $script = base_path('python/device_sync.py');
            $cmd = escapeshellarg($python).' '.escapeshellarg($script).' --device-id '.(int) $device->id.' --command '.escapeshellarg($command).' --json';
            Process::run($cmd);
        }

        // ALWAYS queue for Cloud/Bridge
        $device->update(['pending_command' => $command]);

        $msg = "Command '{$command}' scheduled.";
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Trigger a user list refresh from the device
     */
    public function syncUsers(Request $request, Device $device)
    {
        // Send JSON command so bridge knows which device ID to report back
        $command = json_encode([
            'action' => 'fetch_users',
            'device_id' => $device->id,
        ]);

        $device->update(['pending_command' => $command]);

        $msg = 'User list refresh requested. Bridge will fetch data shortly.';
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }
}
