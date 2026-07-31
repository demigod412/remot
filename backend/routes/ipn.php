<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Payment Gateway IPN / Webhook Routes
|--------------------------------------------------------------------------
| Prefix: /ipn
*/

Route::any('Paypal',           [\App\Http\Controllers\Payment\Paypal\ProcessController::class,           'ipn'])->name('Paypal');
Route::any('PaypalSdk',        [\App\Http\Controllers\Payment\PaypalSdk\ProcessController::class,        'ipn'])->name('PaypalSdk');
Route::any('PerfectMoney',     [\App\Http\Controllers\Payment\PerfectMoney\ProcessController::class,     'ipn'])->name('PerfectMoney');
Route::any('Paystack',         [\App\Http\Controllers\Payment\Paystack\ProcessController::class,         'ipn'])->name('Paystack');
Route::any('Voguepay',         [\App\Http\Controllers\Payment\Voguepay\ProcessController::class,         'ipn'])->name('Voguepay');
Route::any('Flutterwave',      [\App\Http\Controllers\Payment\Flutterwave\ProcessController::class,      'ipn'])->name('Flutterwave');
Route::any('Razorpay',         [\App\Http\Controllers\Payment\Razorpay\ProcessController::class,         'ipn'])->name('Razorpay');
Route::any('Instamojo',        [\App\Http\Controllers\Payment\Instamojo\ProcessController::class,        'ipn'])->name('Instamojo');
Route::any('Coinpayments',     [\App\Http\Controllers\Payment\CoinPayments\ProcessController::class,     'ipn'])->name('Coinpayments');
Route::any('CoinbaseCommerce', [\App\Http\Controllers\Payment\CoinbaseCommerce\ProcessController::class, 'ipn'])->name('CoinbaseCommerce');
Route::any('StripeV3',         [\App\Http\Controllers\Payment\Stripe\ProcessController::class,           'ipn'])->name('StripeV3');
Route::any('Paytm',            [\App\Http\Controllers\Payment\Paytm\ProcessController::class,            'ipn'])->name('Paytm');
Route::any('PayIn',            [\App\Http\Controllers\Payment\PayIn\ProcessController::class,            'ipn'])->name('PayIn');
