<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force visitors to the installation wizard until Job Station is installed.
 * Runs first in the web/api stacks so DB-dependent middleware never execute pre-install.
 */
class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (is_app_installed()) {
            return $next($request);
        }

        // Allow the installer, the health endpoint, and static assets through.
        if ($request->is('install', 'install/*', 'up', 'build/*', 'favicon.ico', 'robots.txt')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Application not installed.'], 503);
        }

        return redirect('/install');
    }
}
