<?php

namespace App\Services\Payment\Drivers;

use App\Models\CoinTopup;

/**
 * Voguepay — form POST redirect.
 */
class VoguepayGateway
{
    public static function initiate(CoinTopup $topup, array $creds): string
    {
        $merchantId = $creds['merchant_id'] ?? '';
        $isDev      = ($creds['environment'] ?? 'demo') === 'demo';

        return 'FORM:' . json_encode([
            'action' => 'https://voguepay.com/pay/',
            'method' => 'POST',
            'fields' => [
                'v_merchant_id'  => $isDev ? 'demo' : $merchantId,
                'merchant_ref'   => $topup->reference,
                'notify_url'     => route('ipn.Voguepay'),
                'return_url'     => route('payment.return', ['gateway' => 'Voguepay']) . '?reference=' . $topup->reference,
                'p_email'        => $topup->user->email ?? '',
                'amount'         => number_format((float) $topup->payable_amount, 2, '.', ''),
                'currency'       => strtoupper($topup->pay_currency ?? 'USD'),
                'item_0_name'    => 'Coin Top-up',
                'item_0_quantity'=> 1,
                'item_0_unit_price' => number_format((float) $topup->payable_amount, 2, '.', ''),
                'developer_code' => '',
                'store_id'       => config('app.name'),
            ],
        ]);
    }

    /**
     * Query Voguepay transaction status.
     */
    public static function queryTransaction(string $transactionId, string $merchantId): bool
    {
        $url = "https://voguepay.com/?v_transaction_id={$transactionId}&type=json&merchant_id={$merchantId}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($body, true);

        return ($result['status'] ?? '') === 'Approved'
            && ($result['total'] ?? 0) > 0;
    }
}
