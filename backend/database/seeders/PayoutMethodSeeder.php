<?php

namespace Database\Seeders;

use App\Models\PayoutMethod;
use Illuminate\Database\Seeder;

class PayoutMethodSeeder extends Seeder
{
    /**
     * Seeds default payout (cash-out) methods so withdrawal works on a fresh
     * install. Cash-outs are processed manually by the admin from the dashboard,
     * so these are active by default — the seller edits the rate/fees/limits (and
     * adds more methods) in Admin → Payout Methods.
     */
    public function run(): void
    {
        $methods = [
            [
                'name'                  => 'Bank Transfer',
                'currency'              => 'USD',
                'min_coins'             => 1000,
                'max_coins'             => 100000,
                'coin_to_currency_rate' => 1,   // 1 coin = 1.00 currency unit — adjust to your economy
                'fixed_fee'             => 0,
                'percent_fee'           => 2,
                'description'           => 'Manual bank transfer. Processed within 24–72 hours.',
                'status'                => 1,
            ],
            [
                'name'                  => 'Mobile Money',
                'currency'              => 'USD',
                'min_coins'             => 500,
                'max_coins'             => 50000,
                'coin_to_currency_rate' => 1,
                'fixed_fee'             => 0,
                'percent_fee'           => 2.5,
                'description'           => 'Mobile money payout. Enter the receiving phone number.',
                'status'                => 1,
            ],
        ];

        foreach ($methods as $data) {
            PayoutMethod::updateOrCreate(['name' => $data['name']], $data);
        }
    }
}
