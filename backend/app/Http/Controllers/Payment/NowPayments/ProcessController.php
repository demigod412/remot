<?php

namespace App\Http\Controllers\Payment\NowPayments;

use App\Http\Controllers\Payment\BaseProcessController;
use App\Models\CoinTopup;
use App\Models\PaymentChannel;
use App\Services\Payment\Drivers\NowPaymentsGateway;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * NOWPayments return / cancel / IPN handling.
 *
 * Shaped exactly like the other gateway ProcessControllers in this folder: one
 * class with returnPay(), cancel() and ipn(), extending BaseProcessController.
 */
class ProcessController extends BaseProcessController
{
    public function returnPay(Request $request)
    {
        $topup = $this->findTopup($request->query('reference', ''));

        if ($topup->status === 1) {
            return redirect()->route('user.wallet.overview')
                ->with('success', 'Payment confirmed. Your coins have been credited.');
        }

        return redirect()->route('user.wallet.overview')
            ->with('info', 'Payment received. Coins will be credited once the network confirms it.');
    }

    public function cancel(Request $request)
    {
        $topup = $this->findTopup($request->query('reference', ''));

        return $this->fail($topup, 'cancelled');
    }

    public function ipn(Request $request)
    {
        $payload   = $request->getContent();
        $signature = $request->header('x-nowpayments-sig', '');

        $channel = PaymentChannel::where('driver', 'like', '%nowpayments%')->first();

        if (! $channel) {
            return $this->ipnOk();
        }

        try {
            $event = NowPaymentsGateway::verifyWebhook(
                $payload,
                $signature,
                $channel->credentials['ipn_secret'] ?? ''
            );
        } catch (\Throwable $e) {
            Log::warning('NowPayments webhook rejected', ['error' => $e->getMessage()]);
            return response('Unauthorized', 401);
        }

        if (! NowPaymentsGateway::isPaymentComplete($event)) {
            // Intermediate states (waiting, confirming, partially_paid) are
            // acknowledged but must not credit coins.
            return $this->ipnOk();
        }

        $reference = NowPaymentsGateway::referenceFrom($event);
        $topup     = CoinTopup::where('reference', $reference)->first();

        if ($topup && $topup->status !== 1) {
            PaymentService::completTopup($topup, NowPaymentsGateway::transactionIdFrom($event));
        }

        return $this->ipnOk();
    }
}
