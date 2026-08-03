<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Worker earnings in USD.
 *
 * Deliberately a separate class from CoinService rather than a currency argument
 * bolted onto it. The two currencies are not interchangeable and never convert:
 *
 *   JC coins  bought via topup, spent on task application fees
 *   USD       earned when admin approves delivered work, withdrawn, nothing else
 *
 * There is intentionally NO method here for spending USD. A worker cannot pay an
 * application fee out of earnings, and the way that rule is enforced is that the
 * door does not exist — not a boolean check somewhere that a future caller can
 * forget to make. If you find yourself wanting to add spendUsd(), the currency
 * model has changed and this whole file needs revisiting, along with the comment
 * in migration 0074.
 *
 * withdraw() is the single exit. It is named for its one legitimate caller (the
 * cashout flow) rather than a generic debit(), so that calling it from anywhere
 * else reads as obviously wrong in review.
 *
 * Static to match CoinService, which every existing money caller already uses.
 * Consistency beats purity here; a half-converted static-to-instance refactor is
 * what broke this codebase once already.
 */
class EarningsService
{
    /**
     * Currency tag written to ledger_entries.currency for every row this class
     * creates. Coin rows keep the column default of 'coin'.
     */
    public const CURRENCY = 'usd';

    /**
     * Credit USD earnings to a worker.
     *
     * Idempotency is the caller's job via $reference, the same contract
     * CoinService::credit() has. TaskReviewService already guards double payout
     * by locking the submission row and re-checking delivery_status.
     */
    public static function credit(
        User   $user,
        float  $usd,
        string $reference,
        string $category,
        string $description = ''
    ): void {
        if ($usd <= 0) {
            return;
        }

        DB::transaction(function () use ($user, $usd, $reference, $category, $description) {
            // Lock so the balance we read, write and record in balance_after
            // cannot move underneath us.
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            $locked->increment('usd_balance', $usd);
            $locked->refresh();

            self::writeRow($locked->id, $usd, '+', $locked->usd_balance, $reference, $category, $description);

            // Keep the caller's instance consistent with what was persisted.
            $user->usd_balance = $locked->usd_balance;
            $user->syncOriginalAttribute('usd_balance');
        });
    }

    /**
     * Take USD out of a worker's balance for a withdrawal.
     *
     * @throws \RuntimeException if the balance will not cover the amount
     */
    public static function withdraw(
        User   $user,
        float  $usd,
        string $reference,
        string $category = 'cashout',
        string $description = ''
    ): void {
        if ($usd <= 0) {
            throw new \RuntimeException('Withdrawal amount must be greater than zero.');
        }

        DB::transaction(function () use ($user, $usd, $reference, $category, $description) {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ((float) $locked->usd_balance < $usd) {
                throw new \RuntimeException('Insufficient USD balance.');
            }

            $locked->decrement('usd_balance', $usd);
            $locked->refresh();

            self::writeRow($locked->id, $usd, '-', $locked->usd_balance, $reference, $category, $description);

            $user->usd_balance = $locked->usd_balance;
            $user->syncOriginalAttribute('usd_balance');
        });
    }

