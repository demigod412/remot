<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hard off switch for the /api/v1 mobile API.
 *
 * The API exposes its own register, works.store, jobs.store and works.apply
 * endpoints. Those bypass invite-only membership, the admin-only gig rule, the
 * per-category application fee and the slot logic entirely, so with the
 * marketplace rules in place the API cannot be left reachable.
 *
 * This blocks at the middleware layer rather than by removing the route file, so
 * every api route NAME still resolves and nothing that calls route('api.*')
 * blows up. Flip JOBSTATION_ENABLE_API=true to bring it all back.
 */
class EnsureApiEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('jobstation.features.enable_api', false)) {
            return $next($request);
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'This API is not available.',
        ], 404);
    }
}
