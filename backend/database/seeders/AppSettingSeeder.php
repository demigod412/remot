<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class AppSettingSeeder extends Seeder
{
    public function run(): void
    {
        if (AppSetting::count() === 0) {
            AppSetting::create([
                'site_name'          => 'Job Station',
                'coin_name'          => 'Job Station Coins',
                'coin_symbol'        => 'JC',
                'cur_text'           => 'USD',
                'cur_sym'            => '$',
                'default_currency'   => 'USD',
                'currencies'         => [
                    ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'rate' => 1],
                ],
                'ref_commission'     => 2.00,
                'contract_commission'=> 5.00,
                'email_from'         => 'hello@jobstation.test',
                'email_template'     => null,
                'sms_body'           => null,
                'sms_from'           => null,
                'base_color'         => '#6C47FF',
                'secondary_color'    => '#F59E0B',
                'mail_config'        => null,
                'sms_config'         => null,
                'global_shortcodes'  => null,
                'kyc_required'       => 0,
                'email_verification' => 1,
                'email_notify'       => 1,
                'phone_verification' => 0,
                'sms_notify'         => 0,
                'force_ssl'          => 0,
                'maintenance_mode'   => 0,
                'secure_password'    => 0,
                'agree'              => 0,
                'registration'       => 1,
                'active_template'    => 'default',
                'system_info'        => null,
                'socialite_credentials' => null,
                'custom_css'         => null,
            ]);
        }
    }
}
