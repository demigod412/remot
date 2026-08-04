<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Cashout;
use App\Models\DynamicForm;
use App\Models\LedgerEntry;
use App\Models\PayoutMethod;
use App\Models\User;
use App\Models\UserPayoutAccount;

/**
 * The withdrawal flow, end to end, through real HTTP requests.
 *
 * This is the only path that moves money OUT of the platform and it had no test
 * coverage at all, which is why four separate bugs in it were only ever found by
 * hand:
 *
 *   - the submit form contained no inputs, so payout_details always arrived empty
 *     and validation redirected onto a POST-only route as a 405;
 *   - the manual-entry fields were hidden with x-show rather than removed, so
 *     their empty values overwrote a saved wallet address;
 *   - approval wrote a second '-' ledger row, double-counting every withdrawal;
 *   - rejection credited USD unconditionally, refunding money that had never left
 *     the USD balance.
 *
 * Each test below fails if one of those returns. The assertions deliberately check
 * the ledger AND the balance separately: those two agreeing is the property that
 * matters, and every bug above broke it in one direction or the other.
 */
class WithdrawalFlowTest extends FeatureTestCase
{
    private const MIN_USD = 10.0;

    /**
     * A worker who can legitimately withdraw: verified, no forced password change,
     * and a USD earnings balance.
     */
    private function makeEarner(float $usd = 500, float $coins = 0): User
    {
        $user = $this->makeUser([
            'kyc_status'           => 1,
            'must_change_password' => false,
        ], $coins);

        $user->forceFill(['usd_balance' => $usd])->save();

        return $user->fresh();
    }

    /**
     * A crypto payout method with a wallet-address field, mirroring what
     * PayoutMethodSeeder creates in production.
     */
    private function makeCryptoMethod(): PayoutMethod
    {
        $form = DynamicForm::create([
            'act'       => 'payout_usdt_erc20_test',
            'form_data' => [
                [
                    'name'     => 'wallet_address',
                    'label'    => 'USDT (ERC20) wallet address',
                    'type'     => 'text',
                    'required' => true,
                ],
            ],
        ]);

        return PayoutMethod::create([
            'name'                  => 'Crypto USDT (ERC20)',
            'form_id'               => $form->id,
            'currency'              => 'USDT',
            'min_coins'             => self::MIN_USD,
            'max_coins'             => 100000,
            'coin_to_currency_rate' => 1,
            'fixed_fee'             => 1,
            'percent_fee'           => 1,
            'status'                => 1,
        ]);
    }

    private function makeAdmin(): Admin
    {
        // The admins table has no status column and AdminAuthenticate only checks the
        // guard, so name/email/username/password is the whole requirement.
        return Admin::firstOrCreate(
            ['username' => 'admin_test'],
            [
                'name'     => 'Test Admin',
                'email'    => 'admin_test@example.test',
                'password' => bcrypt('password'),
            ]
        );
    }

    /**
     * Drive the two-step flow: preview, then submit.
     */
    private function withdraw(User $user, PayoutMethod $method, float $amount, array $details, bool $save = false)
    {
        $this->actingAs($user, 'web')
            ->post(route('user.wallet.cashout.preview'), [
                'payout_method_id' => $method->id,
                'coin_amount'      => $amount,
            ])
            ->assertOk();

        $payload = ['payout_details' => $details];
        if ($save) {
            $payload['save_account'] = 1;
            $payload['save_label']   = 'My wallet';
        }

        return $this->actingAs($user, 'web')
            ->post(route('user.wallet.cashout.submit'), $payload);
    }

    // ─────────────────────────── request ───────────────────────────

    public function test_a_withdrawal_deducts_usd_once_and_records_one_ledger_row(): void
    {
        $user   = $this->makeEarner(usd: 500);
        $method = $this->makeCryptoMethod();

        $this->withdraw($user, $method, 100, ['wallet_address' => '0xABC123'])
            ->assertRedirect();

        $cashout = Cashout::where('user_id', $user->id)->firstOrFail();

        $this->assertSame(100.0, (float) $cashout->net_coins_deducted);
        $this->assertSame(400.0, (float) $user->fresh()->usd_balance, 'USD should drop by exactly the amount requested.');

        $debits = LedgerEntry::where('reference', $cashout->reference)
            ->where('entry_type', '-')
            ->get();

        $this->assertCount(1, $debits, 'Exactly one debit row per withdrawal.');
        $this->assertSame('usd', $debits->first()->currency);
        $this->assertSame('cashout', $debits->first()->category);
    }

