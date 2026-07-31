<?php

namespace App\Http\Controllers\Payment\Paypal;

use App\Http\Controllers\Payment\BaseProcessController;
use App\Services\Payment\Drivers\PaypalGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessController extends BaseProcessController
{
    public function returnPay(Request $request)
    {
        $reference = $request->query('reference', '');
        $topup     = $this->findTopup($reference);

        // PayPal legacy return — payment confirmed by IPN; just redirect if already credited
        if ($topup->status === 1) {
            return redirect()->route('user.wallet.overview')
                ->with('success', 'Payment successful! Your coins have been credited.');
        }

        // If status is still 0, IPN may arrive later — show pending message
        return redirect()->route('user.wallet.overview')
            ->with('info', 'Payment received. Coins will be credited once confirmed.');
    }

    public function cancel(Request $request)
    {
        $topup = $this->findTopup($request->query('reference', ''));
        return $this->fail($topup, 'cancelled');
    }

    public function ipn(Request $request)
    {
        $rawPost = $request->getContent();

        try {
            $verified = PaypalGateway::verifyIpn($rawPost);
        } catch (\Throwable $e) {
            Log::error('PayPal IPN verify error', ['error' => $e->getMessage()]);
            return $this->ipnOk();
        }

        if (! $verified) {
            return $this->ipnOk();
        }

        $post = $request->all();

        if (($post['payment_status'] ?? '') !== 'Completed') {
            return $this->ipnOk();
        }

        $reference = $post['item_number'] ?? '';
        $topup     = \App\Models\CoinTopup::where('reference', $reference)->first();

        if ($topup) {
            \App\Services\Payment\PaymentService::completTopup($topup, $post['txn_id'] ?? '');
        }

        return $this->ipnOk();
    }
}
