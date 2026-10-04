<?php

namespace App\Http\Controllers;

use App\Models\License;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::orderBy('group')->orderBy('key')->get()->groupBy('group');
        // Fetch the currently valid and active license
        $license = License::where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest('activated_at')
            ->first();

        return view('settings.index', compact('settings', 'license'));
    }

    public function update(Request $request)
    {
        $data = $request->except('_token', '_method', 'company_logo');

        foreach ($data as $key => $value) {
            Setting::setValue(str_replace('__', '.', $key), $value);
        }

        if ($request->hasFile('company_logo')) {
            $path = $request->file('company_logo')->store('logos', 'public');
            Setting::setValue('company_logo', $path, 'general');
        }

        return redirect()->route('settings.index')->with('success', 'Settings saved.');
    }
}
