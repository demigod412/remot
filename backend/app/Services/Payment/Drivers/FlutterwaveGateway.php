<?php

namespace App\Services\Payment\Drivers;

use App\Models\CoinTopup;

class FlutterwaveGateway
{
    public static function initiate(CoinTopup $topup, array $creds): string
    {
        $secretKey = $creds['secret_key'] ?? '';
        $user      = $topup->user;

        $ch = curl_init('https://api.flutterwave.com/v3/payments');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'tx_ref'       => $topup->reference,
                'amount'       => (float) $topup->payable_amount,
                'currency'     => strtoupper($topup->pay_currency ?? 'USD'),
                'redirect_url' => route('payment.return', ['gateway' => 'Flutterwave']) . '?reference=' . $topup->reference,
                'customer'     => [
                    'email'       => $user->email ?? '',
                    'name'        => ($user->firstname ?? '') . ' ' . ($user->lastname ?? ''),
                    'phonenumber' => $user->mobile ?? '',
                ],
                'customizations' => [
                    'title'       => config('app.name') . ' Top-up',
                    'description' => 'Coin Top-up',
                ],
                'meta' => ['topup_reference' => $topup->reference],
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

        if (($result['status'] ?? '') !== 'success' || empty($result['data']['link'])) {
            throw new \RuntimeException('Flutterwave: ' . ($result['message'] ?? 'failed to initiate payment.'));
        }

        return $result['data']['link'];
    }

    public static function verify(string $transactionId, string $secretKey, ?CoinTopup $topup = null): bool
    {
        $ch = curl_init("https://api.flutterwave.com/v3/transactions/{$transactionId}/verify");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$secretKey}"],
            CURLOPT_TIMEOUT        => 30,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($body, true);
        $data   = $result['data'] ?? [];

        if (($data['status'] ?? '') !== 'successful') {
            return false;
        }

        // Defence-in-depth: Flutterwave reports amounts in major currency units,
        // mirroring initiate()'s payable_amount.
        if ($topup) {
            if ((float) ($data['amount'] ?? 0) + 0.001 < (float) $topup->payable_amount) {
                return false;
            }
            if ($topup->pay_currency && strtoupper((string) ($data['currency'] ?? '')) !== strtoupper($topup->pay_currency)) {
                return false;
            }
        }

        return true;
    }

    public static function verifyWebhook(string $hash, string $secretHash): bool
    {
        return hash_equals($secretHash, $hash);
    }
}
