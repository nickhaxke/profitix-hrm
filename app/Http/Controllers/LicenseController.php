<?php

namespace App\Http\Controllers;

use App\Mail\LicenseKeyMail;
use App\Models\AttendanceLog;
use App\Models\AttendanceSummary;
use App\Models\Device;
use App\Models\Employee;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class LicenseController extends Controller
{
    public function index(Request $request)
    {
        if (! auth()->user() || auth()->user()->role !== 'superadmin') {
            abort(403, 'Unauthorized. Super Admin Access Required.');
        }

        $licenses = License::orderBy('created_at', 'desc')->get();

        return view('admin.licenses', compact('licenses'));
    }

    public function store(Request $request)
    {
        if (! auth()->user() || auth()->user()->role !== 'superadmin') {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'organization_name' => 'required|string|max:255',
            'client_email' => 'nullable|email',
            'client_phone' => 'nullable|string|max:20',
            'expires_at' => 'required|date',
        ]);

        $key = strtoupper(Str::random(4).'-'.Str::random(4).'-'.Str::random(4));

        License::create([
            'license_key' => $key,
            'client_name' => $request->organization_name,
            'client_email' => $request->client_email,
            'client_phone' => $request->client_phone,
            'status' => 'pending',
            'expires_at' => $request->expires_at,
            'max_employees' => $request->max_devices ?? 100,
        ]);

        return redirect()->route('admin.licenses.index')->with('success', "New License Generated: $key");
    }

    public function sendKey(Request $request, License $license)
    {
        if (! auth()->user() || auth()->user()->role !== 'superadmin') {
            abort(403, 'Unauthorized.');
        }

        $type = $request->query('type', 'email');

        if ($type === 'email') {
            if (! $license->client_email) {
                return redirect()->back()->withErrors(['email' => 'Client email not provided for this license.']);
            }
            try {
                Mail::to($license->client_email)->send(new LicenseKeyMail($license));

                return redirect()->back()->with('success', 'License key sent to '.$license->client_email);
            } catch (\Exception $e) {
                return redirect()->back()->withErrors(['email' => 'Failed to send email: '.$e->getMessage()]);
            }
        }

        return redirect()->back()->with('success', 'Operation completed.');
    }

    public function activate()
    {
        $license = License::first();

        return view('license.activate', compact('license'));
    }

    public function processActivation(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string',
        ]);

        $license = License::where('license_key', $request->license_key)->first();

        if (! $license) {
            return redirect()->back()->withErrors(['license_key' => 'Invalid License Key.']);
        }

        if ($license->expires_at && $license->expires_at->isPast()) {
            return redirect()->back()->withErrors(['license_key' => 'This License Key has expired.']);
        }

        $license->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);

        return redirect()->route('dashboard')->with('success', 'System Activated Successfully!');
    }

    public function suspend(License $license)
    {
        if (! auth()->user() || auth()->user()->role !== 'superadmin') {
            abort(403);
        }

        $license->update([
            'status' => $license->status === 'suspended' ? 'active' : 'suspended',
        ]);

        $msg = $license->status === 'suspended' ? 'License suspended successfully.' : 'License activated successfully.';

        return redirect()->back()->with('success', $msg);
    }

    public function destroy(License $license)
    {
        if (! auth()->user() || auth()->user()->role !== 'superadmin') {
            abort(403);
        }

        $license->delete();

        return redirect()->back()->with('success', 'License deleted successfully.');
    }

    public function factoryReset(Request $request)
    {
        if (! auth()->user() || auth()->user()->role !== 'superadmin') {
            abort(403, 'Unauthorized.');
        }

        // Truncate all main tables
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        AttendanceLog::truncate();
        AttendanceSummary::truncate();
        Employee::truncate();
        Device::truncate();
        License::truncate();

        // Clear any cache
        Cache::flush();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        return redirect()->route('admin.licenses.index')->with('success', 'SYSTEM FACTORY RESET COMPLETED! All data wiped.');
    }
}
