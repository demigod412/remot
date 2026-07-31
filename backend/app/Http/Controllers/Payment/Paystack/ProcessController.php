<?php

namespace App\Http\Controllers\Payment\Paystack;

use App\Http\Controllers\Payment\BaseProcessController;
use App\Services\Payment\Drivers\PaystackGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessController extends BaseProcessController
{
    public function returnPay(Request $request)
    {
        $reference = $request->query('reference', '');
        $topup     = $this->findTopup($reference);
        $creds     = $topup->channel->credentials ?? [];

        try {
            if (PaystackGateway::verify($reference, $creds['secret_key'] ?? '', $topup)) {
                return $this->complete($topup, $reference);
            }
        } catch (\Throwable $e) {
            Log::error('Paystack verify', ['error' => $e->getMessage()]);
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
        $payload   = $request->getContent();
        $signature = $request->header('x-paystack-signature', '');

        $channel = \App\Models\PaymentChannel::where('driver', 'like', '%paystack%')->first();
        if (! $channel) {
            return $this->ipnOk();
        }

        $secretKey = $channel->credentials['secret_key'] ?? '';

        if (! PaystackGateway::verifyWebhook($payload, $signature, $secretKey)) {
            return response('Unauthorized', 401);
        }

        $event = json_decode($payload, true);

        if (($event['event'] ?? '') === 'charge.success') {
            $reference = $event['data']['reference'] ?? '';
            $topup     = \App\Models\CoinTopup::where('reference', $reference)->first();

            if ($topup && PaystackGateway::verify($reference, $secretKey, $topup)) {
                \App\Services\Payment\PaymentService::completTopup($topup, $reference);
            }
        }

        return $this->ipnOk();
    }
}
