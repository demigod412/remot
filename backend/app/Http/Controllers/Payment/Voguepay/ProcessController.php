<?php

namespace App\Http\Controllers\Payment\Voguepay;

use App\Http\Controllers\Payment\BaseProcessController;
use App\Services\Payment\Drivers\VoguepayGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessController extends BaseProcessController
{
    public function returnPay(Request $request)
    {
        $reference     = $request->query('reference', $request->query('merchant_ref', ''));
        $transactionId = $request->query('transaction_id', '');
        $status        = $request->query('status', '');

        $topup = $this->findTopup($reference);

        if ($topup->status === 1) {
            return redirect()->route('user.wallet.overview')
                ->with('success', 'Payment confirmed! Your coins have been credited.');
        }

        if ($status === 'Approved' && $transactionId) {
            $creds      = $topup->channel->credentials ?? [];
            $merchantId = $creds['merchant_id'] ?? '';

            try {
                if (VoguepayGateway::queryTransaction($transactionId, $merchantId)) {
                    return $this->complete($topup, $transactionId);
                }
            } catch (\Throwable $e) {
                Log::error('Voguepay query', ['error' => $e->getMessage()]);
            }
        }

        return $this->fail($topup, $status);
    }

    public function cancel(Request $request)
    {
        $reference = $request->query('reference', $request->query('merchant_ref', ''));
        $topup     = $this->findTopup($reference);
        return $this->fail($topup, 'cancelled');
    }

    public function ipn(Request $request)
    {
        $post          = $request->all();
        $transactionId = $post['transaction_id'] ?? '';
        $reference     = $post['merchant_ref'] ?? '';

        $topup = \App\Models\CoinTopup::where('reference', $reference)->first();
        if (! $topup) {
            return $this->ipnOk();
        }

        $creds      = $topup->channel->credentials ?? [];
        $merchantId = $creds['merchant_id'] ?? '';

        try {
            if (VoguepayGateway::queryTransaction($transactionId, $merchantId)) {
                \App\Services\Payment\PaymentService::completTopup($topup, $transactionId);
            }
        } catch (\Throwable $e) {
            Log::error('Voguepay IPN', ['error' => $e->getMessage()]);
        }

        return $this->ipnOk();
    }
}
