<?php

namespace App\Http\Controllers\Payment\PaypalSdk;

use App\Http\Controllers\Payment\BaseProcessController;
use App\Services\Payment\Drivers\PaypalSdkGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessController extends BaseProcessController
{
    public function returnPay(Request $request)
    {
        $topup   = $this->findTopup($request->query('reference', ''));
        $orderId = $request->query('token', $topup->gateway_response ?? '');
        $creds   = $topup->channel->credentials ?? [];

        try {
            if (PaypalSdkGateway::captureOrder($orderId, $creds)) {
                return $this->complete($topup, $orderId);
            }
        } catch (\Throwable $e) {
            Log::error('PaypalSdk capture', ['error' => $e->getMessage()]);
        }

        return $this->fail($topup);
    }

    public function cancel(Request $request)
    {
        $topup = $this->findTopup($request->query('reference', ''));
        return $this->fail($topup, 'cancelled');
    }

    public function ipn(Request $request)
    {
        // PayPal REST webhooks — verify and process PAYMENT.CAPTURE.COMPLETED
        $payload = json_decode($request->getContent(), true);

        if (($payload['event_type'] ?? '') === 'PAYMENT.CAPTURE.COMPLETED') {
            $orderId  = $payload['resource']['supplementary_data']['related_ids']['order_id'] ?? '';
            $reference = $payload['resource']['purchase_units'][0]['reference_id']
                ?? $payload['resource']['custom_id']
                ?? '';

            if ($reference) {
                $topup = \App\Models\CoinTopup::where('reference', $reference)->first();
                if ($topup) {
                    \App\Services\Payment\PaymentService::completTopup($topup, $orderId);
                }
            }
        }

        return $this->ipnOk();
    }
}