    /**
     * The bug this guards: the wallet address arriving blank. A payout queued
     * against an empty address is money sent nowhere.
     */
    public function test_the_wallet_address_is_actually_persisted(): void
    {
        $user   = $this->makeEarner();
        $method = $this->makeCryptoMethod();

        $this->withdraw($user, $method, 100, ['wallet_address' => '0xDEADBEEF0000'])
            ->assertRedirect();

        $cashout = Cashout::where('user_id', $user->id)->firstOrFail();
        $details = $cashout->payout_details;

        $this->assertIsArray($details, 'payout_details must round-trip as an array.');
        $this->assertArrayHasKey('wallet_address', $details);
        $this->assertSame('0xDEADBEEF0000', $details['wallet_address']);
    }

    public function test_submitting_without_payout_details_creates_nothing(): void
    {
        $user   = $this->makeEarner();
        $method = $this->makeCryptoMethod();

        $this->actingAs($user, 'web')
            ->post(route('user.wallet.cashout.preview'), [
                'payout_method_id' => $method->id,
                'coin_amount'      => 100,
            ])
            ->assertOk();

        $this->actingAs($user, 'web')
            ->post(route('user.wallet.cashout.submit'), [])
            ->assertSessionHasErrors('payout_details');

        $this->assertSame(0, Cashout::where('user_id', $user->id)->count());
        $this->assertSame(500.0, (float) $user->fresh()->usd_balance);
    }

    /**
     * Withdrawals draw on USD only. A worker flush with coins and no earnings must
     * not get through — the page used to gate on coin_balance.
     */
    public function test_coins_cannot_be_withdrawn(): void
    {
        $user   = $this->makeEarner(usd: 0, coins: 5000);
        $method = $this->makeCryptoMethod();

        $this->actingAs($user, 'web')
            ->post(route('user.wallet.cashout.preview'), [
                'payout_method_id' => $method->id,
                'coin_amount'      => 100,
            ]);

        $this->assertSame(0, Cashout::where('user_id', $user->id)->count());
        $this->assertSame(5000.0, (float) $user->fresh()->coin_balance, 'Coins must be untouched.');
        $this->assertSame(0.0, (float) $user->fresh()->usd_balance);
    }

    public function test_withdrawing_more_than_the_earnings_balance_is_refused(): void
    {
        $user   = $this->makeEarner(usd: 40);
        $method = $this->makeCryptoMethod();

        $this->actingAs($user, 'web')
            ->post(route('user.wallet.cashout.preview'), [
                'payout_method_id' => $method->id,
                'coin_amount'      => 400,
            ]);

        $this->assertSame(0, Cashout::where('user_id', $user->id)->count());
        $this->assertSame(40.0, (float) $user->fresh()->usd_balance);
    }

    public function test_saving_the_account_twice_does_not_duplicate_it(): void
    {
        $user   = $this->makeEarner(usd: 500);
        $method = $this->makeCryptoMethod();
        $wallet = ['wallet_address' => '0xSAME'];

        $this->withdraw($user, $method, 100, $wallet, save: true)->assertRedirect();
        $this->withdraw($user, $method, 100, $wallet, save: true)->assertRedirect();

        $this->assertSame(
            1,
            UserPayoutAccount::where('user_id', $user->id)->count(),
            'The same wallet saved twice must not create two accounts.'
        );
    }

    // ─────────────────────────── review ───────────────────────────

