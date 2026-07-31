<?php

namespace Database\Seeders;

use App\Models\PaymentChannel;
use Illuminate\Database\Seeder;

class PaymentChannelSeeder extends Seeder
{
    public function run(): void
    {
        $channels = [
            [
                'code'         => 1001,
                'name'         => 'Manual Bank Transfer',
                'driver'       => 'manual',
                'status'       => 1,
                'is_crypto'    => 0,
                'credentials'  => ['bank_name' => '', 'account_number' => '', 'account_name' => ''],
                'currencies'   => ['USD' => ['rate' => 1, 'min' => 1, 'max' => 10000, 'fixed_charge' => 0, 'percent_charge' => 0]],
                'instructions' => 'Transfer to the bank account below and upload your receipt.',
                'webhook_info' => null,
                'form_id'      => 0,
            ],
            [
                'code'         => 1002,
                'name'         => 'PayPal',
                'driver'       => 'paypal',
                'status'       => 0,
                'is_crypto'    => 0,
                'credentials'  => ['client_id' => '', 'client_secret' => '', 'mode' => 'sandbox'],
                'currencies'   => ['USD' => ['rate' => 1, 'min' => 5, 'max' => 10000, 'fixed_charge' => 0, 'percent_charge' => 3.5]],
                'instructions' => null,
                'webhook_info' => null,
                'form_id'      => 0,
            ],
            [
                'code'         => 1003,
                'name'         => 'Stripe',
                'driver'       => 'stripe',
                'status'       => 0,
                'is_crypto'    => 0,
                'credentials'  => ['publishable_key' => '', 'secret_key' => '', 'webhook_secret' => ''],
                'currencies'   => ['USD' => ['rate' => 1, 'min' => 1, 'max' => 99999, 'fixed_charge' => 0.30, 'percent_charge' => 2.9]],
                'instructions' => null,
                'webhook_info' => null,
                'form_id'      => 0,
            ],
            [
                'code'         => 1004,
                'name'         => 'Flutterwave',
                'driver'       => 'flutterwave',
                'status'       => 0,
                'is_crypto'    => 0,
                'credentials'  => ['public_key' => '', 'secret_key' => '', 'encryption_key' => ''],
                'currencies'   => ['NGN' => ['rate' => 1500, 'min' => 100, 'max' => 1000000, 'fixed_charge' => 0, 'percent_charge' => 1.4]],
                'instructions' => null,
                'webhook_info' => null,
                'form_id'      => 0,
            ],
            [
                'code'         => 1005,
                'name'         => 'Paystack',
                'driver'       => 'paystack',
                'status'       => 0,
                'is_crypto'    => 0,
                'credentials'  => ['public_key' => '', 'secret_key' => ''],
                'currencies'   => ['NGN' => ['rate' => 1500, 'min' => 100, 'max' => 5000000, 'fixed_charge' => 100, 'percent_charge' => 1.5]],
                'instructions' => null,
                'webhook_info' => null,
                'form_id'      => 0,
            ],
        ];

        foreach ($channels as $data) {
            PaymentChannel::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }
    }
}
