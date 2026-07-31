<?php

namespace App\Services\Payment\Drivers;

use App\Models\CoinTopup;

class CoinbaseCommerceGateway
{
    public static function initiate(CoinTopup $topup, array $creds): string
    {
        $apiKey = $creds['api_key'] ?? '';

        $ch = curl_init('https://api.commerce.coinbase.com/charges');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'name'        => 'Coin Top-up',
                'description' => 'Top-up for ' . config('app.name'),
                'pricing_type'=> 'fixed_price',
                'local_price' => [
                    'amount'   => number_format((float) $topup->payable_amount, 2, '.', ''),
                    'currency' => strtoupper($topup->pay_currency ?? 'USD'),
                ],
                'metadata' => [
                    'topup_reference' => $topup->reference,
                    'user_id'         => $topup->user_id,
                ],
                'redirect_url' => route('payment.return', ['gateway' => 'CoinbaseCommerce']) . '?reference=' . $topup->reference,
                'cancel_url'   => route('payment.cancel', ['gateway' => 'CoinbaseCommerce']) . '?reference=' . $topup->reference,
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "X-CC-Api-Key: {$apiKey}",
                'X-CC-Version: 2018-03-22',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($body, true);

        if (empty($result['data']['hosted_url'])) {
            throw new \RuntimeException('Coinbase Commerce: failed to create charge. ' . ($result['error']['message'] ?? ''));
        }

        $topup->update(['gateway_response' => $result['data']['code']]);

        return $result['data']['hosted_url'];
    }

    public static function verifyWebhook(string $payload, string $signature, string $sharedSecret): array
    {
        $expected = hash_hmac('sha256', $payload, $sharedSecret);

        if (! hash_equals($expected, $signature)) {
            throw new \RuntimeException('Coinbase Commerce: invalid webhook signature.');
        }

        return json_decode($payload, true);
    }

    /**
     * Check if the charge event represents a successful payment.
     */
    public static function isPaymentComplete(array $event): bool
    {
        return ($event['event']['type'] ?? '') === 'charge:confirmed';
    }
}
