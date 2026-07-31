<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\CoinTopup;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

abstract class BaseProcessController extends Controller
{
    /**
     * Look up the topup by reference. Fails with 404 if not found.
     */
    protected function findTopup(string $reference): CoinTopup
    {
        return CoinTopup::with(['user', 'channel', 'package'])
            ->where('reference', $reference)
            ->firstOrFail();
    }

    /**
     * Credit user + update topup status and redirect to success.
     */
    protected function complete(CoinTopup $topup, string $gatewayRef = '')
    {
        try {
            PaymentService::completTopup($topup, $gatewayRef);
        } catch (\Throwable $e) {
            Log::error('Payment complete failed', [
                'topup_id' => $topup->id,
                'error'    => $e->getMessage(),
            ]);
        }

        return redirect()->route('user.wallet.overview')
            ->with('success', 'Payment successful! Your coins have been credited.');
    }

    /**
     * Mark topup failed and redirect.
     */
    protected function fail(CoinTopup $topup, string $reason = '')
    {
        PaymentService::failTopup($topup);

        return redirect()->route('user.wallet.overview')
            ->with('error', 'Payment failed or was cancelled.' . ($reason ? " ({$reason})" : ''));
    }

    /**
     * Return 200 OK for IPN endpoints (tells gateway delivery was received).
     */
    protected function ipnOk(): \Illuminate\Http\Response
    {
        return response('OK', 200);
    }
}
