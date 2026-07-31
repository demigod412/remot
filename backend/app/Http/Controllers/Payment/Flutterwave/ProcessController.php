<?php

namespace App\Http\Controllers\Payment\Flutterwave;

use App\Http\Controllers\Payment\BaseProcessController;
use App\Services\Payment\Drivers\FlutterwaveGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessController extends BaseProcessController
{
    public function returnPay(Request $request)
    {
        $reference     = $request->query('reference', $request->query('tx_ref', ''));
        $status        = $request->query('status', '');
        $transactionId = $request->query('transaction_id', '');

        $topup = $this->findTopup($reference);
        $creds = $topup->channel->credentials ?? [];

        if ($status !== 'successful') {
            return $this->fail($topup, $status);
        }

        try {
            if (FlutterwaveGateway::verify($transactionId, $creds['secret_key'] ?? '', $topup)) {
                return $this->complete($topup, $transactionId);
            }
        } catch (\Throwable $e) {
            Log::error('Flutterwave verify', ['error' => $e->getMessage()]);
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
        $channel = \App\Models\PaymentChannel::where('driver', 'like', '%flutterwave%')->first();
        $secretHash = $channel->credentials['secret_hash'] ?? '';
        $headerHash = $request->header('verif-hash', '');

        if (! FlutterwaveGateway::verifyWebhook($headerHash, $secretHash)) {
            return response('Unauthorized', 401);
        }

        $event = json_decode($request->getContent(), true);

        if (($event['event'] ?? '') === 'charge.completed' && ($event['data']['status'] ?? '') === 'successful') {
            $reference     = $event['data']['tx_ref'] ?? '';
            $transactionId = (string) ($event['data']['id'] ?? '');
            $creds         = $channel->credentials ?? [];

            $topup = \App\Models\CoinTopup::where('reference', $reference)->first();

            if ($topup && FlutterwaveGateway::verify($transactionId, $creds['secret_key'] ?? '', $topup)) {
                \App\Services\Payment\PaymentService::completTopup($topup, $transactionId);
            }
        }

        return $this->ipnOk();
    }
}
