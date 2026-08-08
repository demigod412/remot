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
// Random-rate application approval. Every 3 hours, per the platform design: the
// rate each worker gets is drawn once per category per day and reused by every run,
// so the interval affects how promptly applications are decided, not the odds.
//
// withoutOverlapping because a slow run must never be joined by the next one —
// two concurrent runs would both see the same pending applications and could
// approve past a task's remaining slots.
// Auto-approval of submitted work whose review window has elapsed. Hourly rather
// than more often because the window is measured in days; a submission sitting an
// extra 40 minutes past 48 hours is not a problem worth eight times the queries.
Schedule::command('jobstation:process-review-deadlines')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('jobstation:approve-applications')
    ->everyThreeHours()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('license:verify --save')
    ->weekly()
    ->when(fn () => filled(config('jobstation.envato.token')) && is_app_installed())
    ->withoutOverlapping();