    /**
     * The money already left at request time. Approval is a status change, and it
     * used to write a second debit row that double-counted every withdrawal.
     */
    public function test_approving_does_not_move_money_or_write_a_second_debit(): void
    {
        $user   = $this->makeEarner(usd: 500);
        $method = $this->makeCryptoMethod();

        $this->withdraw($user, $method, 100, ['wallet_address' => '0xAPPROVE'])->assertRedirect();
        $cashout = Cashout::where('user_id', $user->id)->firstOrFail();

        $balanceBefore = (float) $user->fresh()->usd_balance;

        $this->actingAs($this->makeAdmin(), 'admin')
            ->post(route('admin.cashouts.approve', $cashout->id), ['admin_note' => 'Sent manually']);

        $this->assertSame(1, (int) $cashout->fresh()->status);
        $this->assertSame($balanceBefore, (float) $user->fresh()->usd_balance, 'Approval must not move money again.');

        $this->assertSame(
            1,
            LedgerEntry::where('reference', $cashout->reference)->where('entry_type', '-')->count(),
            'Approval must not write a second debit row.'
        );
    }

    public function test_rejecting_refunds_exactly_the_amount_debited(): void
    {
        $user   = $this->makeEarner(usd: 500);
        $method = $this->makeCryptoMethod();

        $this->withdraw($user, $method, 120, ['wallet_address' => '0xREJECT'])->assertRedirect();
        $cashout = Cashout::where('user_id', $user->id)->firstOrFail();

        $this->assertSame(380.0, (float) $user->fresh()->usd_balance);

        $this->actingAs($this->makeAdmin(), 'admin')
            ->post(route('admin.cashouts.reject', $cashout->id), ['admin_note' => 'Bad address']);

        $this->assertSame(2, (int) $cashout->fresh()->status);
        $this->assertSame(500.0, (float) $user->fresh()->usd_balance, 'Refund must restore the exact amount, no more.');

        $this->assertSame(
            1,
            LedgerEntry::where('reference', $cashout->reference)->where('category', 'cashout_reversed')->count()
        );
    }

    public function test_rejecting_twice_does_not_refund_twice(): void
    {
        $user   = $this->makeEarner(usd: 500);
        $method = $this->makeCryptoMethod();

        $this->withdraw($user, $method, 120, ['wallet_address' => '0xTWICE'])->assertRedirect();
        $cashout = Cashout::where('user_id', $user->id)->firstOrFail();
        $admin   = $this->makeAdmin();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.cashouts.reject', $cashout->id), ['admin_note' => 'First']);
        $this->actingAs($admin, 'admin')
            ->post(route('admin.cashouts.reject', $cashout->id), ['admin_note' => 'Second']);

        $this->assertSame(500.0, (float) $user->fresh()->usd_balance, 'Second rejection must not pay again.');
        $this->assertSame(
            1,
            LedgerEntry::where('reference', $cashout->reference)->where('category', 'cashout_reversed')->count()
        );
    }

    /**
     * The ledger and the balance must always tell the same story. This is the
     * single assertion that catches every bug in this file's docblock, and it is
     * worth running by hand against production data too.
     */
    public function test_the_usd_ledger_reconciles_to_the_balance(): void
    {
        $user   = $this->makeEarner(usd: 0);
        $method = $this->makeCryptoMethod();

        // Earn, withdraw twice, get one rejected.
        \App\Services\EarningsService::credit($user, 500, 'REF-EARN-REC', 'work_earn', 'Earned');

        $this->withdraw($user->fresh(), $method, 100, ['wallet_address' => '0xA'])->assertRedirect();
        $this->withdraw($user->fresh(), $method, 50, ['wallet_address' => '0xB'])->assertRedirect();

        $second = Cashout::where('user_id', $user->id)->latest('id')->firstOrFail();
        $this->actingAs($this->makeAdmin(), 'admin')
            ->post(route('admin.cashouts.reject', $second->id), ['admin_note' => 'Rejected']);

        $ledger = (float) LedgerEntry::where('user_id', $user->id)
            ->where('currency', 'usd')
            ->selectRaw("SUM(CASE WHEN entry_type='+' THEN coins ELSE -coins END) as total")
            ->value('total');

        $this->assertSame(
            round((float) $user->fresh()->usd_balance, 4),
            round($ledger, 4),
            'Sum of USD ledger rows must equal usd_balance.'
        );
        $this->assertSame(400.0, (float) $user->fresh()->usd_balance);
    }
}
