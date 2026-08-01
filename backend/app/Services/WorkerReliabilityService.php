<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkSubmission;
use Carbon\Carbon;

/**
 * Worker accountability.
 *
 * Without this, someone can abandon task after task, lose the fee each time, and
 * keep applying forever while holding slots that real workers wanted.
 *
 * Strikes are COUNTED FROM work_submissions rather than cached in a column, so
 * they can never drift out of sync with the actual records. The only stored state
 * is users.strikes_cleared_at, the admin forgiveness marker.
 *
 * Abandonment is weighted heavier than rejection on purpose: a rejected worker at
 * least tried and gave admin something to review, while an abandoned task wasted
 * a slot for the full deadline window.
 */
class WorkerReliabilityService
{
    public function windowDays(): int
    {
        return (int) config('jobstation.accountability.window_days', 60);
    }

    public function maxStrikes(): int
    {
        return (int) config('jobstation.accountability.max_strikes', 6);
    }

    public function abandonWeight(): int
    {
        return (int) config('jobstation.accountability.abandon_weight', 3);
    }

    public function rejectWeight(): int
    {
        return (int) config('jobstation.accountability.reject_weight', 1);
    }

    /**
     * Zero disables the whole feature, which is how it ships until you decide the
     * thresholds are right for your workers.
     */
    public function enabled(): bool
    {
        return $this->maxStrikes() > 0;
    }

    // -------------------------------------------------------------------------
    // Counting
    // -------------------------------------------------------------------------

    protected function since(User $user): Carbon
    {
        $window = now()->subDays($this->windowDays());

        // An admin clearing strikes moves the floor forward, forgiving history
        // without editing or deleting any submission.
        if ($user->strikes_cleared_at && $user->strikes_cleared_at->greaterThan($window)) {
            return $user->strikes_cleared_at;
        }

        return $window;
    }

    /**
     * @return array{abandoned:int, rejected:int, completed:int, strikes:int, window_days:int, max_strikes:int, blocked:bool}
     */
    public function summary(User $user): array
    {
        $since = $this->since($user);

        $counts = WorkSubmission::query()
            ->where('worker_id', $user->id)
            ->where('worker_type', 2)
            ->where('updated_at', '>', $since)
            ->selectRaw('delivery_status, COUNT(*) as total')
            ->groupBy('delivery_status')
            ->pluck('total', 'delivery_status');

        $abandoned = (int) ($counts[WorkSubmission::DEL_EXPIRED] ?? 0);
        $rejected  = (int) ($counts[WorkSubmission::DEL_REJECTED] ?? 0);
        $completed = (int) ($counts[WorkSubmission::DEL_APPROVED] ?? 0);

        $strikes = ($abandoned * $this->abandonWeight())
                 + ($rejected * $this->rejectWeight());

        return [
            'abandoned'   => $abandoned,
            'rejected'    => $rejected,
            'completed'   => $completed,
            'strikes'     => $strikes,
            'window_days' => $this->windowDays(),
            'max_strikes' => $this->maxStrikes(),
            'blocked'     => $this->enabled() && $strikes >= $this->maxStrikes(),
        ];
    }

    public function strikes(User $user): int
    {
        return $this->summary($user)['strikes'];
    }

    public function isBlocked(User $user): bool
    {
        return $this->summary($user)['blocked'];
    }

    /**
     * Message shown to a blocked worker. Deliberately explains what happened and
     * when it lifts, rather than a bare refusal.
     */
    public function blockReason(User $user): string
    {
        $s = $this->summary($user);

        $parts = [];
        if ($s['abandoned'] > 0) {
            $parts[] = $s['abandoned'] . ' task(s) not submitted before the deadline';
        }
        if ($s['rejected'] > 0) {
            $parts[] = $s['rejected'] . ' rejected submission(s)';
        }

        return 'Task applications are paused on your account because of '
             . (implode(' and ', $parts) ?: 'recent task activity')
             . ' in the last ' . $s['window_days'] . ' days. '
             . 'This lifts automatically as those records age out of the window. '
             . 'Contact support if you believe this is a mistake.';
    }

    // -------------------------------------------------------------------------
    // Admin override
    // -------------------------------------------------------------------------

    /**
     * Forgive a worker's history. Audited, since it is a discretionary decision
     * that changes what someone is allowed to do.
     */
    public function clearStrikes(User $user, ?int $adminId = null): void
    {
        $before = $this->summary($user);

        $user->forceFill(['strikes_cleared_at' => now()])->save();

        ActivityLogger::log('user.strikes_cleared', $user, [
            'admin_id'         => $adminId,
            'username'         => $user->username,
            'strikes_forgiven' => $before['strikes'],
            'abandoned'        => $before['abandoned'],
            'rejected'         => $before['rejected'],
        ]);
    }
}
