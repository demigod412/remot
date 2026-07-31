<?php

namespace App\Services\Payout\Drivers;

use App\Models\Cashout;

class PayInDriver
{
    private const BASE = 'https://api.payin.co.tz/api/v1';

    /**
     * Send money to a user's mobile wallet via PayIn disbursement API.
     * Returns the gateway request_ref on success.
     * Throws on failure.
     *
     * Expected payout_details on the cashout:
     *   { "phone": "255712345678", "operator": "mpesa" }
     */
    public static function disburse(Cashout $cashout, array $creds): string
    {
        $details  = $cashout->payout_details ?? [];
        $phone    = $details['phone']    ?? '';
        $operator = $details['operator'] ?? '';

        if (! $phone || ! $operator) {
            throw new \RuntimeException('PayIn disbursement requires phone and operator in payout_details.');
        }

        $ch = curl_init(self::BASE . '/disbursement');
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
                'phone'       => $phone,
                'amount'      => (int) $cashout->payout_amount,
                'operator'    => $operator,
                'reference'   => $cashout->reference,
                'description' => 'Cashout payout #' . $cashout->reference,
                'currency'    => 'TZS',
            ]),
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $body     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno    = curl_errno($ch);
        curl_close($ch);

        if ($errno) {
            throw new \RuntimeException("PayIn disbursement cURL error: {$errno}");
        }

        $data = json_decode($body, true) ?? [];

        if ($httpCode !== 201 || empty($data['success'])) {
            $msg = $data['message'] ?? 'Unknown PayIn disbursement error';
            throw new \RuntimeException("PayIn disbursement failed: {$msg}");
        }

        return $data['request_ref'];
    }

    /**
     * Verify a disbursement status. Used by the IPN webhook handler.
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
