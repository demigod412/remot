<?php

namespace Database\Seeders;

use App\Models\CoinPackage;
use Illuminate\Database\Seeder;

class CoinPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name'         => 'Starter Pack',
                'coins'        => 500,
                'bonus_coins'  => 0,
                'price'        => 5.00,
                'currency'     => 'USD',
                'is_popular'   => 0,
                'status'       => 1,
            ],
            [
                'name'         => 'Basic Pack',
                'coins'        => 1200,
                'bonus_coins'  => 100,
                'price'        => 10.00,
                'currency'     => 'USD',
                'is_popular'   => 0,
                'status'       => 1,
            ],
            [
                'name'         => 'Pro Pack',
                'coins'        => 2500,
                'bonus_coins'  => 300,
                'price'        => 20.00,
                'currency'     => 'USD',
                'is_popular'   => 1,
                'status'       => 1,
            ],
            [
                'name'         => 'Business Pack',
                'coins'        => 6500,
                'bonus_coins'  => 1000,
                'price'        => 50.00,
                'currency'     => 'USD',
                'is_popular'   => 0,
                'status'       => 1,
            ],
            [
                'name'         => 'Enterprise Pack',
                'coins'        => 15000,
                'bonus_coins'  => 3000,
                'price'        => 100.00,
                'currency'     => 'USD',
                'is_popular'   => 0,
                'status'       => 1,
            ],
        ];

        foreach ($packages as $data) {
            CoinPackage::updateOrCreate(
                ['name' => $data['name']],
                $data
            );
        }
    }
}
