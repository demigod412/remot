<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            LanguageSeeder::class,
            AppSettingSeeder::class,
            NotificationTemplateSeeder::class,
            PaymentChannelSeeder::class,
            PayoutMethodSeeder::class,
            PluginSeeder::class,
            ContentSectionSeeder::class,
            WorkCategorySeeder::class,
            SkillSeeder::class,
            CoinPackageSeeder::class,
            PolicyPageSeeder::class,
        ]);
    }
}
