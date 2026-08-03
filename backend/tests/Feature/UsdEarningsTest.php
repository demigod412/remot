<?php

namespace Tests\Feature;

use App\Models\LedgerEntry;
use App\Models\User;
use App\Services\CoinService;
use App\Services\EarningsService;

/**
 * USD earnings balance, separate from the JC coin balance.
 *
 * The properties worth protecting are the separation itself and the one-way
 * flow, so most of these assert that a movement in one currency leaves the
 * other completely untouched.
 */
class UsdEarningsTest extends FeatureTestCase
{
    private function makeWorker(float $coins = 0, float $usd = 0): User
    {
        $user = $this->makeUser([], $coins);
        $user->forceFill(['usd_balance' => $usd])->save();

        return $user->fresh();
    }

    public function test_credit_increases_usd_and_records_a_usd_ledger_row(): void
    {
        $user = $this->makeWorker(coins: 100, usd: 0);

        EarningsService::credit($user, 25.50, 'REF-EARN', 'work_earn', 'Earned: test task');

        $this->assertSame(25.50, (float) $user->fresh()->usd_balance);
        $this->assertDatabaseHas('ledger_entries', [
            'user_id'    => $user->id,
            'reference'  => 'REF-EARN',
            'entry_type' => '+',
            'category'   => 'work_earn',
            'currency'   => 'usd',
        ]);
    }

    /**
     * The whole point of the model. Earning dollars must not touch coins.
     */
    public function test_usd_credit_does_not_change_the_coin_balance(): void
    {
        $user = $this->makeWorker(coins: 100, usd: 0);

        EarningsService::credit($user, 40, 'REF-SEP-1', 'work_earn');

        $fresh = $user->fresh();
        $this->assertSame(100.0, (float) $fresh->coin_balance, 'Coins must be untouched by a USD credit.');
        $this->assertSame(40.0, (float) $fresh->usd_balance);
    }

    public function test_coin_movement_does_not_change_the_usd_balance(): void
    {
        $user = $this->makeWorker(coins: 100, usd: 60);

        CoinService::deduct($user, 30, 'REF-SEP-2', 'task_apply');

        $fresh = $user->fresh();
        $this->assertSame(70.0, (float) $fresh->coin_balance);
        $this->assertSame(60.0, (float) $fresh->usd_balance, 'USD must be untouched by a coin debit.');
    }

    /**
     * If currency were dropped on insert, the row would default to 'coin' and a
     * dollar payout would be indistinguishable from coins in every report.
     */
    public function test_currency_is_actually_persisted_and_not_defaulted(): void
    {
        $user = $this->makeWorker();

        EarningsService::credit($user, 10, 'REF-CUR', 'work_earn');

        $row = LedgerEntry::where('reference', 'REF-CUR')->firstOrFail();
        $this->assertSame('usd', $row->currency);
    }

    public function test_coin_rows_are_still_tagged_as_coin(): void
    {
        $user = $this->makeWorker(coins: 100);

        CoinService::deduct($user, 10, 'REF-COIN-TAG', 'task_apply');

        $row = LedgerEntry::where('reference', 'REF-COIN-TAG')->firstOrFail();
        $this->assertSame('coin', $row->currency, 'Existing coin flows must keep the coin default.');
    }

    public function test_currency_scopes_do_not_mix_the_two(): void
    {
        $user = $this->makeWorker(coins: 100, usd: 0);

        CoinService::deduct($user, 10, 'REF-MIX-COIN', 'task_apply');
        EarningsService::credit($user, 10, 'REF-MIX-USD', 'work_earn');

        $this->assertSame(1, LedgerEntry::where('user_id', $user->id)->inUsd()->count());
        $this->assertSame(1, LedgerEntry::where('user_id', $user->id)->inCoins()->count());
    }

    public function test_withdraw_reduces_usd_and_records_a_debit(): void
    {
        $user = $this->makeWorker(usd: 80);

        EarningsService::withdraw($user, 50, 'REF-WD', 'cashout', 'Cashout request');

        $this->assertSame(30.0, (float) $user->fresh()->usd_balance);
        $this->assertDatabaseHas('ledger_entries', [
            'reference'  => 'REF-WD',
            'entry_type' => '-',
            'currency'   => 'usd',
        ]);
    }

