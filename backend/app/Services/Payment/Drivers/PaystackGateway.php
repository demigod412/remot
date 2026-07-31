<?php

namespace App\Services\Payment\Drivers;

use App\Models\CoinTopup;

class PaystackGateway
{
    public static function initiate(CoinTopup $topup, array $creds): string
    {
        $secretKey = $creds['secret_key'] ?? '';

        $ch = curl_init('https://api.paystack.co/transaction/initialize');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'email'        => $topup->user->email ?? '',
                'amount'       => (int) round((float) $topup->payable_amount * 100),
                'currency'     => strtoupper($topup->pay_currency ?? 'NGN'),
                'reference'    => $topup->reference,
                'callback_url' => route('payment.return', ['gateway' => 'Paystack']) . '?reference=' . $topup->reference,
                'metadata'     => ['topup_reference' => $topup->reference],
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "Authorization: Bearer {$secretKey}",
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($body, true);

        if (empty($result['data']['authorization_url'])) {
            throw new \RuntimeException('Paystack: failed to initialize. ' . ($result['message'] ?? ''));
        }

        return $result['data']['authorization_url'];
    }

    public static function verify(string $reference, string $secretKey, ?CoinTopup $topup = null): bool
    {
        $ch = curl_init("https://api.paystack.co/transaction/verify/{$reference}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$secretKey}"],
            CURLOPT_TIMEOUT        => 30,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($body, true);
        $data   = $result['data'] ?? [];

        if (($data['status'] ?? '') !== 'success') {
            return false;
        }

        // Defence-in-depth: the charge must match what we initialised. Paystack
        // amounts are in the minor unit (kobo/cents), mirroring initiate().
        if ($topup) {
            $expected = (int) round((float) $topup->payable_amount * 100);
            if ((int) ($data['amount'] ?? 0) < $expected) {
                return false;
            }
            if ($topup->pay_currency && strtoupper((string) ($data['currency'] ?? '')) !== strtoupper($topup->pay_currency)) {
                return false;
            }
        }

        return true;
    }

    public static function verifyWebhook(string $payload, string $signature, string $secretKey): bool
    {
        $expected = hash_hmac('sha512', $payload, $secretKey);
        return hash_equals($expected, $signature);
    }
}
