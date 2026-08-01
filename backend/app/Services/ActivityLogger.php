<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Record an irreversible admin action.
     *
     * Call this at the point of the state change, inside the same transaction as
     * the change itself, so a rolled back action never leaves an orphan log row.
     *
     * @param  string      $action   Dotted verb, e.g. 'membership.approve'
     * @param  Model|null  $subject  The record acted on
     * @param  array       $meta     Amounts, reasons, before/after values
     */
    public static function log(string $action, ?Model $subject = null, array $meta = []): ?AdminActivityLog
    {
        try {
            $adminId = $meta['admin_id'] ?? Auth::guard('admin')->id();
            unset($meta['admin_id']);

            if (! $adminId) {
                // Console commands and system jobs have no logged-in admin. Those
                // are real events we still want, so record them under admin 0 and
                // mark the actor rather than dropping the row.
                $adminId = 0;
                $meta['actor'] = $meta['actor'] ?? (app()->runningInConsole() ? 'system' : 'unauthenticated');
            }

            return AdminActivityLog::create([
                'admin_id'     => $adminId,
                'action'       => $action,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id'   => $subject?->getKey(),
                'meta'         => empty($meta) ? null : $meta,
                'ip_address'   => static::ip(),
            ]);
        } catch (\Throwable $e) {
            // Audit logging must never be the thing that breaks an admin action.
            // Fall back to the app log so the event is not lost entirely.
            Log::error('ActivityLogger failed to write audit row', [
                'action'  => $action,
                'subject' => $subject ? $subject::class . '#' . $subject->getKey() : null,
                'meta'    => $meta,
                'error'   => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Convenience wrapper for actions that moved coins. Keeps the meta shape
     * consistent so the audit screen can render amounts without special cases.
     */
    public static function logMoney(
        string  $action,
        ?Model  $subject,
        float   $coins,
        ?int    $userId = null,
        array   $meta = []
    ): ?AdminActivityLog {
        return static::log($action, $subject, array_merge($meta, [
            'coins'   => round($coins, 8),
            'user_id' => $userId,
        ]));
    }

    protected static function ip(): ?string
    {
        try {
            return Request::ip();
        } catch (\Throwable) {
            return null;
        }
    }
}
