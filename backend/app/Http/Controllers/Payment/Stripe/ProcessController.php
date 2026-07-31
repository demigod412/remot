<?php

namespace App\Http\Controllers\Payment\Stripe;

use App\Http\Controllers\Payment\BaseProcessController;
use App\Services\Payment\Drivers\StripeGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessController extends BaseProcessController
{
    /** User returns from Stripe Checkout. */
    public function returnPay(Request $request)
    {
        $topup     = $this->findTopup($request->query('reference', ''));
        $sessionId = $request->query('session_id', $topup->gateway_response ?? '');
        $creds     = $topup->channel->credentials ?? [];

        try {
            if (StripeGateway::verify($sessionId, $creds, $topup)) {
                return $this->complete($topup, $sessionId);
            }
        } catch (\Throwable $e) {
            Log::error('Stripe return verify', ['error' => $e->getMessage()]);
        }

        return $this->fail($topup);
    }

    /** User cancelled on Stripe. */
    public function cancel(Request $request)
    {
        $topup = $this->findTopup($request->query('reference', ''));
        return $this->fail($topup, 'cancelled');
    }

    /** Stripe webhook. */
    public function ipn(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature', '');

        // Find a Stripe channel to get credentials
        $channel = \App\Models\PaymentChannel::where('driver', 'like', '%stripe%')
            ->orWhere('code', 'like', '%stripe%')
            ->first();

        if (! $channel) {
            return $this->ipnOk();
        }

        $creds = $channel->credentials ?? [];

        try {
            $event = StripeGateway::verifyWebhook($payload, $sigHeader, $creds['webhook_secret'] ?? '');
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook invalid', ['error' => $e->getMessage()]);
            return response('Webhook Error', 400);
        }

        if ($event['type'] === 'checkout.session.completed') {
            $session   = $event['data']['object'];
            $reference = $session['client_reference_id'] ?? '';

            $topup = \App\Models\CoinTopup::where('reference', $reference)->first();
            if ($topup && $session['payment_status'] === 'paid') {
                \App\Services\Payment\PaymentService::completTopup($topup, $session['id']);
            }
        }

        return $this->ipnOk();
    }
}
