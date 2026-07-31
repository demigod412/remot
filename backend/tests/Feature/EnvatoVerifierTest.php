<?php

namespace Tests\Feature;

use App\Support\Installer\EnvatoVerifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EnvatoVerifierTest extends TestCase
{
    private const CODE = '8a8b7c6d-1234-4abc-9def-0123456789ab';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['jobstation.envato.token' => '', 'jobstation.envato.item_id' => '', 'jobstation.envato.strict' => false]);
    }

    public function test_missing_token_is_rejected(): void
    {
        // A token is required — the buyer verifies their own purchase with it.
        $result = EnvatoVerifier::verify(self::CODE);

        $this->assertFalse($result['verified']);
        $this->assertStringContainsString('token', $result['message']);
    }

    public function test_invalid_format_is_rejected(): void
    {
        $result = EnvatoVerifier::verify('not-a-real-code', 'buyer-token');

        $this->assertFalse($result['verified']);
        $this->assertStringContainsString('format', $result['message']);
    }

    public function test_buyer_purchase_verification_succeeds(): void
    {
        config(['jobstation.envato.token' => 'buyer-token', 'jobstation.envato.item_id' => '12345']);
        Http::fake(['api.envato.com/*' => Http::response([
            'item'            => ['id' => 12345, 'name' => 'Job Station'],
            'license'         => 'Regular License',
            'supported_until' => '2027-01-01T00:00:00+00:00',
            'sold_at'         => '2026-01-01T00:00:00+00:00',
        ], 200)]);

        $result = EnvatoVerifier::verify(self::CODE);

        $this->assertTrue($result['verified']);
        $this->assertTrue($result['online']);
        $this->assertSame('live', $result['mode']);
        $this->assertSame('Regular License', $result['license_type']);
        $this->assertSame('12345', (string) $result['item_id']);
    }

    public function test_it_calls_the_buyer_purchase_endpoint(): void
    {
        config(['jobstation.envato.token' => 'buyer-token']);
        Http::fake(['api.envato.com/*' => Http::response(['item' => ['id' => 1]], 200)]);

        EnvatoVerifier::verify(self::CODE);

        Http::assertSent(fn ($req) => str_contains($req->url(), '/v3/market/buyer/purchase')
            && str_contains($req->url(), 'code='));
    }

    public function test_unknown_code_is_rejected(): void
    {
        config(['jobstation.envato.token' => 'buyer-token']);
        Http::fake(['api.envato.com/*' => Http::response('', 404)]);

        $result = EnvatoVerifier::verify(self::CODE);

        $this->assertFalse($result['verified']);
        $this->assertSame('live', $result['mode']);
    }

    public function test_code_for_a_different_item_is_rejected(): void
    {
        config(['jobstation.envato.token' => 'buyer-token', 'jobstation.envato.item_id' => '12345']);
        Http::fake(['api.envato.com/*' => Http::response([
            'item' => ['id' => 99999, 'name' => 'Some Other Item'],
        ], 200)]);

        $result = EnvatoVerifier::verify(self::CODE);

        $this->assertFalse($result['verified']);
        $this->assertStringContainsString('different item', $result['message']);
    }

    public function test_rejected_token_is_not_accepted(): void
    {
        // A bad/under-permissioned token is the buyer's to fix — never auto-accept.
        config(['jobstation.envato.token' => 'bad-token', 'jobstation.envato.strict' => false]);
        Http::fake(['api.envato.com/*' => Http::response('', 403)]);

        $result = EnvatoVerifier::verify(self::CODE);

        $this->assertFalse($result['verified']);
        $this->assertSame('live', $result['mode']);
    }

    public function test_api_outage_degrades_to_acceptance_when_not_strict(): void
    {
        // A genuine Envato outage (5xx) must not block a legitimate buyer.
        config(['jobstation.envato.token' => 'buyer-token', 'jobstation.envato.strict' => false]);
        Http::fake(['api.envato.com/*' => Http::response('', 503)]);

        $result = EnvatoVerifier::verify(self::CODE);

        $this->assertTrue($result['verified']);
        $this->assertSame('degraded', $result['mode']);
    }

    public function test_api_outage_fails_closed_in_strict_mode(): void
    {
        config(['jobstation.envato.token' => 'buyer-token', 'jobstation.envato.strict' => true]);
        Http::fake(['api.envato.com/*' => Http::response('', 503)]);

        $result = EnvatoVerifier::verify(self::CODE);

        $this->assertFalse($result['verified']);
    }

    public function test_network_failure_degrades_to_acceptance_when_not_strict(): void
    {
        config(['jobstation.envato.token' => 'buyer-token']);
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('timed out'));

        $result = EnvatoVerifier::verify(self::CODE);

        $this->assertTrue($result['verified']);
        $this->assertSame('degraded', $result['mode']);
    }

    public function test_successful_live_result_is_cached(): void
    {
        config(['jobstation.envato.token' => 'buyer-token', 'jobstation.envato.item_id' => '12345']);
        Http::fake(['api.envato.com/*' => Http::response([
            'item' => ['id' => 12345, 'name' => 'Job Station'],
        ], 200)]);

        EnvatoVerifier::verify(self::CODE);
        EnvatoVerifier::verify(self::CODE);

        // Only the first call should have reached Envato.
        Http::assertSentCount(1);
    }
}
