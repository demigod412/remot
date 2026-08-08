<?php

namespace Tests\Feature;

use App\Models\Cashout;
use App\Models\User;
use App\Services\WithdrawalPolicy;
use Illuminate\Support\Carbon;

/**
 * Withdrawal eligibility rules.
 *
 * Every rule here refuses somebody money they can see in their balance, so each one
 * is tested from both sides: that it blocks when it should, and that it does not
 * block when it should not. A rule that over-refuses is worse than none, because the
 * worker has no way to tell it from a bug.
 */
class WithdrawalPolicyTest extends FeatureTestCase
{
    private WithdrawalPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = app(WithdrawalPolicy::class);

        // Rules off by default, so each test enables only what it is about.
        $this->settings([
            'withdrawal_window_enabled' => false,
            'one_withdrawal_per_month'  => false,
            'min_cashout'               => 50,
        ]);
    }

    /**
     * AppSetting::get() memoises in a static, so writing the row is not enough —
     * forgetCache() is the only thing that makes gs() see the change. Without it
     * every test here would run against whatever the first one loaded, and they
     * would pass or fail for reasons unrelated to what they assert.
     */
    private function settings(array $values): void
    {
        $s = \App\Models\AppSetting::first() ?? new \App\Models\AppSetting();
        $s->forceFill($values)->save();
        \App\Models\AppSetting::forgetCache();
    }

    private function earner(float $usd = 500, int $status = 1): User
    {
        $u = $this->makeUser(['status' => $status], 0);
        $u->forceFill(['usd_balance' => $usd])->save();

        return $u->fresh();
    }

    // ───────────────────────── banned ─────────────────────────

    public function test_a_banned_user_cannot_withdraw(): void
    {
        $result = $this->policy->check($this->earner(status: 0), 100);

        $this->assertFalse($result['allowed']);
        $this->assertSame(WithdrawalPolicy::REASON_BANNED, $result['reason']);
    }

    public function test_an_active_user_can(): void
    {
        $this->assertTrue($this->policy->check($this->earner(), 100)['allowed']);
    }

    /**
     * Ban wins over every other reason, so someone banned on the 3rd is told they
     * are banned rather than being told to come back on the 15th.
     */
    public function test_ban_is_reported_ahead_of_the_window(): void
    {
        $this->settings(['withdrawal_window_enabled' => true, 'withdrawal_window_start' => 15, 'withdrawal_window_end' => 28]);
        Carbon::setTestNow(Carbon::create(2026, 3, 3));

        $result = $this->policy->check($this->earner(status: 0), 100);

        $this->assertSame(WithdrawalPolicy::REASON_BANNED, $result['reason']);
        Carbon::setTestNow();
    }

    // ───────────────────────── window ─────────────────────────

    public function test_requests_are_refused_outside_the_window(): void
    {
        $this->settings(['withdrawal_window_enabled' => true, 'withdrawal_window_start' => 15, 'withdrawal_window_end' => 28]);

        foreach ([1, 14, 29, 31] as $day) {
            Carbon::setTestNow(Carbon::create(2026, 3, $day));
            $result = $this->policy->check($this->earner(), 100);
            $this->assertFalse($result['allowed'], "Day {$day} should be closed.");
            $this->assertSame(WithdrawalPolicy::REASON_OUT_OF_WINDOW, $result['reason']);
        }

        Carbon::setTestNow();
    }

    public function test_the_window_is_inclusive_at_both_ends(): void
    {
        $this->settings(['withdrawal_window_enabled' => true, 'withdrawal_window_start' => 15, 'withdrawal_window_end' => 28]);

        foreach ([15, 20, 28] as $day) {
            Carbon::setTestNow(Carbon::create(2026, 3, $day));
            $this->assertTrue($this->policy->check($this->earner(), 100)['allowed'], "Day {$day} should be open.");
        }

        Carbon::setTestNow();
    }

    public function test_the_window_does_nothing_while_disabled(): void
    {
        $this->settings(['withdrawal_window_enabled' => false]);
        Carbon::setTestNow(Carbon::create(2026, 3, 1));

        $this->assertTrue($this->policy->check($this->earner(), 100)['allowed']);
        Carbon::setTestNow();
    }

    // ───────────────────── one per month ─────────────────────

    public function test_a_second_withdrawal_in_the_same_month_is_refused(): void
    {
        $this->settings(['one_withdrawal_per_month' => true]);
        $user = $this->earner();

        Cashout::create([
            'user_id' => $user->id, 'payout_method_id' => 1, 'coin_amount' => 100,
            'fee' => 0, 'net_coins_deducted' => 100, 'payout_amount' => 100,
            'payout_currency' => 'USDT', 'coin_to_currency_rate' => 1,
            'reference' => 'PREV-1', 'status' => 1,
        ]);

        $result = $this->policy->check($user, 100);

        $this->assertFalse($result['allowed']);
        $this->assertSame(WithdrawalPolicy::REASON_ALREADY_THIS_MONTH, $result['reason']);
    }

    /**
     * Pending means an admin has not looked at it yet. Blocking on that would let an
     * unreviewed request cost someone their whole month.
     */
    public function test_a_pending_request_does_not_block(): void
    {
        $this->settings(['one_withdrawal_per_month' => true]);
        $user = $this->earner();

        Cashout::create([
            'user_id' => $user->id, 'payout_method_id' => 1, 'coin_amount' => 100,
            'fee' => 0, 'net_coins_deducted' => 100, 'payout_amount' => 100,
            'payout_currency' => 'USDT', 'coin_to_currency_rate' => 1,
            'reference' => 'PENDING-1', 'status' => 0,
        ]);

        $this->assertTrue($this->policy->check($user, 100)['allowed']);
    }

    public function test_a_rejected_request_does_not_block(): void
    {
        $this->settings(['one_withdrawal_per_month' => true]);
        $user = $this->earner();

        Cashout::create([
            'user_id' => $user->id, 'payout_method_id' => 1, 'coin_amount' => 100,
            'fee' => 0, 'net_coins_deducted' => 100, 'payout_amount' => 100,
            'payout_currency' => 'USDT', 'coin_to_currency_rate' => 1,
            'reference' => 'REJ-1', 'status' => 2,
        ]);

        $this->assertTrue($this->policy->check($user, 100)['allowed'],
            'An admin refusing a request must not cost the worker their month.');
    }

    public function test_last_months_approval_does_not_block(): void
    {
        $this->settings(['one_withdrawal_per_month' => true]);
        $user = $this->earner();

        $old = Cashout::create([
            'user_id' => $user->id, 'payout_method_id' => 1, 'coin_amount' => 100,
            'fee' => 0, 'net_coins_deducted' => 100, 'payout_amount' => 100,
            'payout_currency' => 'USDT', 'coin_to_currency_rate' => 1,
            'reference' => 'OLD-1', 'status' => 1,
        ]);
        $old->forceFill(['created_at' => now()->subMonth()])->save();

        $this->assertTrue($this->policy->check($user, 100)['allowed']);
    }

    // ───────────────────────── minimum ─────────────────────────

    public function test_below_the_minimum_is_refused(): void
    {
        $this->settings(['min_cashout' => 50]);
        $result = $this->policy->check($this->earner(), 49.99);

        $this->assertFalse($result['allowed']);
        $this->assertSame(WithdrawalPolicy::REASON_BELOW_MINIMUM, $result['reason']);
    }

    public function test_exactly_the_minimum_is_allowed(): void
    {
        $this->settings(['min_cashout' => 50]);
        $this->assertTrue($this->policy->check($this->earner(), 50.0)['allowed']);
    }

    // ─────────────── cancellation on ban ───────────────

    public function test_banning_cancels_pending_requests_only(): void
    {
        $user = $this->earner();

        foreach ([['P1', 0], ['P2', 0], ['A1', 1], ['R1', 2]] as [$ref, $status]) {
            Cashout::create([
                'user_id' => $user->id, 'payout_method_id' => 1, 'coin_amount' => 100,
                'fee' => 0, 'net_coins_deducted' => 100, 'payout_amount' => 100,
                'payout_currency' => 'USDT', 'coin_to_currency_rate' => 1,
                'reference' => $ref, 'status' => $status,
            ]);
        }

        $cancelled = $this->policy->cancelPendingFor($user);

        $this->assertSame(2, $cancelled);
        $this->assertSame(2, Cashout::where('status', WithdrawalPolicy::STATUS_CANCELLED)->count());
        $this->assertSame(1, Cashout::where('status', 1)->count(), 'An approved payout is not undone.');
        $this->assertSame(1, Cashout::where('status', 2)->count());

        $this->assertSame(
            WithdrawalPolicy::REASON_BANNED,
            Cashout::where('reference', 'P1')->value('cancelled_reason')
        );
    }

    /**
     * Cancelling is not refunding. The balance left when the request was made, and
     * returning it automatically to an account just banned is a decision for a human.
     */
    public function test_cancelling_does_not_return_the_balance(): void
    {
        $user   = $this->earner(usd: 400);
        $before = (float) $user->fresh()->usd_balance;

        Cashout::create([
            'user_id' => $user->id, 'payout_method_id' => 1, 'coin_amount' => 100,
            'fee' => 0, 'net_coins_deducted' => 100, 'payout_amount' => 100,
            'payout_currency' => 'USDT', 'coin_to_currency_rate' => 1,
            'reference' => 'NOREFUND-1', 'status' => 0,
        ]);

        $this->policy->cancelPendingFor($user);

        $this->assertSame($before, (float) $user->fresh()->usd_balance);
    }

    public function test_cancelling_with_nothing_pending_is_harmless(): void
    {
        $this->assertSame(0, $this->policy->cancelPendingFor($this->earner()));
    }
}
