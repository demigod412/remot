<?php

namespace Tests\Feature;

use App\Models\CoinPackage;
use App\Models\CoinTopup;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Services\Payment\PaymentService;

class PaymentServiceTest extends FeatureTestCase
{
    public function test_completing_topup_credits_package_coins_plus_bonus(): void
    {
        $user = $this->makeUser();

        $package = CoinPackage::create([
            'name' => 'Starter', 'coins' => 500, 'bonus_coins' => 100,
            'price' => 5, 'currency' => 'USD', 'status' => 1,
        ]);

        $topup = CoinTopup::create([
            'user_id'        => $user->id,
            'package_id'     => $package->id,
            'channel_code'   => 'manual',
            'amount'         => 5,
            'pay_currency'   => 'USD',
            'payable_amount' => 5,
            'reference'      => 'TX-BONUS',
            'status'         => 0,
        ]);

        PaymentService::completTopup($topup, 'gateway-ref');

        // 500 coins + 100 bonus must be credited (bonus was previously dropped).
        $this->assertSame(600.0, (float) $user->fresh()->coin_balance);
        $this->assertSame(1, (int) $topup->fresh()->status);
        $this->assertDatabaseHas('ledger_entries', [
            'user_id'   => $user->id,
            'reference' => 'TX-BONUS',
            'category'  => 'topup',
        ]);
    }

    /** A duplicate IPN / re-call must not credit twice. */
    public function test_completing_topup_is_idempotent(): void
    {
        $user = $this->makeUser();

        $topup = CoinTopup::create([
            'user_id'        => $user->id,
            'channel_code'   => 'manual',
            'amount'         => 200,
            'pay_currency'   => 'USD',
            'rate'           => 1,
            'payable_amount' => 200,
            'reference'      => 'TX-IDEMPOTENT',
            'status'         => 0,
        ]);

        PaymentService::completTopup($topup, 'ref');
        PaymentService::completTopup($topup, 'ref'); // duplicate delivery

        $this->assertSame(200.0, (float) $user->fresh()->coin_balance);
        $this->assertSame(1, LedgerEntry::where('reference', 'TX-IDEMPOTENT')->count());
    }
}
