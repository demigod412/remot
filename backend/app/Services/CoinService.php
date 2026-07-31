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
     * Check if user has enough balance.
     */
    public static function hasBalance(User $user, float $amount): bool
    {
        return $user->coin_balance >= $amount;
    }
}
