<?php

namespace App\Http\Controllers\Payment;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Dispatches gateway return / cancel callbacks to the correct ProcessController.
 */
class GatewayCallbackController extends BaseProcessController
{
    private static array $map = [
        'stripev3'         => Stripe\ProcessController::class,
        'stripe'           => Stripe\ProcessController::class,
        'paypalsdk'        => PaypalSdk\ProcessController::class,
        'paypal'           => Paypal\ProcessController::class,
        'razorpay'         => Razorpay\ProcessController::class,
        'paystack'         => Paystack\ProcessController::class,
        'flutterwave'      => Flutterwave\ProcessController::class,
        'paytm'            => Paytm\ProcessController::class,
        'coinpayments'     => CoinPayments\ProcessController::class,
        'coinbasecommerce' => CoinbaseCommerce\ProcessController::class,
        'instamojo'        => Instamojo\ProcessController::class,
        'perfectmoney'     => PerfectMoney\ProcessController::class,
        'voguepay'         => Voguepay\ProcessController::class,
    ];

    public function returnPay(string $gateway, Request $request)
    {
        return $this->dispatch($gateway, 'returnPay', $request);
    }

    public function cancel(string $gateway, Request $request)
    {
        return $this->dispatch($gateway, 'cancel', $request);
    }

    private function dispatch(string $gateway, string $method, Request $request)
    {
        $key        = strtolower($gateway);
        $controller = static::$map[$key] ?? null;

        if (! $controller) {
            Log::warning("GatewayCallbackController: unknown gateway [{$gateway}]");
            return redirect()->route('user.wallet.overview')->with('error', 'Unknown payment gateway.');
        }

        return app($controller)->{$method}($request);
    }
}
