<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.demo_mode', false) && $request->isMethod('POST')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Action disabled in demo mode.'], 403);
            }
            return back()->with('error', 'This action is disabled in demo mode.');
        }

        return $next($request);
    }
}
