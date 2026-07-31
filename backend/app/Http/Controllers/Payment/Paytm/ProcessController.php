<?php

namespace App\Http\Controllers\Payment\Paytm;

use App\Http\Controllers\Payment\BaseProcessController;
use App\Services\Payment\Drivers\PaytmGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessController extends BaseProcessController
{
    /** Paytm posts back to callback URL with transaction result. */
    public function returnPay(Request $request)
    {
        $reference = $request->query('reference', $request->input('ORDERID', ''));
        $topup     = $this->findTopup($reference);
        $creds     = $topup->channel->credentials ?? [];

        try {
            if (PaytmGateway::verifyResponse($request->all(), $creds['merchant_key'] ?? '')) {
                return $this->complete($topup, $request->input('TXNID', ''));
            }
        } catch (\Throwable $e) {
            Log::error('Paytm verify', ['error' => $e->getMessage()]);
        }

        return $this->fail($topup, $request->input('RESPMSG', ''));
    }

    public function cancel(Request $request)
    {
        $topup = $this->findTopup($request->query('reference', ''));
        return $this->fail($topup, 'cancelled');
    }

    public function ipn(Request $request)
    {
        // Paytm uses callback URL — same as returnPay, handled there.
        return $this->ipnOk();
    }
}
