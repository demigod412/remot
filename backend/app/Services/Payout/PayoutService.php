<?php

namespace App\Services\Payout;

use App\Models\Cashout;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Services\NotifyService;
use App\Services\Payout\Drivers\PayInDriver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayoutService
{
    /**
     * Dispatch a disbursement to the correct driver based on the payout method's driver field.
     * Returns the gateway_reference (request_ref).
     * Throws on failure.
     */
    public static function disburse(Cashout $cashout): string
    {
        $method = $cashout->payoutMethod;
        $creds  = is_string($method->credentials)
            ? (json_decode($method->credentials, true) ?? [])
            : ($method->credentials ?? []);
        $driver = strtolower($method->driver ?? '');

        return match (true) {
            str_contains($driver, 'payin') => PayInDriver::disburse($cashout, $creds),
            default => throw new \RuntimeException(
                "No automatic disbursement driver is configured for \"{$driver}\". Process this cashout manually."
            ),
        };
    }

    /**
     * Called by the PayIn IPN when a disbursement webhook arrives with status=completed.
     * Marks the cashout as fully approved and notifies the user.
     * Idempotent: skips if already approved.
     */
    public static function completeDisbursement(Cashout $cashout, string $gatewayRef = ''): void
    {
        if ($cashout->status === 1) {
            return;
        }

        DB::transaction(function () use ($cashout, $gatewayRef) {
            $cashout->update([
                'status'            => 1,
                'gateway_reference' => $gatewayRef ?: $cashout->gateway_reference,
            ]);

            // Denominated in USD: withdrawals come out of the earnings balance, so
            // this row must be tagged accordingly or it pollutes every coin report.
            //
            // PRE-EXISTING ISSUE, deliberately left alone: the balance was already
            // debited when the worker submitted the request, and this writes a
            // SECOND '-' row against the same reference. Summing cashout rows
            // therefore double-counts. That behaviour predates the USD split; fixing
            // it changes historical report totals, so it needs its own commit and a
            // decision about existing data.
            LedgerEntry::create([
                'user_id'       => $cashout->user_id,
                'coins'         => $cashout->net_coins_deducted,
                'fee'           => $cashout->fee,
                'balance_after' => $cashout->user?->usd_balance ?? 0,
                'entry_type'    => '-',
                'reference'     => $cashout->reference,
                'description'   => 'Cashout paid via ' . ($cashout->payoutMethod?->name ?? 'payout method'),
                'category'      => 'cashout',
                'currency'      => 'usd',
            ]);
        });

        if ($cashout->user) {
            NotifyService::send($cashout->user, 'CASHOUT_APPROVED', [
                'coins'     => number_format($cashout->net_coins_deducted, 0),
                'reference' => $cashout->reference,
            ]);
        }
    }

    /**
     * Called by the PayIn IPN when a disbursement fails.
     * Refunds coins and marks the cashout as failed.
     */
    public static function failDisbursement(Cashout $cashout, string $reason = ''): void
    {
        if ($cashout->status !== 3) {
            return;
        }

        DB::transaction(function () use ($cashout, $reason) {
            $user = User::findOrFail($cashout->user_id);

            \App\Services\EarningsService::reverseWithdrawal(
                $user,
                (float) $cashout->net_coins_deducted,
                $cashout->reference,
                'Cashout refunded (disbursement failed)'
            );

            // The ledger row is written by EarningsService::reverseWithdrawal()
            // above, tagged currency = usd. Do not add a second one here.
            $cashout->update([
                'status'     => 4,
                'admin_note' => $reason ?: 'Automatic disbursement failed.',
            ]);
        });

        if ($cashout->user) {
            NotifyService::send($cashout->user, 'CASHOUT_REJECTED', [
                'coins'     => number_format($cashout->net_coins_deducted, 0),
                'reference' => $cashout->reference,
                'reason'    => $reason ?: 'Disbursement failed. Your earnings have been returned to your USD balance.',
            ]);
        }
    }
}
