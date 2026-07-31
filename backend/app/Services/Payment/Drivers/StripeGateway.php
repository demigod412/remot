<?php

namespace App\Services\Payment\Drivers;

use App\Models\CoinTopup;

class StripeGateway
{
    /**
     * Create a Stripe Checkout Session and return the hosted URL.
     */
    public static function initiate(CoinTopup $topup, array $creds): string
    {
        $secretKey = $creds['secret_key'] ?? '';

        $params = http_build_query([
            'payment_method_types[0]'             => 'card',
            'line_items[0][price_data][currency]'               => strtolower($topup->pay_currency ?? 'usd'),
            'line_items[0][price_data][unit_amount]'            => (int) round((float) $topup->payable_amount * 100),
            'line_items[0][price_data][product_data][name]'     => 'Coin Top-up',
            'line_items[0][quantity]'                           => 1,
            'mode'                                              => 'payment',
            'client_reference_id'                               => $topup->reference,
            'success_url'                                       => route('payment.return', ['gateway' => 'StripeV3']) . '?reference=' . $topup->reference . '&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'                                        => route('payment.cancel', ['gateway' => 'StripeV3']) . '?reference=' . $topup->reference,
        ]);

        $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $params,
            CURLOPT_USERPWD        => $secretKey . ':',
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 30,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($body, true);

        if (empty($response['url'])) {
            throw new \RuntimeException('Stripe: failed to create checkout session. ' . ($response['error']['message'] ?? ''));
        }

        $topup->update(['gateway_response' => $response['id']]);

        return $response['url'];
    }

    /**
     * Retrieve a Stripe Checkout Session and verify payment.
     * Returns gateway reference (session ID) on success.
     */
    public static function verify(string $sessionId, array $creds, ?CoinTopup $topup = null): bool
    {
        $secretKey = $creds['secret_key'] ?? '';

        $ch = curl_init("https://api.stripe.com/v1/checkout/sessions/{$sessionId}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $secretKey . ':',
            CURLOPT_TIMEOUT        => 30,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        $session = json_decode($body, true);

        if (($session['payment_status'] ?? '') !== 'paid') {
            return false;
        }

        // Defence-in-depth: amount_total is in the minor unit (cents), mirroring
        // initiate()'s unit_amount; Stripe currencies are lower-case.
        if ($topup) {
            $expected = (int) round((float) $topup->payable_amount * 100);
            if ((int) ($session['amount_total'] ?? 0) < $expected) {
                return false;
            }
            if ($topup->pay_currency && strtolower((string) ($session['currency'] ?? '')) !== strtolower($topup->pay_currency)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verify Stripe webhook signature.
     */
    public static function verifyWebhook(string $payload, string $sigHeader, string $webhookSecret): array
    {
        $parts = [];
        foreach (explode(',', $sigHeader) as $part) {
            [$k, $v] = explode('=', $part, 2);
            $parts[$k][] = $v;
        }

        $timestamp = $parts['t'][0] ?? 0;
        $expected  = hash_hmac('sha256', "{$timestamp}.{$payload}", $webhookSecret);

        if (! hash_equals($expected, $parts['v1'][0] ?? '')) {
            throw new \RuntimeException('Stripe: invalid webhook signature.');
        }

        return json_decode($payload, true);
    }
}
