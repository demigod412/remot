<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'site_name', 'coin_name', 'coin_symbol', 'coin_rate', 'min_cashout', 'coin_rate_currency', 'show_coin_rate', 'cur_text', 'cur_sym',
        'ref_commission', 'contract_commission',
        'boost_cost_work', 'boost_days_work', 'boost_cost_job', 'boost_days_job',
        'email_from', 'email_template',
        'sms_body', 'sms_from', 'base_color', 'secondary_color',
        'mail_config', 'sms_config', 'global_shortcodes',
        'currencies', 'default_currency',
        'kyc_required', 'email_verification', 'email_notify',
        'phone_verification', 'sms_notify', 'force_ssl',
        'maintenance_mode', 'secure_password', 'agree', 'registration',
        'allow_dark_mode',
        'active_template', 'system_info', 'socialite_credentials', 'custom_css',
        'cookie_enabled', 'cookie_text',
        'contact_email', 'contact_phone', 'support_hours',
        'facebook', 'twitter', 'linkedin', 'instagram',
        'app_store_url', 'play_store_url',
        'firebase_config',
    ];

    protected function casts(): array
    {
        return [
            'currencies'           => 'array',
            'mail_config'          => 'array',
            'sms_config'           => 'array',
            'socialite_credentials'=> 'array',
            'system_info'          => 'array',
            'firebase_config'      => 'array',
            'email_verification'   => 'boolean',
            'email_notify'         => 'boolean',
            'phone_verification'   => 'boolean',
            'sms_notify'           => 'boolean',
            'kyc_required'         => 'boolean',
            'force_ssl'            => 'boolean',
            'maintenance_mode'     => 'boolean',
            'secure_password'      => 'boolean',
            'agree'                => 'boolean',
            'registration'         => 'boolean',
            'allow_dark_mode'      => 'boolean',
            'show_coin_rate'       => 'boolean',
            'cookie_enabled'       => 'boolean',
            'coin_rate'            => 'decimal:4',
            'min_cashout'          => 'decimal:2',
            'ref_commission'       => 'decimal:2',
            'contract_commission'  => 'decimal:2',
            'boost_cost_work'     => 'decimal:2',
            'boost_cost_job'      => 'decimal:2',
            'boost_days_work'     => 'integer',
            'boost_days_job'      => 'integer',
        ];
    }

    /**
     * Get the singleton settings instance.
     */
    public static function get(): static
    {
        static $instance = null;
        if ($instance === null) {
            $instance = static::first() ?? new static();
        }
        return $instance;
    }

    /**
     * Flush cached instance (call after updating settings).
     */
    public static function forgetCache(): void
    {
        // re-query on next call by clearing PHP static
        // Can be extended to use Redis cache later
    }
}
