<?php

namespace App\Http\Middleware;

use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip locale resolution before installation (no DB yet) and on the installer itself.
        if (! is_app_installed() || $request->is('install', 'install/*')) {
            return $next($request);
        }

        $locale = Session::get('locale');

        if (!$locale && auth()->check()) {
            $locale = auth()->user()->locale;
        }

        if (!$locale) {
            $default = Language::getDefault();
            $locale  = $default?->code ?? 'en';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
