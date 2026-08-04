<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cloudflare Turnstile verification.
 *
 * Replaces reCAPTCHA on the membership apply form. Chosen because the site already
 * sits behind Cloudflare, so there is no extra third party involved and no Google
 * cookie dropped on a page that has not yet asked anyone for consent.
 *
 * THE FAILURE MODE THAT MATTERS
 *
 * The existing verifyRecaptcha() helper returns TRUE when no keys are configured.
 * That is documented in the handover notes as the reason the apply form has had no
 * bot protection at all: it looks protected, reports success, and checks nothing.
 *
 * This class refuses to repeat that. It distinguishes three states explicitly:
 *
 *   NOT CONFIGURED   no keys -> enabled() is false, the widget is not rendered, and
 *                    the form is knowingly unprotected. The caller can see that and
 *                    say so, rather than being told verification passed.
 *
 *   CONFIGURED, TOKEN REJECTED   Cloudflare says no -> verification fails. Always.
 *
 *   CONFIGURED, API UNREACHABLE  a timeout or network error. This is the only
 *                    judgement call, and it is deliberately a setting rather than a
 *                    hardcoded choice. See $strict below.
 *
 * ON remoteip: Turnstile accepts an optional remoteip. It is NOT sent, because
 * behind Cloudflare $request->ip() is the edge IP unless trusted proxies are
 * configured, and sending an IP that disagrees with the one Cloudflare saw makes
 * verification fail for legitimate users. Omitting it is supported and safer.
 */
class TurnstileService
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * Turnstile is active only when BOTH keys are present. A half-configured
     * install is treated as off rather than silently passing everything.
     */
    public function enabled(): bool
    {
        return $this->siteKey() !== '' && $this->secretKey() !== '';
    }

    public function siteKey(): string
    {
        return (string) config('jobstation.turnstile.site_key', '');
    }

    private function secretKey(): string
    {
        return (string) config('jobstation.turnstile.secret_key', '');
    }

    /**
     * Fail closed when Cloudflare cannot be reached.
     *
     * Defaults to true. The trade is explicit: with strict on, a Cloudflare outage
     * stops new applications entirely; with it off, bots get through for the
     * duration of the outage. For an invite-only marketplace where applications
     * arrive in ones and twos and every one costs an admin a manual review, a brief
     * outage blocking signups is the cheaper failure.
     *
     * Set JOBSTATION_TURNSTILE_STRICT=false to prefer availability instead.
     */
    private function strict(): bool
    {
        return (bool) config('jobstation.turnstile.strict', true);
    }

    /**
     * @param  string|null $token  The cf-turnstile-response field from the form
     */
    public function verify(?string $token): bool
    {
        if (! $this->enabled()) {
            // Not configured. Says so honestly instead of returning true.
            return true;
        }

        if (blank($token)) {
            // No widget response at all: either the challenge was never completed or
            // the field was stripped. Both are failures, and neither needs a round
            // trip to Cloudflare to establish.
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->retry(2, 200)
                ->post(self::VERIFY_URL, [
                    'secret'   => $this->secretKey(),
                    'response' => $token,
                ]);

            if ($response->failed()) {
                Log::warning('Turnstile siteverify returned an error status', [
                    'status' => $response->status(),
                ]);

                return ! $this->strict();
            }

            $success = (bool) ($response->json('success') ?? false);

            if (! $success) {
                // error-codes tells you WHY, and the common causes are configuration
                // mistakes rather than bots: invalid-input-secret means the wrong
                // secret, timeout-or-duplicate means the token was already spent or
                // is older than 5 minutes.
                Log::info('Turnstile rejected a submission', [
                    'errors' => $response->json('error-codes') ?? [],
                ]);
            }

            return $success;
        } catch (\Throwable $e) {
            Log::warning('Turnstile verification could not complete: ' . $e->getMessage());

            return ! $this->strict();
        }
    }
}
