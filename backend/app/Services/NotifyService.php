<?php

namespace App\Services;

use App\Mail\UserNotification;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Services\FcmService;

class NotifyService
{
    /** Acts that carry one-time codes — kept out of the persistent in-app feed. */
    private const FEED_EXCLUDED_ACTS = [
        'EMAIL_VERIFICATION', 'PHONE_VERIFICATION', 'PASSWORD_RESET', 'TWO_FA_CODE',
    ];

    /**
     * Send a notification to a user by template act key.
     *
     * @param  User   $user
     * @param  string $act   Template act key (e.g. 'TOPUP_APPROVED')
     * @param  array  $data  Shortcode replacements (e.g. ['coins' => 100])
     */
    public static function send(User $user, string $act, array $data = []): bool
    {
        $template = NotificationTemplate::getByAct($act);

        if (! $template) {
            // A missing template is a configuration problem, not a delivery one, and
            // it is the reason MEMBERSHIP_APPROVED sends nothing until
            // MarketplaceSeeder has been run.
            Log::warning("NotifyService: no template found for act [{$act}]");

            return false;
        }

        $gs = gs();

        // Build global shortcode data
        $baseData = array_merge([
            'name'        => $user->fullname,
            'email'       => $user->email,
            'username'    => $user->username,
            'site_name'   => $gs->site_name ?? config('app.name'),
            'coin_name'   => $gs->coin_name ?? coinName(),
            'coin_symbol' => $gs->coin_symbol ?? coinSymbol(),
            'balance'     => number_format((float) $user->coin_balance, 0),
        ], $data);

        // Send email
        $emailed = null;
        if ($template->email_status && $gs->email_notify) {
            $subject = static::replacePlaceholders($template->subj, $baseData);
            $body    = static::replacePlaceholders($template->email_body, $baseData);
            $emailed = static::sendEmailTo($user, $subject, $body);
        }

        // Send SMS
        if ($template->sms_status && $gs->sms_notify && $user->mobile) {
            $message = static::replacePlaceholders($template->sms_body, $baseData);
            static::sendSmsTo($user, $message);
        }

        // Shared title/body for the in-app feed and the push payload.
        $pushTitle = static::replacePlaceholders($template->subj, $baseData);
        $pushBody  = trim(strip_tags(static::replacePlaceholders(
            $template->sms_body ?: $template->email_body,
            $baseData
        )));
        $pushBody  = mb_strimwidth($pushBody, 0, 200, '…');

        // In-app notification feed (the bell icon). Persisted for every act
        // except one-time security codes, which only belong in email/SMS.
        if (! in_array($act, static::FEED_EXCLUDED_ACTS, true)) {
            $meta = static::feedMeta($act);
            \App\Models\UserNotification::notify(
                $user->id,
                $meta['type'],
                $pushTitle,
                $pushBody,
                $meta['url'],
                $meta['icon'],
            );
        }

        // Send FCM push notification (if the user has any registered devices).
        if ($user->relationLoaded('deviceTokens')
            ? $user->deviceTokens->isNotEmpty()
            : $user->deviceTokens()->exists()
        ) {
            FcmService::sendToUser($user, $pushTitle, $pushBody);
        }

        // true when email went out, or when this template does not send email at all.
        // Only an actual delivery failure returns false, so callers that care about
        // the email specifically can tell the difference.
        return $emailed !== false;
    }

    /** Map a template act key to an in-app feed type + lucide icon. */
    private static function feedMeta(string $act): array
    {
        return match (true) {
            in_array($act, ['TOPUP_APPROVED', 'TOPUP_REJECTED', 'CASHOUT_APPROVED', 'CASHOUT_REJECTED', 'REFERRAL_BONUS'], true)
                => ['type' => 'wallet', 'icon' => 'wallet', 'url' => '/wallet'],
            in_array($act, ['SUBMISSION_APPROVED', 'SUBMISSION_REJECTED', 'WORK_APPROVED', 'WORK_REJECTED'], true)
                => ['type' => 'work', 'icon' => 'zap', 'url' => '/submissions'],
            str_starts_with($act, 'CONTRACT_')
                => ['type' => 'contract', 'icon' => 'handshake', 'url' => '/contracts'],
            default
                => ['type' => 'system', 'icon' => 'bell', 'url' => null],
        };
    }

