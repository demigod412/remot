<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks access to the installer once Job Station is already installed,
 * except for the final "finished" confirmation screen.
 */
class PreventReinstall
{
    public function handle(Request $request, Closure $next): Response
    {
        if (is_app_installed() && ! $request->is('install/finished')) {
            return redirect('/');
        }

        return $next($request);
    }
}
