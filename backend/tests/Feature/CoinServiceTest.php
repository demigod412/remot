<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CoinService;

class CoinServiceTest extends FeatureTestCase
{
    public function test_credit_increases_balance_and_records_ledger(): void
    {
        $user = $this->makeUser([], 100);

        CoinService::credit($user, 50, 'REF-CREDIT', 'topup', 'Test credit');

        $this->assertSame(150.0, (float) $user->fresh()->coin_balance);
        $this->assertDatabaseHas('ledger_entries', [
            'user_id'    => $user->id,
            'entry_type' => '+',
            'reference'  => 'REF-CREDIT',
            'category'   => 'topup',
        ]);
    }

    public function test_deduct_reduces_balance_and_records_ledger(): void
    {
        $user = $this->makeUser([], 100);

        CoinService::deduct($user, 40, 'REF-DEDUCT', 'work_spend', 'Test deduct');

        $this->assertSame(60.0, (float) $user->fresh()->coin_balance);
        $this->assertDatabaseHas('ledger_entries', [
            'user_id'    => $user->id,
            'entry_type' => '-',
            'reference'  => 'REF-DEDUCT',
        ]);
    }

    /** The core double-spend guard: an overdraw must throw and change nothing. */
    public function test_deduct_rejects_overdraw_and_keeps_balance(): void
    {
        $user = $this->makeUser([], 30);

        try {
            CoinService::deduct($user, 100, 'REF-OVERDRAW', 'work_spend');
            $this->fail('Expected a RuntimeException when deducting more than the balance.');
        } catch (\RuntimeException $e) {
            // expected
        }

        $this->assertSame(30.0, (float) $user->fresh()->coin_balance);
        $this->assertDatabaseMissing('ledger_entries', ['reference' => 'REF-OVERDRAW']);
    }

    public function test_has_balance_reflects_current_funds(): void
    {
        $user = $this->makeUser([], 75);

        $this->assertTrue(CoinService::hasBalance($user, 75));
        $this->assertFalse(CoinService::hasBalance($user, 76));
    }
}
