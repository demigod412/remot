<?php

namespace App\Http\Controllers\Payment\PerfectMoney;

use App\Http\Controllers\Payment\BaseProcessController;
use App\Services\Payment\Drivers\PerfectMoneyGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessController extends BaseProcessController
{
    public function returnPay(Request $request)
    {
        $reference = $request->query('reference', $request->query('PAYMENT_ID', ''));
        $topup     = $this->findTopup($reference);

        // PerfectMoney confirms via IPN — if already credited, show success
        if ($topup->status === 1) {
            return redirect()->route('user.wallet.overview')
                ->with('success', 'Payment confirmed! Your coins have been credited.');
        }

        return redirect()->route('user.wallet.overview')
            ->with('info', 'Payment received. Coins will be credited after confirmation.');
    }

    public function cancel(Request $request)
    {
        $reference = $request->query('reference', $request->query('PAYMENT_ID', ''));
        $topup     = $this->findTopup($reference);
        return $this->fail($topup, 'cancelled');
    }

    public function ipn(Request $request)
    {
        $post  = $request->all();
        $creds = \App\Models\PaymentChannel::where('driver', 'like', '%perfectmoney%')->first()?->credentials ?? [];

        if (! PerfectMoneyGateway::verifyIpn($post, $creds['alt_passphrase'] ?? '')) {
            Log::warning('PerfectMoney IPN hash mismatch');
            return $this->ipnOk();
        }

        $reference = $post['PAYMENT_ID'] ?? '';
        $topup     = \App\Models\CoinTopup::where('reference', $reference)->first();

        if ($topup) {
            \App\Services\Payment\PaymentService::completTopup($topup, $post['PAYMENT_BATCH_NUM'] ?? '');
        }

        return $this->ipnOk();
    }
}
