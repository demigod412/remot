<?php

namespace App\Services\Payment\Drivers;

use App\Models\CoinTopup;

/**
 * Paytm — uses a form POST redirect.
 * Returns "FORM:" + JSON for the redirect view.
 */
class PaytmGateway
{
    public static function initiate(CoinTopup $topup, array $creds): string
    {
        $merchantId  = $creds['merchant_id'] ?? '';
        $merchantKey = $creds['merchant_key'] ?? '';
        $channelId   = $creds['channel_id'] ?? 'WEB';
        $website     = $creds['website'] ?? 'DEFAULT';
        $isTesting   = ($creds['environment'] ?? 'testing') !== 'production';

        $params = [
            'MID'            => $merchantId,
            'ORDER_ID'       => $topup->reference,
            'TXN_AMOUNT'     => number_format((float) $topup->payable_amount, 2, '.', ''),
            'CUST_ID'        => (string) $topup->user_id,
            'EMAIL'          => $topup->user->email ?? '',
            'MOBILE_NO'      => $topup->user->mobile ?? '',
            'CHANNEL_ID'     => $channelId,
            'WEBSITE'        => $website,
            'INDUSTRY_TYPE_ID' => 'Retail',
            'CALLBACK_URL'   => route('payment.return', ['gateway' => 'Paytm']) . '?reference=' . $topup->reference,
        ];

        $checksum = static::generateChecksum($params, $merchantKey);
        $params['CHECKSUMHASH'] = $checksum;

        $action = $isTesting
            ? 'https://securegw-stage.paytm.in/order/process'
            : 'https://securegw.paytm.in/order/process';

        return 'FORM:' . json_encode([
            'action' => $action,
            'method' => 'POST',
            'fields' => $params,
        ]);
    }

    public static function verifyResponse(array $params, string $merchantKey): bool
    {
        $checksum = $params['CHECKSUMHASH'] ?? '';
        unset($params['CHECKSUMHASH']);

        return static::verifyChecksum($params, $merchantKey, $checksum)
            && ($params['STATUS'] ?? '') === 'TXN_SUCCESS';
    }

    private static function generateChecksum(array $params, string $key): string
    {
        ksort($params);
        $str = implode('|', array_values($params)) . '|';
        $salt = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4);

        return base64_encode(hash_hmac('sha256', $str . $salt, $key, true)) . $salt;
    }

    private static function verifyChecksum(array $params, string $key, string $checksum): bool
    {
        ksort($params);
        $str  = implode('|', array_values($params)) . '|';
        $salt = substr($checksum, -4);
        $expected = base64_encode(hash_hmac('sha256', $str . $salt, $key, true)) . $salt;

        return hash_equals($expected, $checksum);
    }
}
