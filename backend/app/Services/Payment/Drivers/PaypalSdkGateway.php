<?php

namespace App\Services\Payment\Drivers;

use App\Models\CoinTopup;

class PaypalSdkGateway
{
    public static function initiate(CoinTopup $topup, array $creds): string
    {
        $token = static::getAccessToken($creds);

        $baseUrl = static::baseUrl($creds);
        $payload = [
            'intent'         => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $topup->reference,
                'amount'       => [
                    'currency_code' => strtoupper($topup->pay_currency ?? 'USD'),
                    'value'         => number_format((float) $topup->payable_amount, 2, '.', ''),
                ],
                'description' => 'Coin Top-up',
            ]],
            'application_context' => [
                'return_url' => route('payment.return', ['gateway' => 'PaypalSdk']) . '?reference=' . $topup->reference,
                'cancel_url' => route('payment.cancel', ['gateway' => 'PaypalSdk']) . '?reference=' . $topup->reference,
                'brand_name' => config('app.name'),
                'user_action' => 'PAY_NOW',
            ],
        ];

        $ch = curl_init("{$baseUrl}/v2/checkout/orders");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                "Authorization: Bearer {$token}",
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        $order = json_decode($body, true);

        $approveLink = collect($order['links'] ?? [])->firstWhere('rel', 'approve');

        if (empty($approveLink['href'])) {
            throw new \RuntimeException('PayPal: failed to create order. ' . ($order['message'] ?? ''));
        }

        $topup->update(['gateway_response' => $order['id']]);

        return $approveLink['href'];
    }

    public static function captureOrder(string $orderId, array $creds): bool
    {
        $token   = static::getAccessToken($creds);
        $baseUrl = static::baseUrl($creds);

        $ch = curl_init("{$baseUrl}/v2/checkout/orders/{$orderId}/capture");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => '{}',
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                "Authorization: Bearer {$token}",
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($body, true);

        return ($result['status'] ?? '') === 'COMPLETED';
    }

    private static function getAccessToken(array $creds): string
    {
        $clientId     = $creds['client_id'] ?? '';
        $clientSecret = $creds['client_secret'] ?? '';
        $baseUrl      = static::baseUrl($creds);

        $ch = curl_init("{$baseUrl}/v1/oauth2/token");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
            CURLOPT_USERPWD        => "{$clientId}:{$clientSecret}",
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 30,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($body, true);

        if (empty($result['access_token'])) {
            throw new \RuntimeException('PayPal: failed to obtain access token.');
        }

        return $result['access_token'];
    }

    private static function baseUrl(array $creds): string
    {
        return ($creds['mode'] ?? 'sandbox') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }
}
