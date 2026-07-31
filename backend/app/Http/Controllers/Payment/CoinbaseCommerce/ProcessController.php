<?php

namespace App\Http\Controllers\Payment\CoinbaseCommerce;

use App\Http\Controllers\Payment\BaseProcessController;
use App\Services\Payment\Drivers\CoinbaseCommerceGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessController extends BaseProcessController
{
    public function returnPay(Request $request)
    {
        $reference = $request->query('reference', '');
        $topup     = $this->findTopup($reference);

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
        $payload   = $request->getContent();
        $signature = $request->header('X-CC-Webhook-Signature', '');

        $channel = \App\Models\PaymentChannel::where('driver', 'like', '%coinbasecommerce%')->first();
        if (! $channel) {
            return $this->ipnOk();
        }

        try {
            $event = CoinbaseCommerceGateway::verifyWebhook($payload, $signature, $channel->credentials['webhook_secret'] ?? '');
        } catch (\Throwable $e) {
            Log::warning('CoinbaseCommerce webhook', ['error' => $e->getMessage()]);
            return response('Unauthorized', 401);
        }

        if (CoinbaseCommerceGateway::isPaymentComplete($event)) {
            $meta      = $event['event']['data']['metadata'] ?? [];
            $reference = $meta['topup_reference'] ?? '';
            $chargeCode= $event['event']['data']['code'] ?? '';

            $topup = \App\Models\CoinTopup::where('reference', $reference)->first();
            if ($topup) {
                \App\Services\Payment\PaymentService::completTopup($topup, $chargeCode);
            }
        }

        return $this->ipnOk();
    }
}
