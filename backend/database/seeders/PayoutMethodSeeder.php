<?php

namespace Database\Seeders;

use App\Models\DynamicForm;
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
        // USDT (ERC20) is the only withdrawal route on this install. Workers earn in
        // USD and are paid in a dollar-pegged stablecoin, so no FX is involved and
        // coin_to_currency_rate stays at 1.
        //
        // Bank Transfer and Mobile Money are DISABLED rather than deleted: existing
        // cashout records still reference them by id, so removing the rows would
        // orphan history. Flip status back to 1 in Admin -> Payout Methods to
        // re-enable either one.
        $walletForm = DynamicForm::updateOrCreate(
            ['act' => 'payout_usdt_erc20'],
            ['form_data' => [
                [
                    'name'        => 'wallet_address',
                    'label'       => 'USDT (ERC20) wallet address',
                    'type'        => 'text',
                    'required'    => true,
                    'placeholder' => '0x...',
                ],
                [
                    'name'        => 'wallet_label',
                    'label'       => 'Label for this wallet (optional)',
                    'type'        => 'text',
                    'required'    => false,
                    'placeholder' => 'e.g. My Binance deposit address',
                ],
            ]]
        );

        PayoutMethod::updateOrCreate(
            ['name' => 'Crypto USDT (ERC20)'],
            [
                'form_id'               => $walletForm->id,
                'currency'              => 'USDT',
                'min_coins'             => 10,
                'max_coins'             => 100000,
                'coin_to_currency_rate' => 1,
                'fixed_fee'             => 1,
                'percent_fee'           => 1,
                'description'           => 'Paid in USDT on the Ethereum (ERC20) network. Double-check the address: transfers cannot be reversed.',
                'status'                => 1,
            ]
        );

        PayoutMethod::whereIn('name', ['Bank Transfer', 'Mobile Money'])->update(['status' => 0]);

        $methods = [];

        foreach ($methods as $data) {
            PayoutMethod::updateOrCreate(['name' => $data['name']], $data);
        }
    }
}
