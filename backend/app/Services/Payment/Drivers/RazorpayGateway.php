<?php

namespace App\Services\Payment\Drivers;

use App\Models\CoinTopup;

/**
 * Razorpay — creates an order, then signals a JS checkout form render.
 * Returns "RAZORPAY:" + JSON so the controller can render the checkout JS view.
 */
class RazorpayGateway
{
    public static function initiate(CoinTopup $topup, array $creds): string
    {
        $keyId     = $creds['key_id'] ?? '';
        $keySecret = $creds['key_secret'] ?? '';

        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'amount'          => (int) round((float) $topup->payable_amount * 100),
                'currency'        => strtoupper($topup->pay_currency ?? 'INR'),
                'receipt'         => $topup->reference,
                'payment_capture' => 1,
            ]),
            CURLOPT_USERPWD       => "{$keyId}:{$keySecret}",
            CURLOPT_HTTPHEADER    => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT       => 30,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        $order = json_decode($body, true);

        if (empty($order['id'])) {
            throw new \RuntimeException('Razorpay: failed to create order. ' . ($order['error']['description'] ?? ''));
        }

        $topup->update(['gateway_response' => $order['id']]);

        return 'RAZORPAY:' . json_encode([
            'key'         => $keyId,
            'order_id'    => $order['id'],
            'amount'      => $order['amount'],
            'currency'    => $order['currency'],
            'name'        => config('app.name'),
            'description' => 'Coin Top-up',
            'reference'   => $topup->reference,
            'callback_url'=> route('payment.return', ['gateway' => 'Razorpay']) . '?reference=' . $topup->reference,
            'cancel_url'  => route('payment.cancel', ['gateway' => 'Razorpay']) . '?reference=' . $topup->reference,
        ]);
    }

    public static function verifySignature(string $orderId, string $paymentId, string $signature, string $keySecret): bool
    {
        $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, $keySecret);
        return hash_equals($expected, $signature);
    }
}
