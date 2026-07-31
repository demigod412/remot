<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowRegistration
{
    public function handle(Request $request, Closure $next): Response
    {
        $setting = AppSetting::get();

        if (!$setting->registration) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Registration is currently disabled.'], 403);
            }
            return redirect()->route('user.login')
                ->with('error', 'Registration is currently disabled.');
        }

        return $next($request);
    }
}
