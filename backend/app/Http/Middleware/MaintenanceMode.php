<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MaintenanceMode
{
    public function handle(Request $request, Closure $next)
    {
        // Skip before installation (no DB yet) and on the installer itself.
        if (! is_app_installed() || $request->is('install', 'install/*')) {
            return $next($request);
        }

        // Always let admin routes through
        if ($request->is('admin') || $request->is('admin/*')) {
            return $next($request);
        }

        if (AppSetting::get()->maintenance_mode) {
            // Logged-in admins can browse the full site during maintenance
            if (Auth::guard('admin')->check()) {
                return $next($request);
            }
            return response()->view('maintenance', [], 503);
        }

        return $next($request);
    }
}
