<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'site_name', 'coin_name', 'coin_symbol', 'coin_rate', 'min_cashout', 'coin_rate_currency', 'show_coin_rate', 'cur_text', 'cur_sym',
        // Withdrawal window and review clock. Absent from this list, the settings
        // form validated them, reported success, and mass assignment silently threw
        // them away — so the rules appeared configured and did nothing at all.
        'withdrawal_window_enabled', 'withdrawal_window_start', 'withdrawal_window_end',
        'one_withdrawal_per_month', 'default_review_hours', 'abandon_after_hours',
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
            'withdrawal_window_enabled' => 'boolean',
            'one_withdrawal_per_month'  => 'boolean',
            'withdrawal_window_start'   => 'integer',
            'withdrawal_window_end'     => 'integer',
            'default_review_hours'      => 'integer',
            'abandon_after_hours'       => 'integer',
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
    /**
     * Memoised for the life of the process.
     *
     * Held in a class property rather than a function-scoped `static $instance`,
     * because a function static cannot be reached from anywhere else — which is why
     * forgetCache() below was an empty method that silently did nothing.
     */
    protected static ?self $cached = null;

    public static function get(): static
    {
        if (static::$cached === null) {
            static::$cached = static::first() ?? new static();
        }

        return static::$cached;
    }

    /**
     * Flush cached instance (call after updating settings).
     */
    /**
     * Drop the memoised copy so the next gs() re-reads the row.
     *
     * This was an empty method. Every caller that changed a setting and then called
     * it believed the change had taken effect, and in a normal web request it had —
     * but only because the next request is a new process, not because this did
     * anything. Anywhere sharing a process (tests, queue workers, long-running
     * commands) kept serving the settings loaded at boot.
     */
    public static function forgetCache(): void
    {
        static::$cached = null;
    }
}
