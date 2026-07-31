<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class OnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if ($user && !$user->profile_complete) {
            if (!$request->routeIs('user.onboarding*')) {
                return redirect()->route('user.onboarding');
            }
        }

        return $next($request);
    }
}
