<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // The admin path is configurable and NOT 'admin'.
            //
            // Route names stay 'admin.*', so every route('admin.…') call keeps working
            // and the prefix can change again later by editing .env alone. Only literal
            // '/admin/...' strings need hunting, and there were two — the subcategory
            // fetch in the work create and edit forms.
            //
            // This is obscurity, not security: it stops opportunistic scanners hitting
            // a known login path, and stops nothing that can read a URL over your
            // shoulder. The throttle on the login route is what limits guessing.
            Route::middleware('web')
                ->prefix(config('jobstation.admin_path', 'admin'))
                ->name('admin.')
                ->group(base_path('routes/admin.php'));

            Route::middleware('web')
                ->prefix('dashboard')
                ->name('user.')
                ->group(base_path('routes/user.php'));

            Route::middleware('api')
                ->prefix('ipn')
                ->name('ipn.')
                ->group(base_path('routes/ipn.php'));

            // API served under /api/v1/
            // Gated by jobstation.features.enable_api (default false). The API has
            // its own register / works.store / jobs.store / works.apply endpoints
            // which bypass the marketplace rules, so it is off by default.
            Route::middleware(['api', \App\Http\Middleware\EnsureApiEnabled::class])
                ->prefix('api/v1')
                ->group(base_path('routes/api.php'));

            // CodeCanyon installation wizard (/install)
            Route::middleware(['web', \App\Http\Middleware\PreventReinstall::class])
                ->prefix('install')
                ->name('install.')
                ->group(base_path('routes/install.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Cloudflare sits in front of this app, so every request arrives from a
        // Cloudflare edge IP. Without trusting it, $request->ip() is that edge address
        // rather than the visitor's, which quietly breaks anything IP-based:
        //
        //   - throttle:6,1 on admin login would rate limit every visitor behind the
        //     same edge together, so one person's failed logins lock out strangers;
        //   - audit log entries all record the same handful of addresses;
        //   - HTTPS detection depends on X-Forwarded-Proto, and without it Laravel can
        //     generate http:// URLs behind an https:// site.
        //
        // Trusting '*' is correct only because Cloudflare is the ONLY thing that can
        // reach the origin. If the origin is ever exposed directly, replace this with
        // Cloudflare's published IP ranges, or a spoofed header becomes a spoofed IP.
        $middleware->trustProxies(
            at: '*',
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB
        );

        // CSRF exclusions for payment gateway callbacks
        $middleware->validateCsrfTokens(except: [
            'payment/*',
        ]);

        // Force the installer until the app is installed (runs before DB-dependent middleware)
        $middleware->prependToGroup('web', \App\Http\Middleware\EnsureInstalled::class);
        $middleware->prependToGroup('api', \App\Http\Middleware\EnsureInstalled::class);

        // Baseline security headers on every web + API response.
        $middleware->appendToGroup('web', \App\Http\Middleware\SecurityHeaders::class);
        $middleware->appendToGroup('api', \App\Http\Middleware\SecurityHeaders::class);

        // Apply locale to all web routes
        $middleware->appendToGroup('web', \App\Http\Middleware\LocaleMiddleware::class);

        // Maintenance mode check (bypass admin routes)
        $middleware->appendToGroup('web', \App\Http\Middleware\MaintenanceMode::class);

        // Redirect unauthenticated users to the user login route (not the default 'login')
        $middleware->redirectGuestsTo(fn () => route('user.login'));

        $middleware->alias([
            'admin'          => \App\Http\Middleware\AdminAuthenticate::class,
            'guest.admin'    => \App\Http\Middleware\RedirectIfAdmin::class,
            'user.status'    => \App\Http\Middleware\CheckUserStatus::class,
            'onboarding'     => \App\Http\Middleware\OnboardingComplete::class,
            'registration'   => \App\Http\Middleware\AllowRegistration::class,
            'locale'         => \App\Http\Middleware\LocaleMiddleware::class,
            'demo'           => \App\Http\Middleware\DemoMode::class,
            'kyc'            => \App\Http\Middleware\RequireKyc::class,
            'api.enabled'    => \App\Http\Middleware\EnsureApiEnabled::class,
            'feature'        => \App\Http\Middleware\FeatureEnabled::class,
            'force.password' => \App\Http\Middleware\ForcePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
