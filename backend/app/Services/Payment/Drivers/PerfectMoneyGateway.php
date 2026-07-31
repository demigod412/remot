<?php

namespace App\Services\Payment\Drivers;

use App\Models\CoinTopup;

/**
 * PerfectMoney — form POST redirect via intermediate view.
 */
class PerfectMoneyGateway
{
    public static function initiate(CoinTopup $topup, array $creds): string
    {
        $accountId   = $creds['account_id'] ?? '';
        $accountName = $creds['account_name'] ?? config('app.name');

        return 'FORM:' . json_encode([
            'action' => 'https://perfectmoney.com/api/step1.asp',
            'method' => 'POST',
            'fields' => [
                'PAYEE_ACCOUNT'    => $accountId,
                'PAYEE_NAME'       => $accountName,
                'PAYMENT_AMOUNT'   => number_format((float) $topup->payable_amount, 2, '.', ''),
                'PAYMENT_UNITS'    => strtoupper($topup->pay_currency ?? 'USD'),
                'PAYMENT_ID'       => $topup->reference,
                'STATUS_URL'       => route('ipn.PerfectMoney'),
                'PAYMENT_URL'      => route('payment.return', ['gateway' => 'PerfectMoney']) . '?reference=' . $topup->reference,
                'NOPAYMENT_URL'    => route('payment.cancel', ['gateway' => 'PerfectMoney']) . '?reference=' . $topup->reference,
                'PAYMENT_URL_METHOD'   => 'GET',
                'NOPAYMENT_URL_METHOD' => 'GET',
            ],
        ]);
    }

    /**
     * Verify PerfectMoney IPN using V2_HASH.
     */
    public static function verifyIpn(array $post, string $altPassphrase): bool
    {
        $hash = strtoupper(md5(implode(':', [
            $post['PAYMENT_ID']       ?? '',
            $post['PAYEE_ACCOUNT']    ?? '',
            $post['PAYMENT_AMOUNT']   ?? '',
            $post['PAYMENT_UNITS']    ?? '',
            $post['PAYMENT_BATCH_NUM'] ?? '',
            $post['PAYER_ACCOUNT']    ?? '',
            strtoupper(md5($altPassphrase)),
            $post['TIMESTAMPGMT']     ?? '',
        ])));

        return $hash === ($post['V2_HASH'] ?? '');
    }
}