    public function test_withdraw_rejects_overdraw_and_keeps_the_balance(): void
    {
        $user = $this->makeWorker(usd: 20);

        try {
            EarningsService::withdraw($user, 100, 'REF-OVER');
            $this->fail('Expected a RuntimeException when withdrawing more than the balance.');
        } catch (\RuntimeException $e) {
            // expected
        }

        $this->assertSame(20.0, (float) $user->fresh()->usd_balance);
        $this->assertDatabaseMissing('ledger_entries', ['reference' => 'REF-OVER']);
    }

    public function test_reversing_a_withdrawal_returns_the_money_once(): void
    {
        $user = $this->makeWorker(usd: 100);

        EarningsService::withdraw($user, 40, 'REF-REV', 'cashout');
        $this->assertSame(60.0, (float) $user->fresh()->usd_balance);

        EarningsService::reverseWithdrawal($user, 40, 'REF-REV', 'Cashout rejected');
        $this->assertSame(100.0, (float) $user->fresh()->usd_balance);

        // Second attempt must refuse rather than pay twice.
        try {
            EarningsService::reverseWithdrawal($user, 40, 'REF-REV', 'Cashout rejected');
            $this->fail('Expected a RuntimeException on a repeated reversal.');
        } catch (\RuntimeException $e) {
            // expected
        }

        $this->assertSame(100.0, (float) $user->fresh()->usd_balance);
    }

    public function test_commission_books_against_the_platform_not_a_user(): void
    {
        $user = $this->makeWorker();

        EarningsService::recordCommission(7.50, 'REF-COMM', 'Commission: test task', $user->id);

        $this->assertDatabaseHas('ledger_entries', [
            'user_id'  => 0,
            'coins'    => 7.50000000,
            'category' => 'task_commission',
            'currency' => 'usd',
        ]);
        $this->assertSame(0.0, (float) $user->fresh()->usd_balance);
    }

    /**
     * Commission plus net must equal gross exactly. This is why usd_balance and
     * payout_usd are decimal(18,4) rather than 2 — rounding each share at two
     * places loses cents against the total.
     */
    public function test_a_split_payout_reconciles_to_gross(): void
    {
        $user  = $this->makeWorker();
        $gross = 33.33;
        $rate  = 17.5;

        $commission = round($gross * $rate / 100, 4);
        $net        = round($gross - $commission, 4);

        EarningsService::credit($user, $net, 'REF-SPLIT', 'work_earn');
        EarningsService::recordCommission($commission, 'REF-SPLIT', 'Commission', $user->id);

        $paidToWorker = (float) LedgerEntry::where('reference', 'REF-SPLIT')
            ->where('user_id', $user->id)->sum('coins');
        $paidToPlatform = (float) LedgerEntry::where('reference', 'REF-SPLIT')
            ->where('user_id', 0)->sum('coins');

        $this->assertSame($gross, round($paidToWorker + $paidToPlatform, 2));
    }

    public function test_zero_or_negative_credit_is_a_no_op(): void
    {
        $user = $this->makeWorker(usd: 10);

        EarningsService::credit($user, 0, 'REF-ZERO', 'work_earn');
        EarningsService::credit($user, -5, 'REF-NEG', 'work_earn');

        $this->assertSame(10.0, (float) $user->fresh()->usd_balance);
        $this->assertDatabaseMissing('ledger_entries', ['reference' => 'REF-ZERO']);
        $this->assertDatabaseMissing('ledger_entries', ['reference' => 'REF-NEG']);
    }

    /**
     * Guards the one-way rule structurally. If someone adds a spend method to
     * EarningsService, this test fails and forces the currency model to be
     * reconsidered deliberately rather than by accident.
     */
    public function test_earnings_service_exposes_no_way_to_spend_usd(): void
    {
        $allowed = [
            'credit', 'withdraw', 'reverseWithdrawal',
            'recordCommission', 'balance', 'hasBalance',
        ];

        $actual = array_map(
            fn (\ReflectionMethod $m) => $m->getName(),
            (new \ReflectionClass(EarningsService::class))->getMethods(\ReflectionMethod::IS_PUBLIC)
        );

        $this->assertSame(
            [],
            array_diff($actual, $allowed),
            'USD is withdrawal-only. A new public method on EarningsService means that rule changed.'
        );
    }
}
