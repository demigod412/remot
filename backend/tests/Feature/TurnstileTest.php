<?php

namespace Tests\Feature;

use App\Services\TurnstileService;
use Illuminate\Support\Facades\Http;

/**
 * Turnstile verification, with particular attention to the failure modes.
 *
 * The reason this file exists: verifyRecaptcha() returns TRUE when no keys are set,
 * which is why the apply form has been unprotected while appearing protected. These
 * tests pin down what happens in each state so that cannot happen again quietly.
 */
class TurnstileTest extends FeatureTestCase
{
    private function service(?string $site = 'site-key', ?string $secret = 'secret-key', bool $strict = true): TurnstileService
    {
        config([
            'jobstation.turnstile.site_key'   => $site,
            'jobstation.turnstile.secret_key' => $secret,
            'jobstation.turnstile.strict'     => $strict,
        ]);

        return app(TurnstileService::class);
    }

    public function test_it_is_disabled_until_both_keys_are_set(): void
    {
        $this->assertFalse($this->service(site: '', secret: '')->enabled());
        $this->assertFalse($this->service(site: 'only-site', secret: '')->enabled(), 'A half-configured widget must count as off.');
        $this->assertFalse($this->service(site: '', secret: 'only-secret')->enabled());
        $this->assertTrue($this->service()->enabled());
    }

    /**
     * With no keys there is nothing to check, so submissions pass. The important part
     * is that enabled() reports false, so the form knows it is unprotected rather
     * than being told a check succeeded.
     */
    public function test_when_not_configured_it_passes_but_reports_disabled(): void
    {
        Http::fake();
        $service = $this->service(site: '', secret: '');

        $this->assertTrue($service->verify('anything'));
        $this->assertFalse($service->enabled());
        Http::assertNothingSent();
    }

    public function test_an_empty_token_fails_without_calling_cloudflare(): void
    {
        Http::fake();

        $this->assertFalse($this->service()->verify(null));
        $this->assertFalse($this->service()->verify(''));

        Http::assertNothingSent();
    }

    public function test_a_valid_token_passes(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true], 200),
        ]);

        $this->assertTrue($this->service()->verify('good-token'));
    }

    public function test_a_rejected_token_fails(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response([
                'success'     => false,
                'error-codes' => ['invalid-input-response'],
            ], 200),
        ]);

        $this->assertFalse($this->service()->verify('bad-token'));
    }

    /**
     * A spent or stale token is the most common real-world rejection, because this
     * form takes longer to fill than the token's five minute life.
     */
    public function test_a_duplicate_or_expired_token_fails(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response([
                'success'     => false,
                'error-codes' => ['timeout-or-duplicate'],
            ], 200),
        ]);

        $this->assertFalse($this->service()->verify('stale-token'));
    }

    public function test_strict_mode_fails_closed_when_cloudflare_is_unreachable(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('timed out'));

        $this->assertFalse(
            $this->service(strict: true)->verify('token'),
            'With strict on, an outage must block the submission.'
        );
    }

    public function test_non_strict_mode_fails_open_when_cloudflare_is_unreachable(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('timed out'));

        $this->assertTrue(
            $this->service(strict: false)->verify('token'),
            'With strict off, availability is preferred over the check.'
        );
    }

    public function test_a_server_error_from_cloudflare_respects_strict_mode(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response('', 503),
        ]);

        $this->assertFalse($this->service(strict: true)->verify('token'));
        $this->assertTrue($this->service(strict: false)->verify('token'));
    }

    /**
     * remoteip is deliberately omitted: behind Cloudflare the app's idea of the
     * client IP can disagree with the one Cloudflare saw, and a mismatch fails
     * verification for legitimate users.
     */
    public function test_the_client_ip_is_not_sent(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true], 200),
        ]);

        $this->service()->verify('token');

        Http::assertSent(function ($request) {
            $body = $request->data();
            $this->assertArrayHasKey('secret', $body);
            $this->assertArrayHasKey('response', $body);
            $this->assertArrayNotHasKey('remoteip', $body);

            return true;
        });
    }
}
