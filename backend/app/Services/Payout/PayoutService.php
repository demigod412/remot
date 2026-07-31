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

            LedgerEntry::create([
                'user_id'       => $cashout->user_id,
                'coins'         => $cashout->net_coins_deducted,
                'fee'           => $cashout->fee,
                'balance_after' => $cashout->user?->coin_balance ?? 0,
                'entry_type'    => '-',
                'reference'     => $cashout->reference,
                'description'   => 'Cashout paid via ' . ($cashout->payoutMethod?->name ?? 'payout method'),
                'category'      => 'cashout',
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
            $user = User::lockForUpdate()->findOrFail($cashout->user_id);
            $user->increment('coin_balance', $cashout->net_coins_deducted);
            $user->refresh();

            LedgerEntry::create([
                'user_id'       => $user->id,
                'coins'         => $cashout->net_coins_deducted,
                'fee'           => 0,
                'balance_after' => $user->coin_balance,
                'entry_type'    => '+',
                'reference'     => $cashout->reference,
                'description'   => 'Cashout refunded (disbursement failed)',
                'category'      => 'cashout',
            ]);

            $cashout->update([
                'status'     => 4,
                'admin_note' => $reason ?: 'Automatic disbursement failed.',
            ]);
        });

        if ($cashout->user) {
            NotifyService::send($cashout->user, 'CASHOUT_REJECTED', [
                'coins'     => number_format($cashout->net_coins_deducted, 0),
                'reference' => $cashout->reference,
                'reason'    => $reason ?: 'Disbursement failed. Your coins have been refunded.',
            ]);
        }
    }
}
