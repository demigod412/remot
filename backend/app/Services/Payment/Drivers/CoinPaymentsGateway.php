<?php

namespace App\Services\Payment\Drivers;

use App\Models\CoinTopup;

class CoinPaymentsGateway
{
    private const API_URL = 'https://www.coinpayments.net/api.php';

    public static function initiate(CoinTopup $topup, array $creds): string
    {
        $publicKey  = $creds['public_key'] ?? '';
        $privateKey = $creds['private_key'] ?? '';
        $merchantId = $creds['merchant_id'] ?? '';

        $params = [
            'version'      => 1,
            'cmd'          => 'create_transaction',
            'key'          => $publicKey,
            'format'       => 'json',
            'amount'       => number_format((float) $topup->payable_amount, 8, '.', ''),
            'currency1'    => strtoupper($topup->pay_currency ?? 'USD'),
            'currency2'    => strtoupper($topup->pay_currency ?? 'USD'),
            'buyer_email'  => $topup->user->email ?? '',
            'item_name'    => 'Coin Top-up',
            'item_number'  => $topup->reference,
            'ipn_url'      => route('ipn.Coinpayments'),
            'success_url'  => route('payment.return', ['gateway' => 'Coinpayments']) . '?reference=' . $topup->reference,
            'cancel_url'   => route('payment.cancel', ['gateway' => 'Coinpayments']) . '?reference=' . $topup->reference,
        ];

        $postString = http_build_query($params);
        $hmac       = hash_hmac('sha512', $postString, $privateKey);

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postString,
            CURLOPT_HTTPHEADER     => [
                "HMAC: {$hmac}",
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($body, true);

        if (($result['error'] ?? '') !== 'ok' || empty($result['result']['checkout_url'])) {
            throw new \RuntimeException('CoinPayments: ' . ($result['error'] ?? 'failed to create transaction.'));
        }

        $topup->update([
            'gateway_response' => $result['result']['txn_id'],
            'crypto_address'   => $result['result']['address'] ?? null,
        ]);

        return $result['result']['checkout_url'];
    }

    public static function verifyIpn(array $post, string $rawBody, string $ipnSecret, string $hmacHeader): bool
    {
        $expected = hash_hmac('sha512', $rawBody, $ipnSecret);

        return hash_equals($expected, $hmacHeader)
            && ($post['status'] ?? -1) >= 100
            && ($post['status'] ?? -1) !== 2;
    }
}
