<?php

namespace App\Services\Payment\Drivers;

use App\Models\CoinTopup;

/**
 * PayPal Legacy (HTML Button / IPN).
 * Returns the PayPal payment URL as a self-submitting form target.
 * Since PayPal legacy requires a form POST, we return a special URL
 * prefixed with "FORM:" — the controller will render a redirect view.
 */
class PaypalGateway
{
    public static function initiate(CoinTopup $topup, array $creds): string
    {
        // Signal to the controller that this needs a form POST redirect
        return 'FORM:' . json_encode([
            'action' => 'https://www.paypal.com/cgi-bin/webscr',
            'method' => 'POST',
            'fields'  => [
                'cmd'           => '_xclick',
                'business'      => $creds['email'] ?? '',
                'item_name'     => 'Coin Top-up',
                'item_number'   => $topup->reference,
                'amount'        => number_format((float) $topup->payable_amount, 2, '.', ''),
                'currency_code' => strtoupper($topup->pay_currency ?? 'USD'),
                'return'        => route('payment.return', ['gateway' => 'Paypal']) . '?reference=' . $topup->reference,
                'cancel_return' => route('payment.cancel', ['gateway' => 'Paypal']) . '?reference=' . $topup->reference,
                'notify_url'    => route('ipn.Paypal'),
                'no_note'       => 1,
                'no_shipping'   => 1,
                'charset'       => 'UTF-8',
            ],
        ]);
    }

    /**
     * Verify PayPal IPN by posting back to PayPal.
     */
    public static function verifyIpn(string $rawPost): bool
    {
        $ch = curl_init('https://www.paypal.com/cgi-bin/webscr');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => 'cmd=_notify-validate&' . $rawPost,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response === 'VERIFIED';
    }
}
