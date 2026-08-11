<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    private function settings(): AppSetting
    {
        return AppSetting::first() ?? new AppSetting();
    }

    // -------------------------------------------------------------------------
    // General
    // -------------------------------------------------------------------------

    public function general()
    {
        $settings = $this->settings();
        return view('admin.settings.general', compact('settings'));
    }

    public function updateGeneral(Request $request)
    {
        $data = $request->validate([
            'site_name'        => ['required', 'string', 'max:100'],
            'coin_name'        => ['required', 'string', 'max:50'],
            'coin_symbol'      => ['required', 'string', 'max:10'],
            'cur_text'         => ['required', 'string', 'max:30'],
            'cur_sym'          => ['required', 'string', 'max:10'],
            'ref_commission'       => ['required', 'numeric', 'min:0', 'max:100'],
            'contract_commission'  => ['required', 'numeric', 'min:0', 'max:100'],
            'boost_cost_work'     => ['required', 'numeric', 'min:0'],
            'boost_days_work'     => ['required', 'integer', 'min:1', 'max:90'],
            'boost_cost_job'      => ['required', 'numeric', 'min:0'],
            'boost_days_job'      => ['required', 'integer', 'min:1', 'max:90'],
            'coin_rate'          => ['nullable', 'numeric', 'min:0'],
            'min_cashout'        => ['nullable', 'numeric', 'min:1'],
            'withdrawal_window_enabled' => ['nullable', 'boolean'],
            'withdrawal_window_start'   => ['nullable', 'integer', 'min:1', 'max:31'],
            'withdrawal_window_end'     => ['nullable', 'integer', 'min:1', 'max:31', 'gte:withdrawal_window_start'],
            'one_withdrawal_per_month'  => ['nullable', 'boolean'],
            'default_review_hours'      => ['nullable', 'integer', 'min:1', 'max:720'],
            'abandon_after_hours'       => ['nullable', 'integer', 'min:1', 'max:720'],
            'coin_rate_currency' => ['nullable', 'string', 'max:20'],
            'show_coin_rate'     => ['nullable', 'boolean'],
            'currencies'        => ['nullable'],
            'default_currency'  => ['nullable', 'string', 'max:10'],
            'registration'     => ['nullable', 'boolean'],
            'allow_dark_mode'  => ['nullable', 'boolean'],
            'kyc_required'     => ['nullable', 'boolean'],
            'email_verification'=> ['nullable', 'boolean'],
            'phone_verification'=> ['nullable', 'boolean'],
            'secure_password'  => ['nullable', 'boolean'],
            'agree'            => ['nullable', 'boolean'],
            'force_ssl'        => ['nullable', 'boolean'],
            'maintenance_mode' => ['nullable', 'boolean'],
            'cookie_enabled'   => ['nullable', 'boolean'],
            'cookie_text'      => ['nullable', 'string', 'max:500'],
        ]);

        // Checkboxes default to false when unchecked
        foreach (['registration','allow_dark_mode','kyc_required','email_verification','phone_verification',
                  'secure_password','agree','force_ssl','maintenance_mode','show_coin_rate','cookie_enabled'] as $bool) {
            $data[$bool] = $request->boolean($bool);
        }

        // Parse currencies from form inputs
        $codes   = $request->input('currency_code', []);
        $names   = $request->input('currency_name', []);
        $symbols = $request->input('currency_symbol', []);
        $rates   = $request->input('currency_rate', []);
        $currencyRows = [];
        foreach ($codes as $i => $code) {
            if (trim($code)) {
                $currencyRows[] = [
                    'code'   => strtoupper(trim($code)),
                    'name'   => trim($names[$i] ?? ''),
                    'symbol' => trim($symbols[$i] ?? $code),
                    'rate'   => (float) ($rates[$i] ?? 0),
                ];
            }
        }
        $data['currencies'] = empty($currencyRows) ? null : $currencyRows;

        $settings = AppSetting::first();
        if ($settings) {
            $settings->update($data);
        } else {
            AppSetting::create($data);
        }

        AppSetting::forgetCache();

        return back()->with('success', 'General settings updated.');
    }

    // -------------------------------------------------------------------------
    // Mail
    // -------------------------------------------------------------------------

    public function mailSettings()
    {
        $settings = $this->settings();
        return view('admin.settings.mail', compact('settings'));
    }

    /**
     * Send a probe email to whatever address the admin gives, and report the result.
     *
     * Without this there is no way to know mail works until a real applicant is
     * approved and their temporary password disappears — the password is hashed on
     * save, so a failed send means that account cannot be logged into at all until it
     * is reset. Verifying beforehand is the difference between a config mistake and a
     * locked-out member.
     */
    public function sendTestMail(Request $request)
    {
        $data = $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        $result = \App\Services\NotifyService::sendTestEmail($data['test_email']);

        if ($result['sent']) {
            return back()->with('success', 'Test email sent to ' . $data['test_email'] . '. Check the inbox, and the spam folder.');
        }

        return back()->with('error', 'Test email FAILED: ' . $result['error']);
    }

    public function updateMailSettings(Request $request)
    {
        $data = $request->validate([
            'email_from'              => ['required', 'email'],
            'mail_config.driver'      => ['required', 'in:smtp,mailgun,sendgrid,log'],
            'mail_config.from_name'   => ['nullable', 'string', 'max:100'],
            'mail_config.host'        => ['nullable', 'string'],
            'mail_config.port'        => ['nullable', 'integer'],
            'mail_config.username'    => ['nullable', 'string'],
            'mail_config.password'    => ['nullable', 'string'],
            'mail_config.encryption'  => ['nullable', 'in:tls,ssl,'],
        ]);

        $mailConfig = $data['mail_config'];
        $mailConfig['from_address'] = $data['email_from'];

        $settings = AppSetting::first();
        if ($settings) {
            $settings->update([
                'email_from'  => $data['email_from'],
                'mail_config' => $mailConfig,
            ]);
        }

        AppSetting::forgetCache();
        return back()->with('success', 'Mail settings updated.');
    }

    // -------------------------------------------------------------------------
    // Notification
    // -------------------------------------------------------------------------

    public function notification()
    {
        $settings = $this->settings();
        return view('admin.settings.notification', compact('settings'));
    }

    public function updateNotification(Request $request)
    {
        $driver = $request->input('sms_config.driver', 'log');

        $driverRules = match ($driver) {
            'twilio'  => [
                'sms_config.account_sid' => ['required', 'string'],
                'sms_config.auth_token'  => ['required', 'string'],
                'sms_config.from'        => ['required', 'string', 'max:20'],
            ],
            'nexmo'   => [
                'sms_config.api_key'    => ['required', 'string'],
                'sms_config.api_secret' => ['required', 'string'],
                'sms_config.from'       => ['nullable', 'string', 'max:20'],
            ],
            'nextsms' => [
                'sms_config.token' => ['required', 'string'],
                'sms_config.from'  => ['nullable', 'string', 'max:20'],
            ],
            default   => [],
        };

        $request->validate(array_merge([
            'sms_config.driver' => ['required', 'in:twilio,nexmo,nextsms,log'],
        ], $driverRules));

        $smsConfig = $request->input('sms_config', []);
        $smsConfig['driver'] = $driver;

        $data = [
            'email_notify' => $request->boolean('email_notify'),
            'sms_notify'   => $request->boolean('sms_notify'),
            'sms_config'   => $smsConfig,
        ];

        $settings = AppSetting::first();
        if ($settings) {
            $settings->update($data);
        }

        AppSetting::forgetCache();
        return back()->with('success', 'Notification settings updated.');
    }

    // -------------------------------------------------------------------------
    // Logo / Icon
    // -------------------------------------------------------------------------

    public function logoIcon()
    {
        $settings = $this->settings();
        return view('admin.settings.logo', compact('settings'));
    }

    public function updateLogoIcon(Request $request)
    {
        $request->validate([
            'logo'    => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'image', 'max:512'],
        ]);

        $updates = [];

        if ($request->hasFile('logo')) {
            $updates['logo'] = uploadFile($request->file('logo'), config('jobstation.upload_paths.logos'));
        }
        if ($request->hasFile('favicon')) {
            $updates['favicon'] = uploadFile($request->file('favicon'), config('jobstation.upload_paths.logos'));
        }

        if ($updates) {
            $settings = AppSetting::first();
            if ($settings) $settings->update($updates);
        }

        AppSetting::forgetCache();
        return back()->with('success', 'Logo updated.');
    }

    // -------------------------------------------------------------------------
    // Notification Events
    // -------------------------------------------------------------------------

    public function notifEvents()
    {
        $groups = [
            'Account'    => ['WELCOME', 'EMAIL_VERIFICATION', 'PASSWORD_RESET'],
            'KYC'        => ['KYC_APPROVED', 'KYC_REJECTED'],
            'Work & Tasks' => ['WORK_APPROVED', 'WORK_REJECTED', 'SUBMISSION_APPROVED', 'SUBMISSION_REJECTED'],
            'Finance'    => ['TOPUP_APPROVED', 'TOPUP_REJECTED', 'CASHOUT_APPROVED', 'CASHOUT_REJECTED'],
            'Jobs'       => ['JOB_APPROVED', 'JOB_REJECTED'],
            'Other'      => ['REFERRAL_BONUS', 'TICKET_REPLY'],
        ];

        $templates = NotificationTemplate::all()->keyBy('act');

        return view('admin.settings.events', compact('groups', 'templates'));
    }

    public function updateNotifEvents(Request $request)
    {
        $input = $request->input('templates', []);

        $ids = NotificationTemplate::all();
        foreach ($ids as $template) {
            $template->update([
                'email_status' => isset($input[$template->id]['email_status']) ? 1 : 0,
                'sms_status'   => isset($input[$template->id]['sms_status'])   ? 1 : 0,
            ]);
        }

        return back()->with('success', 'Notification events updated.');
    }

    // -------------------------------------------------------------------------
    // Custom CSS
    // -------------------------------------------------------------------------

    public function customCss()
    {
        $settings = $this->settings();
        return view('admin.settings.css', compact('settings'));
    }

    public function updateCustomCss(Request $request)
    {
        $data = $request->validate([
            'custom_css' => ['nullable', 'string', 'max:10000'],
        ]);

        $settings = AppSetting::first();
        if ($settings) $settings->update($data);

        AppSetting::forgetCache();
        return back()->with('success', 'Custom CSS saved.');
    }

    // -------------------------------------------------------------------------
    // Social Login
    // -------------------------------------------------------------------------

    public function socialLogin()
    {
        $settings = $this->settings();
        return view('admin.settings.social', compact('settings'));
    }

    public function updateSocialLogin(Request $request)
    {
        $data = $request->validate([
            'google_client_id'     => ['nullable', 'string', 'max:255'],
            'google_client_secret' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = AppSetting::first();
        $existing = $settings->socialite_credentials ?? [];

        $existing['google'] = [
            'client_id'     => $data['google_client_id'] ?? '',
            'client_secret' => $data['google_client_secret'] ?? '',
        ];

        $settings->update(['socialite_credentials' => $existing]);
        AppSetting::forgetCache();

        return back()->with('success', 'Social login settings saved.');
    }

    // -------------------------------------------------------------------------
    // Links & Social (footer app badges, social icons, public contact)
    // -------------------------------------------------------------------------

    public function links()
    {
        $settings = $this->settings();
        return view('admin.settings.links', compact('settings'));
    }

    public function updateLinks(Request $request)
    {
        $data = $request->validate([
            'app_store_url'  => ['nullable', 'url', 'max:255'],
            'play_store_url' => ['nullable', 'url', 'max:255'],
            'facebook'       => ['nullable', 'url', 'max:255'],
            'twitter'        => ['nullable', 'url', 'max:255'],
            'linkedin'       => ['nullable', 'url', 'max:255'],
            'instagram'      => ['nullable', 'url', 'max:255'],
            'contact_email'  => ['nullable', 'email', 'max:255'],
            'contact_phone'  => ['nullable', 'string', 'max:40'],
            'support_hours'  => ['nullable', 'string', 'max:100'],
        ]);

        AppSetting::first()->update($data);
        AppSetting::forgetCache();

        return back()->with('success', 'Links & social settings saved.');
    }

    // -------------------------------------------------------------------------
    // Firebase
    // -------------------------------------------------------------------------

    public function firebase()
    {
        $settings    = $this->settings();
        $fbConfig    = $settings->firebase_config ?? [];
        $credPath    = $fbConfig['credentials_path'] ?? null;
        $credExists  = $credPath && file_exists($credPath);

        return view('admin.settings.firebase', compact('settings', 'fbConfig', 'credExists', 'credPath'));
    }

    public function updateFirebase(Request $request)
    {
        $request->validate([
            'project_id'          => ['nullable', 'string', 'max:200'],
            'web_api_key'         => ['nullable', 'string', 'max:200'],
            'credentials_json'    => ['nullable', 'file', 'mimes:json', 'max:512'],
        ]);

        $settings  = AppSetting::first();
        $fbConfig  = $settings->firebase_config ?? [];

        // Update text fields if provided
        if ($request->filled('project_id')) {
            $fbConfig['project_id'] = trim($request->project_id);
        }
        if ($request->filled('web_api_key')) {
            $fbConfig['web_api_key'] = trim($request->web_api_key);
        }

        // Handle service account JSON upload
        if ($request->hasFile('credentials_json')) {
            $file    = $request->file('credentials_json');
            $content = file_get_contents($file->getRealPath());
            $json    = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withErrors(['credentials_json' => 'The file is not valid JSON.']);
            }

            $required = ['type', 'project_id', 'private_key', 'client_email'];
            foreach ($required as $key) {
                if (empty($json[$key])) {
                    return back()->withErrors(['credentials_json' => "Missing required field \"{$key}\" — make sure this is a Firebase service account JSON."]);
                }
            }

            if ($json['type'] !== 'service_account') {
                return back()->withErrors(['credentials_json' => 'This does not look like a service account JSON (type must be "service_account").']);
            }

            // Store outside public/ so it's not web-accessible
            $dir  = storage_path('app/firebase');
            File::ensureDirectoryExists($dir, 0750);
            $path = $dir . '/service-account.json';
            file_put_contents($path, $content);

            $fbConfig['credentials_path'] = $path;

            // Sync project_id from the JSON if admin hasn't filled the field
            if (empty($fbConfig['project_id']) && ! empty($json['project_id'])) {
                $fbConfig['project_id'] = $json['project_id'];
            }

            // Bust the cached FCM access token so the new credentials are used immediately
            \Illuminate\Support\Facades\Cache::forget('fcm_access_token');
        }

        $settings->update(['firebase_config' => $fbConfig]);
        AppSetting::forgetCache();

        return back()->with('success', 'Firebase settings saved.');
    }

    // -------------------------------------------------------------------------
    // License (CodeCanyon / Envato purchase code)
    // -------------------------------------------------------------------------

    public function license()
    {
        return view('admin.settings.license', [
            'settings' => $this->settings(),
            'license'  => \App\Services\LicenseService::current(),
            'liveMode' => \App\Services\LicenseService::liveMode(),
        ]);
    }

    public function reverifyLicense()
    {
        if (! \App\Services\LicenseService::purchaseCode()) {
            return back()->with('error', 'No purchase code is recorded for this installation.');
        }

        $result = \App\Services\LicenseService::reverify();

        return $result['verified']
            ? back()->with('success', 'License re-checked: '.$result['message'])
            : back()->with('error', 'License check failed: '.$result['message']);
    }
}
