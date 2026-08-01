<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Accounts created by admin approval start on a temporary password. Until it is
 * replaced they can reach the change-password screen and logout, nothing else.
 *
 * Registered on the authenticated user route group, so a worker cannot skip it by
 * navigating straight to a deep link.
 */
class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        // Routes that must stay reachable, otherwise the user is locked in a loop.
        $allowed = [
            'user.password.change',
            'user.password.change.submit',
            'user.logout',
        ];

        if (in_array($request->route()?->getName(), $allowed, true)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You must change your temporary password before continuing.',
            ], 403);
        }

        return redirect()->route('user.password.change')
            ->with('info', 'Please set a new password before continuing.');
    }
}
