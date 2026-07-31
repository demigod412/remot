<?php

namespace App\Services\Payment;

use App\Models\CoinTopup;
use App\Models\LedgerEntry;
use App\Models\NotificationLog;
use App\Models\PaymentChannel;
use App\Models\User;
use App\Services\NotifyService;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Initiate a payment for the given topup.
     * Returns a redirect URL or throws on failure.
     */
    public static function initiate(CoinTopup $topup, PaymentChannel $channel): string
    {
        $creds  = $channel->credentials ?? [];
        $driver = strtolower($channel->driver ?? $channel->code);

        return match (true) {
            str_contains($driver, 'stripe')           => Drivers\StripeGateway::initiate($topup, $creds),
            str_contains($driver, 'paypalsdk')        => Drivers\PaypalSdkGateway::initiate($topup, $creds),
            str_contains($driver, 'paypal')           => Drivers\PaypalGateway::initiate($topup, $creds),
            str_contains($driver, 'razorpay')         => Drivers\RazorpayGateway::initiate($topup, $creds),
            str_contains($driver, 'paystack')         => Drivers\PaystackGateway::initiate($topup, $creds),
            str_contains($driver, 'flutterwave')      => Drivers\FlutterwaveGateway::initiate($topup, $creds),
            str_contains($driver, 'paytm')            => Drivers\PaytmGateway::initiate($topup, $creds),
            str_contains($driver, 'coinpayments')     => Drivers\CoinPaymentsGateway::initiate($topup, $creds),
            str_contains($driver, 'coinbasecommerce') => Drivers\CoinbaseCommerceGateway::initiate($topup, $creds),
            str_contains($driver, 'instamojo')        => Drivers\InstamojoGateway::initiate($topup, $creds),
            str_contains($driver, 'perfectmoney')     => Drivers\PerfectMoneyGateway::initiate($topup, $creds),
            str_contains($driver, 'voguepay')         => Drivers\VoguepayGateway::initiate($topup, $creds),
            str_contains($driver, 'payin')            => Drivers\PayInGateway::initiate($topup, $creds),
            default                                   => throw new \RuntimeException("Unsupported gateway: {$driver}"),
        };
    }

    /**
     * Credit a topup — call this after successful IPN / return verification.
     * Idempotent: silently skips if already credited (status 1).
     */
    public static function completTopup(CoinTopup $topup, string $gatewayRef = ''): void
    {
        if ($topup->status === 1) {
            return;
        }

        $gs    = gs();
        $coins = static::resolveCoins($topup, $gs);

        DB::transaction(function () use ($topup, $coins, $gatewayRef) {
            $user = User::lockForUpdate()->findOrFail($topup->user_id);

            $user->increment('coin_balance', $coins);
            $user->refresh();

            LedgerEntry::create([
                'user_id'       => $user->id,
                'coins'         => $coins,
                'fee'           => 0,
                'balance_after' => $user->coin_balance,
                'entry_type'    => '+',
                'category'      => 'topup',
                'reference'     => $topup->reference,
                'description'   => 'Coin top-up via ' . ($topup->channel->name ?? $topup->channel_code),
            ]);

            $topup->update([
                'status'          => 1,
                'coins_credited'  => $coins,
                'gateway_response'=> $gatewayRef ?: $topup->gateway_response,
            ]);
        });

        $topup->load('user');
        if ($topup->user) {
            NotifyService::send($topup->user, 'TOPUP_APPROVED', [
                'coins'     => number_format($coins, 0),
                'reference' => $topup->reference,
            ]);
        }
    }

    /**
     * Mark a topup as failed/cancelled.
     */
    public static function failTopup(CoinTopup $topup): void
    {
        if ($topup->status !== 0) {
            return;
        }

        $topup->update(['status' => 3]);
    }

    /**
     * Determine how many coins to credit based on package or rate.
     */
    private static function resolveCoins(CoinTopup $topup, $gs): float
    {
        // Explicit package — credit the package's coins PLUS any bonus coins.
        if ($topup->package_id && $topup->package) {
            return (float) $topup->package->total_coins;
        }

        // Use coin_rate from AppSetting (coins per 1 unit of currency)
        $rate = (float) ($gs->coin_rate ?? 1);
        if ($rate <= 0) {
            $rate = 1;
        }

        return round((float) $topup->amount * $rate, 2);
    }

    // -----------------------------------------------------------------------
    // HTTP helpers shared by process controllers
    // -----------------------------------------------------------------------

    public static function curlPost(string $url, array $payload, array $headers = []): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => array_merge(['Content-Type: application/json'], $headers),
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno) {
            throw new \RuntimeException("cURL error {$errno}");
        }

        return json_decode($body, true) ?? [];
    }

    public static function curlGet(string $url, array $headers = []): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno) {
            throw new \RuntimeException("cURL error {$errno}");
        }

        return json_decode($body, true) ?? [];
    }
}
