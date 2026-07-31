<?php

namespace App\Http\Controllers\Payment\CoinPayments;

use App\Http\Controllers\Payment\BaseProcessController;
use App\Services\Payment\Drivers\CoinPaymentsGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessController extends BaseProcessController
{
    public function returnPay(Request $request)
    {
        $reference = $request->query('reference', '');
        $topup     = $this->findTopup($reference);

        // CoinPayments confirms via IPN; redirect with info message
        if ($topup->status === 1) {
            return redirect()->route('user.wallet.overview')
                ->with('success', 'Payment confirmed! Your coins have been credited.');
        }

        return redirect()->route('user.wallet.overview')
            ->with('info', 'Payment received. Coins will be credited once the blockchain confirms it.');
    }

    public function cancel(Request $request)
    {
        $topup = $this->findTopup($request->query('reference', ''));
        return $this->fail($topup, 'cancelled');
    }

    public function ipn(Request $request)
    {
        $rawBody    = $request->getContent();
        $hmacHeader = $request->header('HMAC', '');
        $post       = $request->all();

        $channel = \App\Models\PaymentChannel::where('driver', 'like', '%coinpayments%')->first();
        if (! $channel) {
            return $this->ipnOk();
        }

        $creds = $channel->credentials ?? [];

        try {
            $valid = CoinPaymentsGateway::verifyIpn($post, $rawBody, $creds['ipn_secret'] ?? '', $hmacHeader);
        } catch (\Throwable $e) {
            Log::error('CoinPayments IPN', ['error' => $e->getMessage()]);
            return $this->ipnOk();
        }

        if (! $valid) {
            return $this->ipnOk();
        }

        $reference = $post['order_id'] ?? $post['item_number'] ?? '';
        $topup     = \App\Models\CoinTopup::where('reference', $reference)->first();

        if ($topup) {
            \App\Services\Payment\PaymentService::completTopup($topup, $post['txn_id'] ?? '');
        }

        return $this->ipnOk();
    }
}
