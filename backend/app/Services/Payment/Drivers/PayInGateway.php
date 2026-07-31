<?php

namespace App\Services\Payment\Drivers;

use App\Models\CoinTopup;

class PayInGateway
{
    private const BASE = 'https://api.payin.co.tz/api/v1';

    /**
     * Initiate a top-up using PayIn invoice (C2B).
     * Returns the pay_url for redirect — no phone/operator needed upfront.
     */
    public static function initiate(CoinTopup $topup, array $creds): string
    {
        $ch = curl_init(self::BASE . '/invoice');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-API-Key: '    . ($creds['api_key']    ?? ''),
                'X-API-Secret: ' . ($creds['api_secret'] ?? ''),
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'amount'      => (float) $topup->amount,
                'reference'   => $topup->reference,
                'description' => 'Coin top-up #' . $topup->reference,
                'currency'    => 'TZS',
                'expires_in'  => 60, // 60 minutes to pay
            ]),
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno) {
            throw new \RuntimeException("PayIn cURL error: {$errno}");
        }

        $data = json_decode($body, true) ?? [];

        if (empty($data['success']) || empty($data['pay_url'])) {
            $msg = $data['message'] ?? 'Unknown PayIn error';
            throw new \RuntimeException("PayIn invoice failed: {$msg}");
        }

        return $data['pay_url'];
    }

    /**
     * Verify a transaction status via the PayIn status API.
     */
    public static function verifyStatus(string $requestRef, array $creds): array
    {
        $ch = curl_init(self::BASE . '/status/' . $requestRef);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'X-API-Key: '    . ($creds['api_key']    ?? ''),
                'X-API-Secret: ' . ($creds['api_secret'] ?? ''),
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno) {
            throw new \RuntimeException("PayIn status cURL error: {$errno}");
        }

        return json_decode($body, true) ?? [];
    }
}
