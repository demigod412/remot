<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        Language::updateOrCreate(
            ['code' => 'en'],
            [
                'name'       => 'English',
                'icon'       => '🇬🇧',
                'text_align' => false, // LTR
                'is_default' => true,
            ]
        );
    }
}
