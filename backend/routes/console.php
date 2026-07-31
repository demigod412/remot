<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Job Station scheduled tasks ────────────────────────────────────────────────────

// Expire works and auto-approve submissions every 15 minutes
Schedule::command('jobstation:process-timers')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/jobstation-timers.log'));

// Re-verify the CodeCanyon purchase code weekly (refreshes degraded/offline
// installs once Envato is reachable; no-op when no token is configured).
Schedule::command('license:verify --save')
    ->weekly()
    ->when(fn () => filled(config('jobstation.envato.token')) && is_app_installed())
    ->withoutOverlapping();
