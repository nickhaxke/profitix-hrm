<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BiometricApiController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BulkReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DeviceUserController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\IclockController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\CheckLicense;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

// License Activation (always accessible)
Route::get('/license/activate', [LicenseController::class, 'activate'])->name('license.activate');
Route::post('/license/activate', [LicenseController::class, 'processActivation'])->name('license.process');

// Biometric Bridge API (Called by EXE)
Route::prefix('bridge')->group(function () {
    Route::post('/sync', [BiometricApiController::class, 'syncLogs']);
    Route::post('/sync-users', [BiometricApiController::class, 'syncUsers']);
    Route::get('/ping', [BiometricApiController::class, 'ping']);
});

// ADMS Receiver Routes (ZKTeco Cloud Server)
Route::prefix('iclock')->group(function () {
    Route::get('/cdata', [IclockController::class, 'handshake']);
    Route::post('/cdata', [IclockController::class, 'receiveData']);
    Route::post('/registry', [IclockController::class, 'registry']);
    Route::post('/push', [IclockController::class, 'push']);
    Route::get('/ping', [IclockController::class, 'ping']);
    Route::get('/getrequest', [IclockController::class, 'getRequest']);
    Route::post('/devicecmd', [IclockController::class, 'deviceCmd']);
    Route::get('/trigger', [IclockController::class, 'triggerSync']);
});

// Authentication routes
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        auth()->user()->update(['last_login_at' => now()]);

        return redirect()->intended('dashboard');
    }

    return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
})->name('login.post');

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/login');
})->name('logout');

