<?php

use App\Models\AppSetting;

if (! function_exists('gs')) {
    /**
     * Get the AppSetting singleton.
     */
    function gs(): AppSetting
    {
        return AppSetting::get();
    }
}

if (! function_exists('coinName')) {
    function coinName(): string
    {
        // Prefer the admin-editable setting (Admin → Settings → General),
        // falling back to the .env/config default.
        return gs()->coin_name ?: config('jobstation.coin_name', 'Job Station Coins');
    }
}

if (! function_exists('coinSymbol')) {
    function coinSymbol(): string
    {
        return gs()->coin_symbol ?: config('jobstation.coin_symbol', 'JC');
    }
}

if (! function_exists('formatCoins')) {
    /**
     * The single, canonical way to render a coin amount: "50 connect".
     *
     * Never build this by hand as coinSymbol() . $amount — that produces
     * "connect50", which is how the two styles ended up mixed across the UI.
     *
     * Decimals are shown only when they carry information, so a 50 coin reward
     * reads "50 connect" while a 2.50 fee reads "2.50 connect" instead of being
     * rounded to "3 connect".
     */
    function formatCoins(float|int|string|null $amount, ?int $decimals = null): string
    {
        // null is accepted on purpose and renders as zero.
        //
        // This helper replaced hand-built coinSymbol() . number_format($x) in 54
        // places. number_format() tolerates null; a strict signature here does not,
        // so every one of those call sites became a potential fatal wherever the value
        // could be unset. Nullable settings like gs()->boost_cost_work took the admin
        // skills page down in exactly that way.
        //
        // A display helper should never be the reason a page 500s. If a caller has no
        // amount, zero is the honest thing to render.
        $amount = (float) ($amount ?? 0);

        if ($decimals === null) {
            $decimals = fmod($amount, 1.0) === 0.0 ? 0 : 2;
        }

        return number_format($amount, $decimals) . ' ' . coinSymbol();
    }
}

if (! function_exists('formatMoney')) {
    /**
     * Render an amount in whichever currency it is actually denominated in.
     *
     * ledger_entries stores every amount in a column called `coins` and records the
     * currency separately, so rendering a row without consulting that column shows
     * a USD payout as coins. Use this anywhere a ledger row, cashout or earnings
     * figure is displayed.
     */
    function formatMoney(float|int|string|null $amount, string $currency = 'coin', ?int $decimals = null): string
    {
        if (strtolower($currency) === 'usd') {
            return '$' . number_format((float) ($amount ?? 0), $decimals ?? 2);
        }

        return formatCoins($amount, $decimals);
    }
}

if (! function_exists('formatUsd')) {
    function formatUsd(float|int|string|null $amount): string
    {
        return '$' . number_format((float) ($amount ?? 0), 2);
    }
}

if (! function_exists('generateReference')) {
    function generateReference(int $length = 10): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $result;
    }
}

if (! function_exists('safeUploadExtension')) {
    /**
     * Resolve a safe, lowercase file extension for an upload. Never trusts a
     * client-supplied extension for server-executable / active-content types;
     * falls back to the extension guessed from the real MIME, then to "bin".
     */
    function safeUploadExtension(\Illuminate\Http\UploadedFile $file): string
    {
        $blocked = [
            'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phps', 'pht',
            'phar', 'cgi', 'pl', 'asp', 'aspx', 'jsp', 'sh', 'bash', 'htaccess', 'htm', 'html', 'svg',
        ];

        $ext = preg_replace('/[^a-z0-9]/', '', strtolower((string) $file->getClientOriginalExtension()));

        if ($ext === '' || in_array($ext, $blocked, true)) {
            $guessed = strtolower((string) $file->guessExtension());
            $ext = ($guessed !== '' && ! in_array($guessed, $blocked, true)) ? $guessed : 'bin';
        }

        return $ext;
    }
}