    /**
     * Reverse a withdrawal, e.g. admin rejects a cashout request.
     *
     * The amount returned is read from the ORIGINAL DEBIT ROW, not from the caller's
     * $usd argument. That argument is now only a cross-check.
     *
     * This matters because a reversal must never be able to invent money. Before this
     * guard existed, rejecting a cashout credited usd_balance unconditionally, so:
     *
     *   - a cashout created before earnings moved to USD had debited coin_balance,
     *     and rejecting it credited USD that had never left USD;
     *   - a caller passing a stale or wrong figure would have been believed;
     *   - a cashout whose debit had somehow failed would still pay out on rejection.
     *
     * Same principle as fee_paid / fee_reference in the task application flow: reverse
     * the exact amount that was actually taken, sourced from the record of taking it.
     *
     * @param  string $reference  Reference of the ORIGINAL withdrawal
     * @throws \RuntimeException if there is no matching USD debit, if the amount
     *                           disagrees with it, or if it was already reversed
     */
    public static function reverseWithdrawal(
        User   $user,
        float  $usd,
        string $reference,
        string $description = ''
    ): void {
        if ($usd <= 0) {
            return;
        }

        DB::transaction(function () use ($user, $usd, $reference, $description) {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            $already = LedgerEntry::where('user_id', $locked->id)
                ->where('reference', $reference)
                ->where('entry_type', '+')
                ->where('currency', self::CURRENCY)
                ->where('category', 'cashout_reversed')
                ->exists();

            if ($already) {
                throw new \RuntimeException(
                    "Withdrawal {$reference} has already been reversed."
                );
            }

            // The debit this reversal is undoing. Its existence is the proof that money
            // actually left the USD balance under this reference.
            $debit = LedgerEntry::where('user_id', $locked->id)
                ->where('reference', $reference)
                ->where('entry_type', '-')
                ->where('currency', self::CURRENCY)
                ->where('category', 'cashout')
                ->first();

            if (! $debit) {
                throw new \RuntimeException(
                    "No USD withdrawal was recorded for reference {$reference}, so there "
                    . "is nothing to reverse. Refusing to credit money that never left "
                    . "the earnings balance. If this cashout predates USD earnings, it "
                    . "debited the coin balance and must be corrected there instead."
                );
            }

            $debited = (float) $debit->coins;

            // Tolerance covers decimal(18,4) round-tripping, nothing more.
            if (abs($debited - $usd) > 0.0001) {
                throw new \RuntimeException(
                    "Refund amount mismatch for {$reference}: caller asked to return "
                    . number_format($usd, 4) . " but the recorded debit was "
                    . number_format($debited, 4) . '.'
                );
            }

            $locked->increment('usd_balance', $debited);
            $locked->refresh();

            self::writeRow(
                $locked->id, $debited, '+', $locked->usd_balance,
                $reference, 'cashout_reversed', $description
            );

            $user->usd_balance = $locked->usd_balance;
            $user->syncOriginalAttribute('usd_balance');
        });
    }

    /**
     * Record the platform's commission on a USD payout.
     *
     * Commission is not a user balance movement, so it books against user_id 0
     * (the platform) without touching anybody's funds. Call it inside the same
     * transaction as the worker credit so the two halves always reconcile back
     * to the gross payout.
     */
    public static function recordCommission(
        float  $usd,
        string $reference,
        string $description = '',
        ?int   $sourceUserId = null
    ): void {
        if ($usd <= 0) {
            return;
        }

        self::writeRow(
            0, $usd, '+', 0, $reference, 'task_commission',
            $description . ($sourceUserId ? " (from user #{$sourceUserId})" : '')
        );
    }

    public static function balance(User $user): float
    {
        return (float) $user->usd_balance;
    }

    public static function hasBalance(User $user, float $amount): bool
    {
        return (float) $user->usd_balance >= $amount;
    }

    /**
     * One place that writes a USD ledger row.
     *
     * NOTE on the column name: ledger_entries stores the amount in a column
     * called `coins`, from when coins were the only currency. With the currency
     * dimension added in migration 0074 that column means "amount, denominated
     * in `currency`". Renaming it would touch every report and every existing
     * caller, so it stays. Read it together with `currency` or you will add
     * dollars to coins and get a meaningless total.
     */
    private static function writeRow(
        int    $userId,
        float  $amount,
        string $entryType,
        float  $balanceAfter,
        string $reference,
        string $category,
        string $description
    ): void {
        LedgerEntry::create([
            'user_id'       => $userId,
            'coins'         => $amount,
            'fee'           => 0,
            'balance_after' => $balanceAfter,
            'entry_type'    => $entryType,
            'reference'     => $reference,
            'description'   => $description,
            'category'      => $category,
            'currency'      => self::CURRENCY,
        ]);
    }
}
