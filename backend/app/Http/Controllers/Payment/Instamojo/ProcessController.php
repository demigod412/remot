<?php

namespace App\Http\Controllers\Payment\Instamojo;

use App\Http\Controllers\Payment\BaseProcessController;
use App\Services\Payment\Drivers\InstamojoGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessController extends BaseProcessController
{
    public function returnPay(Request $request)
    {
        $reference        = $request->query('reference', '');
        $paymentId        = $request->query('payment_id', '');
        $paymentRequestId = $request->query('payment_request_id', '');
        $paymentStatus    = $request->query('payment_status', '');

        $topup = $this->findTopup($reference);
        $creds = $topup->channel->credentials ?? [];

        if ($paymentStatus !== 'Credit') {
            return $this->fail($topup, $paymentStatus);
        }

        try {
            $testing = ($creds['environment'] ?? 'testing') !== 'production';
            if (InstamojoGateway::verifyPayment($paymentId, $paymentRequestId, $creds['api_key'] ?? '', $creds['auth_token'] ?? '', $testing)) {
                return $this->complete($topup, $paymentId);
            }
        } catch (\Throwable $e) {
            Log::error('Instamojo verify', ['error' => $e->getMessage()]);
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
        $post  = $request->all();
        $creds = \App\Models\PaymentChannel::where('driver', 'like', '%instamojo%')->first()?->credentials ?? [];

        if (($post['status'] ?? '') !== 'Credit') {
            return $this->ipnOk();
        }

        if (! InstamojoGateway::verifyWebhook($post, $creds['salt'] ?? '')) {
            return response('Unauthorized', 401);
        }

        $reference        = $post['merchant_ref'] ?? '';
        $paymentId        = $post['payment_id'] ?? '';
        $paymentRequestId = $post['payment_request_id'] ?? '';
        $testing          = ($creds['environment'] ?? 'testing') !== 'production';

        $topup = \App\Models\CoinTopup::where('reference', $reference)->first();

        if ($topup && InstamojoGateway::verifyPayment($paymentId, $paymentRequestId, $creds['api_key'] ?? '', $creds['auth_token'] ?? '', $testing)) {
            \App\Services\Payment\PaymentService::completTopup($topup, $paymentId);
        }

        return $this->ipnOk();
    }
}
