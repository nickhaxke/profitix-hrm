<?php

namespace App\Http\Middleware;

use App\Models\License;
use Closure;
use Illuminate\Http\Request;

class CheckLicense
{
    public function handle(Request $request, Closure $next)
    {
        // If user is superadmin, let them through regardless of license
        if (auth()->check() && auth()->user()->role === 'superadmin') {
            return $next($request);
        }

        // Find if there is ANY active valid license in the system
        $hasActiveLicense = License::all()->contains(function ($license) {
            return $license->isActive();
        });

        if (! $hasActiveLicense) {
            // Allow access to the license activation page
            if ($request->is('license*')) {
                return $next($request);
            }

            return redirect()->route('license.activate');
        }

        return $next($request);
    }
}
