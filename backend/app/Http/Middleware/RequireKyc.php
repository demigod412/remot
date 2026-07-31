<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforce KYC verification when app_settings.kyc_required = 1.
 * Attach to routes that require verified identity (post work, cashout).
 */
class RequireKyc
{
    public function handle(Request $request, Closure $next): Response
    {
        $gs = gs();

        if (!$gs->kyc_required) {
            return $next($request);
        }

        $user = $request->user('web') ?? auth('web')->user();

        if (!$user) {
            return $next($request);
        }

        // kyc_status: 0=unverified, 1=verified, 2=pending
        if ($user->kyc_status !== 1) {
            $message = $user->kyc_status === 2
                ? 'Your KYC verification is pending. You\'ll be able to perform this action once approved.'
                : 'KYC verification is required for this action. Please submit your documents first.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'kyc_required' => true], 403);
            }

            return redirect()->route('user.profile.kyc')->with('warning', $message);
        }

        return $next($request);
    }
}
