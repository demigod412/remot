<?php

namespace App\Services\Payment\Drivers;

use App\Models\CoinTopup;

class InstamojoGateway
{
    public static function initiate(CoinTopup $topup, array $creds): string
    {
        $apiKey    = $creds['api_key'] ?? '';
        $authToken = $creds['auth_token'] ?? '';
        $isTesting = ($creds['environment'] ?? 'testing') !== 'production';

        $baseUrl = $isTesting
            ? 'https://test.instamojo.com/api/1.1'
            : 'https://www.instamojo.com/api/1.1';

        $ch = curl_init("{$baseUrl}/payment-requests/");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'purpose'        => 'Coin Top-up',
                'amount'         => number_format((float) $topup->payable_amount, 2, '.', ''),
                'buyer_name'     => ($topup->user->firstname ?? '') . ' ' . ($topup->user->lastname ?? ''),
                'email'          => $topup->user->email ?? '',
                'phone'          => $topup->user->mobile ?? '',
                'redirect_url'   => route('payment.return', ['gateway' => 'Instamojo']) . '?reference=' . $topup->reference,
                'webhook'        => route('ipn.Instamojo'),
                'allow_repeated_payments' => 'false',
                'send_email'     => 'false',
                'send_sms'       => 'false',
            ]),
            CURLOPT_HTTPHEADER => [
                "X-Api-Key: {$apiKey}",
                "X-Auth-Token: {$authToken}",
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($body, true);

        if (empty($result['payment_request']['longurl'])) {
            throw new \RuntimeException('Instamojo: failed to create payment request. ' . ($result['message'] ?? ''));
        }

        $topup->update(['gateway_response' => $result['payment_request']['id']]);

        return $result['payment_request']['longurl'];
    }

    public static function verifyPayment(string $paymentId, string $paymentRequestId, string $apiKey, string $authToken, bool $testing = false): bool
    {
        $baseUrl = $testing
            ? 'https://test.instamojo.com/api/1.1'
            : 'https://www.instamojo.com/api/1.1';

        $ch = curl_init("{$baseUrl}/payment-requests/{$paymentRequestId}/{$paymentId}/");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                "X-Api-Key: {$apiKey}",
                "X-Auth-Token: {$authToken}",
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($body, true);

        return ($result['payment']['status'] ?? '') === 'Credit';
    }

    public static function verifyWebhook(array $post, string $salt): bool
    {
        $mac = $post['mac'] ?? '';
        $data = $post['amount'] . '|' . $post['buyer_name'] . '|' . $post['email']
              . '|' . $post['fees'] . '|' . $post['longurl'] . '|' . $post['payment_id']
              . '|' . $post['payment_request_id'] . '|' . $post['purpose']
              . '|' . $post['shorturl'] . '|' . $post['status'];

        $expected = hash_hmac('sha1', $data, $salt);

        return hash_equals($expected, $mac);
    }
}