if (! function_exists('uploadFile')) {
    /**
     * Upload a PUBLIC file (served directly from public/uploads) and return the
     * stored filename. Hardened extension + unguessable random name. This is
     * defence-in-depth on top of per-request `mimes:` validation and the
     * public/uploads/.htaccess that disables script execution in the uploads tree.
     *
     * Do NOT use this for sensitive documents (KYC, payment proofs, private
     * deliverables) — use uploadPrivateFile() so they never sit in the web root.
     */
    function uploadFile(\Illuminate\Http\UploadedFile $file, string $path): string
    {
        $filename = bin2hex(random_bytes(16)) . '.' . safeUploadExtension($file);

        $dir = public_path($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $file->move($dir, $filename);

        return $filename;
    }
}

if (! function_exists('uploadPrivateFile')) {
    /**
     * Upload a PRIVATE file to storage/app/private/{path} (never web-accessible)
     * and return the stored filename. Use for KYC documents and other PII; serve
     * them only through an authorised controller (see SecureFileController).
     */
    function uploadPrivateFile(\Illuminate\Http\UploadedFile $file, string $path): string
    {
        $filename = bin2hex(random_bytes(16)) . '.' . safeUploadExtension($file);

        \Illuminate\Support\Facades\Storage::disk('local')->putFileAs(trim($path, '/'), $file, $filename);

        return $filename;
    }
}

if (! function_exists('removePrivateFile')) {
    function removePrivateFile(string $path, string $filename): void
    {
        if ($filename === '') {
            return;
        }
        \Illuminate\Support\Facades\Storage::disk('local')->delete(trim($path, '/') . '/' . $filename);
    }
}

if (! function_exists('removeFile')) {
    function removeFile(string $path, string $filename): void
    {
        $fullPath = public_path($path . '/' . $filename);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
}

if (! function_exists('fileUrl')) {
    /**
     * Generate a public URL for an uploaded file.
     *
     * Supports two call patterns:
     *   fileUrl('uploads/works/covers', 'image.jpg')  → path + filename
     *   fileUrl('uploads/works/covers/image.jpg')      → combined path (single arg)
     */
    function fileUrl(string $path, string|null $filename = null): string
    {
        if ($filename === null) {
            // Single-arg: path already includes filename
            if (! $path) {
                return asset('assets/images/placeholder.png');
            }
            return asset($path);
        }

        if (! $filename) {
            return asset('assets/images/placeholder.png');
        }
        return asset(rtrim($path, '/') . '/' . $filename);
    }
}

if (! function_exists('statusBadge')) {
    function statusBadge(int $status, array $labels, array $colors = []): string
    {
        $defaultColors = ['gray', 'green', 'yellow', 'red', 'blue', 'purple'];
        $label = $labels[$status] ?? 'Unknown';
        $color = $colors[$status] ?? $defaultColors[$status] ?? 'gray';
        return "<span class=\"badge badge-{$color}\">{$label}</span>";
    }
}

if (! function_exists('is_app_installed')) {
    /**
     * Whether Job Station has completed the installation wizard.
     * Presence of the lock file at storage/installed marks a finished install.
     */
    function is_app_installed(): bool
    {
        return file_exists(storage_path('installed'));
    }
}

if (! function_exists('renderPluginScripts')) {
    /**
     * Render every active plugin's embed script with its {{shortcode}} values
     * filled in. Output before </body> on the public + user layouts.
     */
    function renderPluginScripts(): string
    {
        $out = '';
        foreach (\App\Models\Plugin::where('status', 1)->get() as $plugin) {
            $script = (string) $plugin->script;
            if ($script === '') {
                continue;
            }
            foreach (($plugin->shortcode ?? []) as $key => $val) {
                $script = str_replace('{{' . $key . '}}', (string) $val, $script);
            }
            $out .= $script . "\n";
        }
        return $out;
    }
}

if (! function_exists('recaptchaPlugin')) {
    function recaptchaPlugin(): ?\App\Models\Plugin
    {
        return \App\Models\Plugin::getByAct('google_recaptcha'); // active only
    }
}

if (! function_exists('recaptchaEnabled')) {
    /** True only when reCAPTCHA is active AND both keys are configured. */
    function recaptchaEnabled(): bool
    {
        $p = recaptchaPlugin();
        return $p && ! empty($p->shortcode['site_key']) && ! empty($p->shortcode['secret_key']);
    }
}

if (! function_exists('recaptchaWidget')) {
    /** The reCAPTCHA v2 widget + api script, or '' when not configured. */
    function recaptchaWidget(): string
    {
        if (! recaptchaEnabled()) {
            return '';
        }
        $siteKey = e(recaptchaPlugin()->shortcode['site_key']);
        return '<div class="g-recaptcha" data-sitekey="' . $siteKey . '" style="margin:14px 0;"></div>'
            . '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
    }
}

if (! function_exists('verifyRecaptcha')) {
    /**
     * Verify a reCAPTCHA response token server-side. Returns true when reCAPTCHA
     * is not configured (so it never blocks a site that hasn't enabled it).
     */
    function verifyRecaptcha(?string $token): bool
    {
        if (! recaptchaEnabled()) {
            return true;
        }
        if (empty($token)) {
            return false;
        }
        try {
            $res = \Illuminate\Support\Facades\Http::asForm()->timeout(10)->post(
                'https://www.google.com/recaptcha/api/siteverify',
                ['secret' => recaptchaPlugin()->shortcode['secret_key'], 'response' => $token]
            );
            return (bool) ($res->json('success') ?? false);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('reCAPTCHA verify failed: ' . $e->getMessage());
            return false;
        }
    }
}

if (! function_exists('richBody')) {
    /**
     * Render a user/admin-authored content field (work/job description, etc.).
     * If it contains HTML (rich content), output it sanitized; otherwise treat it
     * as plain text and preserve line breaks. Use inside {!! !!}.
     */
    function richBody(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        // Plain text → escape + keep line breaks (previous behaviour).
        if ($text === strip_tags($text)) {
            return nl2br(e($text));
        }

        // HTML → strip dangerous tags/attributes, keep formatting.
        $text = preg_replace('#<(script|style|iframe|object|embed|form|svg)\b[^>]*>.*?</\1>#is', '', $text);
        $text = preg_replace('#<(script|style|iframe|object|embed|form|link|meta|base)\b[^>]*>#i', '', $text);
        $text = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $text);
        $text = preg_replace('#(href|src)\s*=\s*("|\')\s*javascript:[^"\']*\2#i', '$1="#"', $text);

        return $text;
    }
}
