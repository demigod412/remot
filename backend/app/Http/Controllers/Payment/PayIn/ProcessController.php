<?php

namespace App\Http\Controllers\Payment\PayIn;

use App\Http\Controllers\Controller;
use App\Models\Cashout;
use App\Models\CoinTopup;
use App\Models\PaymentChannel;
use App\Models\PayoutMethod;
use App\Services\Payment\Drivers\PayInGateway;
use App\Services\Payment\PaymentService;
use App\Services\Payout\PayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessController extends Controller
{
    /**
     * Handle webhook from PayIn for both collection (top-up) and disbursement (cashout).
     * PayIn sends POST to this URL when a transaction completes, fails, or is reversed.
     *
     * Payload includes: request_ref, type (collection|disbursement), status, reference
     */
    public function ipn(Request $request)
    {
        $payload = $request->all();

        $requestRef = $payload['request_ref'] ?? '';
        $type       = $payload['type']        ?? '';
        $status     = $payload['status']      ?? '';
        $reference  = $payload['reference']   ?? '';

        if (! $requestRef || ! $type) {
            return response('Bad Request', 400);
        }

        Log::info('PayIn IPN received', ['request_ref' => $requestRef, 'type' => $type, 'status' => $status]);

        try {
            if ($type === 'collection' || $type === 'manual_c2b') {
                $this->handleCollection($requestRef, $reference, $status);
            } elseif ($type === 'disbursement') {
                $this->handleDisbursement($requestRef, $status);
            }
        } catch (\Throwable $e) {
            Log::error('PayIn IPN processing error', ['error' => $e->getMessage(), 'payload' => $payload]);
        }

        return response('OK', 200);
    }

    private function handleCollection(string $requestRef, string $reference, string $callbackStatus): void
    {
        $topup = CoinTopup::where('reference', $reference)->first();
        if (! $topup) {
            Log::warning('PayIn IPN: CoinTopup not found', ['reference' => $reference]);
            return;
        }

        $creds = $this->getCollectionCreds();
        if (! $creds) {
            Log::error('PayIn IPN: no active PayIn payment channel found');
            return;
        }

        // Always verify with PayIn API — never trust callback data alone
        $verified = PayInGateway::verifyStatus($requestRef, $creds);

        if (($verified['status'] ?? '') === 'completed') {
            PaymentService::completTopup($topup, $requestRef);
        } elseif (in_array($verified['status'] ?? '', ['failed', 'reversed'])) {
            PaymentService::failTopup($topup);
        }
    }

    private function handleDisbursement(string $requestRef, string $callbackStatus): void
    {
        $cashout = Cashout::with(['user', 'payoutMethod'])
            ->where('gateway_reference', $requestRef)
            ->first();

        if (! $cashout) {
            Log::warning('PayIn IPN: Cashout not found for gateway_reference', ['request_ref' => $requestRef]);
            return;
        }

        $method = $cashout->payoutMethod;
        $creds  = is_string($method?->credentials)
            ? (json_decode($method->credentials, true) ?? [])
            : ($method?->credentials ?? []);

        // Verify with PayIn API
        $verified = PayInGateway::verifyStatus($requestRef, $creds);
        $verifiedStatus = $verified['status'] ?? '';

        if ($verifiedStatus === 'completed') {
            PayoutService::completeDisbursement($cashout, $requestRef);
        } elseif (in_array($verifiedStatus, ['failed', 'reversed'])) {
            PayoutService::failDisbursement($cashout, $verified['error'] ?? 'Payment failed.');
        }
    }

    private function getCollectionCreds(): ?array
    {
        $channel = PaymentChannel::where('driver', 'like', '%payin%')
            ->where('status', 1)
            ->first();

        if (! $channel) {
            return null;
        }

        return is_string($channel->credentials)
            ? (json_decode($channel->credentials, true) ?? [])
            : ($channel->credentials ?? []);
    }
}