// Protected Routes
Route::middleware(['auth', CheckLicense::class])->group(function () {

    Route::get('/', fn () => redirect('/dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/list', [DashboardController::class, 'getList'])->name('dashboard.list');

    // Available to admin, hr, manager
    Route::middleware('role:admin,hr,manager')->group(function () {
        // Attendance Views & Reports
        Route::get('/attendance/logs', [AttendanceController::class, 'logs'])->name('attendance.logs');
        Route::get('/attendance/summary', [AttendanceController::class, 'summary'])->name('attendance.summary');
        Route::post('/attendance/manual-punch', [AttendanceController::class, 'manualPunch'])->name('attendance.manual-punch');
        Route::post('/attendance/import-file', [AttendanceController::class, 'importFile'])->name('attendance.import-file');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/process', [ReportController::class, 'process'])->name('reports.process');
        Route::get('/exports/report', [ExportController::class, 'export'])->name('exports.report');
        Route::get('/exports/employees', [ExportController::class, 'employeesCsv'])->name('exports.employees');
        Route::get('/exports/bulk-reports', [BulkReportController::class, 'exportAll'])->name('exports.bulk-reports');
        Route::post('/exports/email-reports', [BulkReportController::class, 'emailAll'])->name('exports.email-reports');

        // Leave
        Route::get('/leave', [LeaveController::class, 'index'])->name('leave.index');
        Route::post('/leave', [LeaveController::class, 'store'])->name('leave.store');
    });

    // Available to admin and hr only
    Route::middleware('role:admin,hr')->group(function () {
        // Organization
        Route::resource('employees', EmployeeController::class)->except(['show']);
        Route::resource('departments', DepartmentController::class)->except(['show', 'create', 'edit']);
        Route::resource('branches', BranchController::class)->except(['show', 'create', 'edit']);

        // Attendance processing & shifts
        Route::post('/attendance/process', [AttendanceController::class, 'process'])->name('attendance.process');
        Route::post('/attendance/repair', [AttendanceController::class, 'repair'])->name('attendance.repair');
        Route::post('/attendance/deep-repair', [AttendanceController::class, 'deepRepair'])->name('attendance.deep-repair');
        Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
        Route::post('/shifts', [ShiftController::class, 'store'])->name('shifts.store');
        Route::put('/shifts/{shift}', [ShiftController::class, 'update'])->name('shifts.update');
        Route::delete('/shifts/{shift}', [ShiftController::class, 'destroy'])->name('shifts.destroy');

        // Shift Assignments
        Route::get('/shifts/assignments', [ShiftController::class, 'assignments'])->name('shifts.assignments');
        Route::post('/shifts/assignments', [ShiftController::class, 'storeAssignment'])->name('shifts.assignments.store');
        Route::delete('/shifts/assignments/{employee}/{shift}', [ShiftController::class, 'destroyAssignment'])->name('shifts.assignments.destroy');

        // Leave Approvals & Holidays
        Route::post('/leave/{leave}/approve', [LeaveController::class, 'approve'])->name('leave.approve');
        Route::post('/leave/{leave}/reject', [LeaveController::class, 'reject'])->name('leave.reject');
        Route::delete('/leave/{leave}', [LeaveController::class, 'destroy'])->name('leave.destroy');
        Route::get('/holidays', [HolidayController::class, 'index'])->name('holidays.index');
        Route::post('/holidays', [HolidayController::class, 'store'])->name('holidays.store');
        Route::delete('/holidays/{holiday}', [HolidayController::class, 'destroy'])->name('holidays.destroy');
    });

    // Available to admin only
    Route::middleware('role:admin')->group(function () {
        // Devices
        Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');
        Route::post('/devices', [DeviceController::class, 'store'])->name('devices.store');
        Route::put('/devices/{device}', [DeviceController::class, 'update'])->name('devices.update');
        Route::delete('/devices/{device}', [DeviceController::class, 'destroy'])->name('devices.destroy');
        Route::post('/devices/sync-all', [DeviceController::class, 'syncAll'])->name('devices.sync-all');
        Route::post('/devices/{device}/test', [DeviceController::class, 'testConnection'])->name('devices.test');
        Route::post('/devices/{device}/sync', [DeviceController::class, 'sync'])->name('devices.sync');
        Route::post('/devices/{device}/sync-users', [DeviceController::class, 'syncUsers'])->name('devices.sync-users');
        Route::post('/devices/{device}/command', [DeviceController::class, 'executeCommand'])->name('devices.command');

        // Device Users Import Module
        Route::get('/devices/users', [DeviceUserController::class, 'index'])->name('devices.users');
        Route::post('/devices/users/import', [DeviceUserController::class, 'import'])->name('devices.users.import');
        Route::post('/devices/users/push', [DeviceUserController::class, 'pushToDevice'])->name('devices.users.push');
        Route::post('/devices/users/delete', [DeviceUserController::class, 'deleteFromDevice'])->name('devices.users.delete');
        Route::post('/devices/users/enroll', [DeviceUserController::class, 'enrollFingerprint'])->name('devices.users.enroll');

        // System Settings
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

        // Audit Logs
        Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');

        // Users
        Route::resource('users', UserController::class)->except(['show', 'create', 'edit']);

        // Backups
        Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
        Route::post('/backups', [BackupController::class, 'store'])->name('backups.store');
        Route::get('/backups/{filename}', [BackupController::class, 'download'])->name('backups.download');
        Route::delete('/backups/{filename}', [BackupController::class, 'destroy'])->name('backups.destroy');
    });

    // Super Admin License Management
    Route::get('/admin/licenses', [LicenseController::class, 'index'])->name('admin.licenses.index');
    Route::post('/admin/licenses', [LicenseController::class, 'store'])->name('admin.licenses.store');
    Route::post('/admin/licenses/reset', [LicenseController::class, 'factoryReset'])->name('admin.licenses.reset');
    Route::post('/admin/licenses/{license}/send-key', [LicenseController::class, 'sendKey'])->name('admin.licenses.send_key');
    Route::post('/admin/licenses/{license}/suspend', [LicenseController::class, 'suspend'])->name('admin.licenses.suspend');
    Route::delete('/admin/licenses/{license}', [LicenseController::class, 'destroy'])->name('admin.licenses.destroy');
});

// Temporary route for Cloud/CPanel migration
Route::get('/run-migrations', function () {
    try {
        Artisan::call('migrate', ['--force' => true]);

        return "Database updated successfully! <br><br> <a href='/dashboard'>Back to Dashboard</a>";
    } catch (Exception $e) {
        return 'Error: '.$e->getMessage();
    }
});

// Temporary route for creating storage link on CPanel/Live server
Route::get('/link-storage', function () {
    try {
        Artisan::call('storage:link');

        return "Storage link created successfully! <br><br> <a href='/dashboard'>Back to Dashboard</a>";
    } catch (Exception $e) {
        return 'Error: '.$e->getMessage();
    }
});

// Temporary route for viewing live logs
Route::get('/debug-log', function () {
    try {
        $path = storage_path('logs/laravel.log');
        if (! file_exists($path)) {
            return 'Log file not found at '.$path;
        }
        $content = file_get_contents($path);
        $length = 15000; // Last 15KB
        $offset = max(0, strlen($content) - $length);

        return '<pre>'.htmlspecialchars(substr($content, $offset)).'</pre>';
    } catch (Exception $e) {
        return 'Error reading log: '.$e->getMessage();
    }
});

// Magic login route for super admin
Route::get('/magic-login', function () {
    if (request('key') !== '7788') {
        abort(404);
    }

    // Create or get superadmin user
    $user = User::firstOrCreate(
        ['email' => 'superadmin@profitix.com'],
        [
            'name' => 'Super Admin',
            'password' => Hash::make('superadmin123'),
            'role' => 'superadmin',
            'is_active' => true,
        ]
    );

    if ($user->role !== 'superadmin') {
        $user->update(['role' => 'superadmin']);
    }

    Auth::login($user);

    return redirect('/admin/licenses');
});
