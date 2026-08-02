<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CoinService
{
    /**
     * Credit coins to a user's balance.
     */
    public static function credit(
        User   $user,
        float  $coins,
        string $reference,
        string $category,
        string $description = '',
        float  $fee = 0
    ): void {
        DB::transaction(function () use ($user, $coins, $reference, $category, $description, $fee) {
            $user->increment('coin_balance', $coins);
            $user->refresh();

            LedgerEntry::create([
                'user_id'       => $user->id,
                'coins'         => $coins,
                'fee'           => $fee,
                'balance_after' => $user->coin_balance,
                'entry_type'    => '+',
                'reference'     => $reference,
                'description'   => $description,
                'category'      => $category,
            ]);
        });
    }

    /**
     * Deduct coins from a user's balance.
     *
     * @throws \RuntimeException if balance is insufficient
     */
    public static function deduct(
        User   $user,
        float  $coins,
        string $reference,
        string $category,
        string $description = '',
        float  $fee = 0
    ): void {
        $total = $coins + $fee;

        DB::transaction(function () use ($user, $coins, $reference, $category, $description, $fee, $total) {
            // Lock the user row so two concurrent debits can't both read the same
            // balance and overdraw it (prevents double-spend / negative balances).
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($locked->coin_balance < $total) {
                throw new \RuntimeException('Insufficient coin balance.');
            }

            $locked->decrement('coin_balance', $total);
            $locked->refresh();

            LedgerEntry::create([
                'user_id'       => $locked->id,
                'coins'         => $coins,
                'fee'           => $fee,
                'balance_after' => $locked->coin_balance,
                'entry_type'    => '-',
                'reference'     => $reference,
                'description'   => $description,
                'category'      => $category,
            ]);

            // Keep the caller's model instance consistent with the persisted balance.
            $user->coin_balance = $locked->coin_balance;
            $user->syncOriginalAttribute('coin_balance');
        });
    }

    /**
     * Return coins a user previously spent.
     *
     * Deliberately separate from credit(): a refund is an idempotency-sensitive
     * reversal, not fresh earnings, so it takes the reference of the original
     * debit and refuses to run twice against it. Without that guard, a double
     * click on "reject application" pays the fee back twice.
     *
     * @param  string $reference  The reference of the ORIGINAL debit being reversed
     * @throws \RuntimeException if this reference has already been refunded
     */
    public static function refund(
        User   $user,
        float  $coins,
        string $reference,
        string $category = 'work_refund',
        string $description = ''
    ): void {
        if ($coins <= 0) {
            return;
        }

        DB::transaction(function () use ($user, $coins, $reference, $category, $description) {
            // Lock the user row for the duration so the balance we read, write and
            // record in balance_after cannot be changed underneath us.
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            $already = LedgerEntry::where('user_id', $locked->id)
                ->where('reference', $reference)
                ->where('entry_type', '+')
                ->where('category', $category)
                ->exists();

            if ($already) {
                throw new \RuntimeException(
                    "Refund for reference {$reference} has already been issued."
                );
            }

            $locked->increment('coin_balance', $coins);
            $locked->refresh();

            LedgerEntry::create([
                'user_id'       => $locked->id,
                'coins'         => $coins,
                'fee'           => 0,
                'balance_after' => $locked->coin_balance,
                'entry_type'    => '+',
                'reference'     => $reference,
                'description'   => $description,
                'category'      => $category,
            ]);

            // Keep the caller's instance consistent with what was persisted.
            $user->coin_balance = $locked->coin_balance;
            $user->syncOriginalAttribute('coin_balance');
        });
    }

    /**
     * Record the platform's cut on a payout.
     *
     * Commission is not a user balance movement, so this writes a ledger row
     * against user_id 0 (the platform) rather than touching anybody's coins.
     * Call it in the same transaction as the worker credit so the two halves of
     * the split always reconcile against the gross amount.
     */
    public static function recordCommission(
        float  $coins,
        string $reference,
        string $description = '',
        ?int   $sourceUserId = null
    ): void {
        if ($coins <= 0) {
            return;
        }

        LedgerEntry::create([
            'user_id'       => 0,
            'coins'         => $coins,
            'fee'           => 0,
            'balance_after' => 0,
            'entry_type'    => '+',
            'reference'     => $reference,
            'description'   => $description . ($sourceUserId ? " (from user #{$sourceUserId})" : ''),
            'category'      => 'task_commission',
        ]);
    }

    /**
     * Check if user has enough balance.
     */
    public static function hasBalance(User $user, float $amount): bool
    {
        return $user->coin_balance >= $amount;
    }
}
