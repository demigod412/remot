<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => 'admin@jobstation.test'],
            [
                'name'     => 'Job Station Admin',
                'username' => 'admin',
                'password' => Hash::make('admin123'),
            ]
        );
    }
}