    /**
     * Send a raw email directly (bypasses template system).
     */
    public static function sendEmailTo(User $user, string $subject, string $body): bool
    {
        $gs = gs();

        try {
            static::configureMailer($gs->mail_config ?? []);

            Mail::to($user->email, $user->fullname)
                ->send(new UserNotification($subject, $body));

            NotificationLog::create([
                'user_id'           => $user->id,
                'sender'            => $gs->site_name ?? config('app.name'),
                'sent_from'         => $gs->email_from ?? config('mail.from.address'),
                'sent_to'           => $user->email,
                'subject'           => $subject,
                'message'           => $body,
                'notification_type' => 'email',
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error("NotifyService email failed for user #{$user->id}: " . $e->getMessage());

            // Reported rather than only logged. A caller that has just created an
            // account whose ONLY copy of the password is in this email needs to know
            // it did not arrive; the log is not somewhere an admin is looking.
            return false;
        }
    }

    /**
     * Send an email to an address with no User row behind it.
     *
     * Needed for membership application rejections: the applicant was never
     * given an account, so there is no User to key sendEmailTo() off. Logged
     * with user_id 0 so the notification history still shows it went out.
     */
    public static function sendRawEmail(
        string $email,
        string $name,
        string $subject,
        string $body
    ): bool {
        $gs = gs();

        try {
            static::configureMailer($gs->mail_config ?? []);

            Mail::to($email, $name)->send(new UserNotification($subject, $body));

            NotificationLog::create([
                'user_id'           => 0,
                'sender'            => $gs->site_name ?? config('app.name'),
                'sent_from'         => $gs->email_from ?? config('mail.from.address'),
                'sent_to'           => $email,
                'subject'           => $subject,
                'message'           => $body,
                'notification_type' => 'email',
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error("NotifyService raw email failed for {$email}: " . $e->getMessage());

            return false;
        }
    }

    /**
     * Send a raw SMS directly.
     */
    public static function sendSmsTo(User $user, string $message): void
    {
        $gs        = gs();
        $smsConfig = $gs->sms_config ?? [];
        $driver    = $smsConfig['driver'] ?? 'off';
        $mobile    = $user->country_code . $user->mobile;

        try {
            match ($driver) {
                'twilio'   => static::sendViaTwilio($mobile, $message, $smsConfig),
                'nexmo'    => static::sendViaNexmo($mobile, $message, $smsConfig),
                'nextsms'  => static::sendViaNextsms($mobile, $message, $smsConfig),
                default    => null,
            };

            NotificationLog::create([
                'user_id'           => $user->id,
                'sender'            => $gs->site_name ?? config('app.name'),
                'sent_from'         => $smsConfig['from'] ?? '',
                'sent_to'           => $mobile,
                'subject'           => 'SMS',
                'message'           => $message,
                'notification_type' => 'sms',
            ]);
        } catch (\Throwable $e) {
            Log::error("NotifyService SMS failed for user #{$user->id}: " . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Replace {{key}} placeholders in a string.
     */
    private static function replacePlaceholders(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            $text = str_replace(['{{' . $key . '}}', '[[' . $key . ']]'], $value, $text);
        }
        return $text;
    }

    /**
     * Dynamically configure Laravel mail from AppSetting.mail_config.
     */
    private static function configureMailer(array $mailConfig): void
    {
        // Anything the admin left blank is DISCARDED rather than applied.
        //
        // This used to read $mailConfig['host'] ?? env('MAIL_HOST'). The ?? operator
        // only falls back on null, and the admin form always posts every key — so a
        // blank host field posted '' and overwrote a perfectly good MAIL_HOST with an
        // empty string. Half-filling that form silently broke all outgoing mail while
        // appearing configured.
        $set = array_filter(
            $mailConfig,
            fn ($v) => $v !== null && $v !== '' && $v !== []
        );

        // Nothing usable in the database: leave Laravel's env-driven config alone.
        // .env alone is a perfectly valid way to run this.
        if ($set === []) {
            return;
        }

        $driver = $set['driver'] ?? config('mail.default', 'smtp');

        $values = ['mail.default' => $driver];

        foreach ([
            'host'     => 'mail.mailers.smtp.host',
            'port'     => 'mail.mailers.smtp.port',
            'username' => 'mail.mailers.smtp.username',
            'password' => 'mail.mailers.smtp.password',
        ] as $key => $path) {
            if (isset($set[$key])) {
                $values[$path] = $set[$key];
            }
        }

        // Laravel 11+ dropped 'encryption' for the SMTP transport in favour of
        // 'scheme' (smtp | smtps). config/mail.php in this app uses 'scheme', so the
        // old code was writing a key nothing reads: an admin choosing SSL got no SSL.
        //
        // Explicit beats inferred here. Left unset, Symfony guesses from the port,
        // which is right often enough to hide the problem and wrong on custom ports.
        if (isset($set['encryption'])) {
            $values['mail.mailers.smtp.scheme'] = $set['encryption'] === 'ssl' ? 'smtps' : 'smtp';
        }

        if (isset($set['from_address'])) {
            $values['mail.from.address'] = $set['from_address'];
        }
        if (isset($set['from_name'])) {
            $values['mail.from.name'] = $set['from_name'];
        }

        config($values);
    }

    /**
     * Send a probe email and report what actually happened.
     *
     * Exists because every other send path in this class swallows failures into the
     * log. That is right for a background notification and wrong for configuration:
     * an admin needs to know whether the settings they just saved work, before a
     * worker's temporary password is the thing that goes missing.
     *
     * @return array{sent: bool, error: string|null}
     */
    public static function sendTestEmail(string $to): array
    {
        $gs = gs();

        try {
            static::configureMailer($gs->mail_config ?? []);

            $subject = ($gs->site_name ?? config('app.name')) . ' — test email';
            $body    = '<p>This is a test email. If you are reading it, outgoing mail works.</p>'
                . '<p>Sent ' . now()->toDayDateTimeString() . ' from '
                . e(config('mail.mailers.smtp.host') ?: config('mail.default')) . '.</p>';

            Mail::to($to)->send(new UserNotification($subject, $body));

            return ['sent' => true, 'error' => null];
        } catch (\Throwable $e) {
            Log::error('Test email failed: ' . $e->getMessage());

            return ['sent' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send SMS via Twilio.
     */
    private static function sendViaTwilio(string $to, string $message, array $config): void
    {
        $sid   = $config['account_sid'] ?? '';
        $token = $config['auth_token'] ?? '';
        $from  = $config['from'] ?? '';

        if (! $sid || ! $token || ! $from) {
            throw new \RuntimeException('Twilio credentials incomplete.');
        }

        $url  = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";
        $data = http_build_query(['To' => $to, 'From' => $from, 'Body' => $message]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_USERPWD        => "{$sid}:{$token}",
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new \RuntimeException("Twilio error {$httpCode}: {$response}");
        }
    }

    /**
     * Send SMS via NextSMS (HTTP GET link method).
     */
    private static function sendViaNextsms(string $to, string $message, array $config): void
    {
        $token = $config['token'] ?? '';
        $from  = $config['from'] ?? 'INFO';

        if (! $token) {
            throw new \RuntimeException('NextSMS token is missing.');
        }

        $url = 'https://messaging-service.co.tz/link/sms/v2/text/single?' . http_build_query([
            'token' => $token,
            'from'  => $from,
            'to'    => ltrim($to, '+'),
            'text'  => $message,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new \RuntimeException("NextSMS error {$httpCode}: {$response}");
        }

        $result = json_decode($response, true);
        if (isset($result['code']) && (int) $result['code'] !== 200) {
            throw new \RuntimeException('NextSMS failed: ' . ($result['message'] ?? 'Unknown error'));
        }
    }

    /**
     * Send SMS via Nexmo/Vonage.
     */
    private static function sendViaNexmo(string $to, string $message, array $config): void
    {
        $apiKey    = $config['api_key'] ?? '';
        $apiSecret = $config['api_secret'] ?? '';
        $from      = $config['from'] ?? 'Job Station';

        if (! $apiKey || ! $apiSecret) {
            throw new \RuntimeException('Nexmo credentials incomplete.');
        }

        $url    = 'https://rest.nexmo.com/sms/json';
        $params = http_build_query([
            'api_key'    => $apiKey,
            'api_secret' => $apiSecret,
            'to'         => ltrim($to, '+'),
            'from'       => $from,
            'text'       => $message,
        ]);

        $ch = curl_init($url . '?' . $params);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        $status = $result['messages'][0]['status'] ?? '1';
        if ($status !== '0') {
            throw new \RuntimeException("Nexmo error: " . ($result['messages'][0]['error-text'] ?? 'Unknown'));
        }
    }
}
