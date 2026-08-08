<?php

namespace App\Services;

use App\Models\Cashout;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The rules governing when a worker may request a withdrawal.
 *
 * Kept in one class rather than scattered through WalletController because these
 * rules are the sort that get partially enforced: a check on the form but not on
 * submit, or on submit but not on an admin's behalf. One place to read means one
 * place to be wrong.
 *
 * ORDER MATTERS. The checks run in the order the platform owner specified, so the
 * message a worker sees is the most relevant reason rather than whichever check
 * happened to be written first. Someone banned mid-month should be told they are
 * banned, not that it is the 3rd.
 */
class WithdrawalPolicy
{
    public const REASON_BANNED       = 'account_banned';
    public const REASON_OUT_OF_WINDOW = 'outside_window';
    public const REASON_ALREADY_THIS_MONTH = 'already_withdrew_this_month';
    public const REASON_BELOW_MINIMUM = 'below_minimum';

    /** cashouts.status — 0 pending, 1 approved, 2 rejected, 3 disbursing, 4 failed. */
    public const STATUS_CANCELLED = 5;

    /**
     * @return array{allowed: bool, reason: string|null, message: string|null}
     */
    public function check(User $user, float $amount): array
    {
        $gs = gs();

        // 1. Banned or suspended.
        if ((int) $user->status !== 1) {
            return $this->deny(
                self::REASON_BANNED,
                'Your account is not active, so withdrawals are unavailable. Contact support.'
            );
        }

        // 2. Inside the day-of-month window.
        if ($gs->withdrawal_window_enabled) {
            $start = (int) ($gs->withdrawal_window_start ?? 15);
            $end   = (int) ($gs->withdrawal_window_end ?? 28);
            $today = (int) now()->day;

            if ($today < $start || $today > $end) {
                return $this->deny(
                    self::REASON_OUT_OF_WINDOW,
                    sprintf(
                        'Withdrawals can only be requested between the %s and the %s of each month. Today is the %s.',
                        $this->ordinal($start),
                        $this->ordinal($end),
                        $this->ordinal($today)
                    )
                );
            }
        }

        // 3. One approved withdrawal per calendar month.
        //
        // Counts APPROVED only, deliberately. Counting pending would mean a request
        // an admin has not looked at yet blocks the month, and a rejected one would
        // punish someone for an admin's decision. Approved is the point at which the
        // worker actually received a payout.
        if ($gs->one_withdrawal_per_month && $this->approvedThisMonth($user) > 0) {
            return $this->deny(
                self::REASON_ALREADY_THIS_MONTH,
                'You have already had a withdrawal approved this month. The next one can be requested from the '
                . $this->ordinal((int) ($gs->withdrawal_window_start ?? 15)) . ' of next month.'
            );
        }

        // 4. Minimum amount. Reuses app_settings.min_cashout, which is already the
        //    admin-editable minimum, rather than introducing a second setting that
        //    must be kept in step with it.
        $minimum = (float) ($gs->min_cashout ?? 50);

        if ($amount < $minimum) {
            return $this->deny(
                self::REASON_BELOW_MINIMUM,
                'The minimum withdrawal is ' . formatUsd($minimum) . '.'
            );
        }

        // 5. Balance is checked by the caller, which already holds a row lock on the
        //    user. Duplicating it here would read an unlocked value and could pass
        //    something the locked check then refuses.

        return ['allowed' => true, 'reason' => null, 'message' => null];
    }

    public function approvedThisMonth(User $user): int
    {
        return Cashout::where('user_id', $user->id)
            ->where('status', 1)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }

    /**
     * Cancel every pending request for a user, synchronously.
     *
     * Called when an account is banned or suspended. Synchronous on purpose: a queued
     * job would leave a window in which a banned user's request could still be
     * approved and paid, and the whole point of the rule is that it cannot be.
     *
     * The balance is NOT returned. A pending cashout has already been debited, and
     * EarningsService::reverseWithdrawal() exists for undoing that — but calling it
     * here would pay the money back into an account that has just been banned,
     * usually for the sort of reason that makes that unwise. Left as an explicit
     * admin action instead, so a human decides.
     *
     * @return int how many were cancelled
     */
    public function cancelPendingFor(User $user, string $reason = self::REASON_BANNED): int
    {
        $pending = Cashout::where('user_id', $user->id)->where('status', 0)->get();

        if ($pending->isEmpty()) {
            return 0;
        }

        DB::transaction(function () use ($pending, $reason) {
            foreach ($pending as $cashout) {
                $cashout->update([
                    'status'           => self::STATUS_CANCELLED,
                    'cancelled_at'     => now(),
                    'cancelled_reason' => $reason,
                    'admin_note'       => 'Cancelled automatically: account is no longer active.',
                ]);
            }
        });

        Log::info('Pending withdrawals cancelled', [
            'user_id' => $user->id,
            'count'   => $pending->count(),
            'reason'  => $reason,
        ]);

        ActivityLogger::log('withdrawal.cancelled_on_ban', $user, [
            'username'  => $user->username,
            'cancelled' => $pending->count(),
            'total_usd' => (float) $pending->sum('net_coins_deducted'),
            'reason'    => $reason,
        ]);

        return $pending->count();
    }

    /**
     * Whether requests can be made right now, for showing the worker where they
     * stand before they fill anything in.
     *
     * @return array{open: bool, opens_on: string|null, closes_on: string|null}
     */
    public function windowStatus(): array
    {
        $gs = gs();

        if (! $gs->withdrawal_window_enabled) {
            return ['open' => true, 'opens_on' => null, 'closes_on' => null];
        }

        $start = (int) ($gs->withdrawal_window_start ?? 15);
        $end   = (int) ($gs->withdrawal_window_end ?? 28);
        $today = (int) now()->day;

        return [
            'open'      => $today >= $start && $today <= $end,
            'opens_on'  => $this->ordinal($start),
            'closes_on' => $this->ordinal($end),
        ];
    }

    private function deny(string $reason, string $message): array
    {
        return ['allowed' => false, 'reason' => $reason, 'message' => $message];
    }

    private function ordinal(int $n): string
    {
        $suffix = 'th';

        if (! in_array($n % 100, [11, 12, 13], true)) {
            $suffix = match ($n % 10) {
                1 => 'st',
                2 => 'nd',
                3 => 'rd',
                default => 'th',
            };
        }

        return $n . $suffix;
    }
}
