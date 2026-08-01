<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side enforcement for the jobstation.features flags.
 *
 * Hiding a nav link is not a control. Anything gated by a feature flag gets this
 * middleware so a hand-crafted POST is refused too.
 *
 * Usage: ->middleware('feature:enable_user_gigs')
 */
class FeatureEnabled
{
    public function handle(Request $request, Closure $next, string $flag): Response
    {
        if (config("jobstation.features.{$flag}", false)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'This feature is not available.',
            ], 403);
        }

        abort(403, 'This feature is not available on this site.');
    }
}
