<?php

namespace App\Http\Controllers\Payment\Razorpay;

use App\Http\Controllers\Payment\BaseProcessController;
use App\Services\Payment\Drivers\RazorpayGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessController extends BaseProcessController
{
    /** Razorpay posts payment details to the callback URL. */
    public function returnPay(Request $request)
    {
        $reference = $request->query('reference', '');
        $topup     = $this->findTopup($reference);
        $creds     = $topup->channel->credentials ?? [];

        $orderId   = $request->input('razorpay_order_id', $topup->gateway_response ?? '');
        $paymentId = $request->input('razorpay_payment_id', '');
        $signature = $request->input('razorpay_signature', '');

        try {
            if (RazorpayGateway::verifySignature($orderId, $paymentId, $signature, $creds['key_secret'] ?? '')) {
                return $this->complete($topup, $paymentId);
            }
        } catch (\Throwable $e) {
            Log::error('Razorpay signature verify', ['error' => $e->getMessage()]);
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
        // Razorpay webhook
        $payload   = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature', '');

        $channel = \App\Models\PaymentChannel::where('driver', 'like', '%razorpay%')->first();
        if (! $channel) {
            return $this->ipnOk();
        }

        $webhookSecret = $channel->credentials['webhook_secret'] ?? '';
        $expected      = hash_hmac('sha256', $payload, $webhookSecret);

        if (! hash_equals($expected, $signature)) {
            return response('Unauthorized', 401);
        }

        $event = json_decode($payload, true);

        if (($event['event'] ?? '') === 'payment.captured') {
            $notes     = $event['payload']['payment']['entity']['notes'] ?? [];
            $reference = $notes['reference'] ?? $event['payload']['order']['entity']['receipt'] ?? '';
            $topup     = \App\Models\CoinTopup::where('reference', $reference)->first();

            if ($topup) {
                \App\Services\Payment\PaymentService::completTopup($topup, $event['payload']['payment']['entity']['id'] ?? '');
            }
        }

        return $this->ipnOk();
    }
}
