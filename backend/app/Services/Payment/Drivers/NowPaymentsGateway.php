<?php

namespace App\Services\Payment\Drivers;

use App\Models\CoinTopup;

/**
 * NOWPayments (crypto) gateway driver.
 *
 * Follows the same shape as the other drivers in this folder: a static initiate()
 * that returns a hosted checkout URL, plus webhook verification helpers used by
 * the matching ProcessController.
 *
 * Credentials come from the payment_channels row (credentials JSON), not config,
 * matching every other gateway here:
 *   api_key     NOWPayments API key
 *   ipn_secret  IPN secret used to sign callbacks
 */
class NowPaymentsGateway
{
    protected const API = 'https://api.nowpayments.io/v1';

    public static function initiate(CoinTopup $topup, array $creds): string
    {
        $apiKey = $creds['api_key'] ?? '';

        if ($apiKey === '') {
            throw new \RuntimeException('NOWPayments: API key is not configured.');
        }

        $payload = [
            'price_amount'      => (float) number_format((float) $topup->payable_amount, 2, '.', ''),
            'price_currency'    => strtolower($topup->pay_currency ?? 'usd'),
            'order_id'          => $topup->reference,
            'order_description' => 'Coin top-up for ' . config('app.name'),
            'ipn_callback_url'  => route('ipn.NowPayments'),
            'success_url'       => route('payment.return', ['gateway' => 'NowPayments']) . '?reference=' . $topup->reference,
            'cancel_url'        => route('payment.cancel', ['gateway' => 'NowPayments']) . '?reference=' . $topup->reference,
        ];

        $ch = curl_init(self::API . '/invoice');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                "x-api-key: {$apiKey}",
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        $body = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException('NOWPayments: request failed. ' . $err);
        }

        $result = json_decode($body, true);

        if (empty($result['invoice_url'])) {
            throw new \RuntimeException(
                'NOWPayments: failed to create invoice. ' . ($result['message'] ?? $body)
            );
        }

        $topup->update(['gateway_response' => (string) ($result['id'] ?? '')]);

        return $result['invoice_url'];
    }

    /**
     * NOWPayments signs the IPN body with HMAC-SHA512 over the JSON payload with
     * its keys sorted alphabetically. Re-encoding without sorting produces a
     * different digest and every callback would be rejected.
     *
     * @throws \RuntimeException when the signature does not match
     */
    public static function verifyWebhook(string $payload, string $signature, string $ipnSecret): array
    {
        if ($ipnSecret === '') {
            throw new \RuntimeException('NOWPayments: IPN secret is not configured.');
        }

        $data = json_decode($payload, true);

        if (! is_array($data)) {
            throw new \RuntimeException('NOWPayments: webhook payload is not valid JSON.');
        }

        $sorted = $data;
        ksort($sorted);

        $expected = hash_hmac(
            'sha512',
            json_encode($sorted, JSON_UNESCAPED_SLASHES),
            $ipnSecret
        );

        if (! hash_equals($expected, trim($signature))) {
            throw new \RuntimeException('NOWPayments: invalid webhook signature.');
        }

        return $data;
    }

    /**
     * Only these two states mean the money has actually landed. "confirming",
     * "sending", "waiting" and "partially_paid" must NOT credit coins.
     */
    public static function isPaymentComplete(array $event): bool
    {
        return in_array($event['payment_status'] ?? '', ['finished', 'confirmed'], true);
    }

    public static function referenceFrom(array $event): string
    {
        return (string) ($event['order_id'] ?? '');
    }

    public static function transactionIdFrom(array $event): string
    {
        return (string) ($event['payment_id'] ?? $event['invoice_id'] ?? '');
    }
}
