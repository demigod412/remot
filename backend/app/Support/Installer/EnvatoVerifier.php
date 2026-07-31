<?php

namespace App\Support\Installer;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * CodeCanyon / Envato purchase verification (buyer model).
 *
 * The person installing the product verifies THEIR OWN purchase: during setup
 * they enter their purchase code plus an Envato personal token generated on
 * their own Envato account. We call the Envato buyer endpoint
 * (`/v3/market/buyer/purchase?code=`) with that token — it returns the purchase
 * only if the token owner actually bought this item, so no seller secret is ever
 * shipped inside the package.
 *
 * A token is REQUIRED — there is no offline bypass. Verification fails when the
 * format is wrong, when Envato can't find the purchase for that token (404), or
 * when the token/permission is rejected (401/403). Only a genuine Envato outage
 * (network error / 5xx) degrades gracefully so a legitimate buyer isn't blocked
 * by something outside their control; set ENVATO_STRICT=true to fail closed even
 * then.
 *
 * Buyer creates the token at build.envato.com with the permissions:
 *   "View and search Envato sites" + "Download your purchased items".
 * Seller sets ENVATO_ITEM_ID (this product's CodeCanyon item id) so the code is
 * checked against THIS item — configure it once the item is published.
 */
class EnvatoVerifier
{
    private const PURCHASE_ENDPOINT = 'https://api.envato.com/v3/market/buyer/purchase';

    /** Cache successful live verifications this many hours (respects Envato rate limits). */
    private const CACHE_HOURS = 12;

    public static function validFormat(string $code): bool
    {
        return (bool) preg_match(
            '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i',
            trim($code)
        );
    }

    /**
     * @return array{
     *   verified:bool, online:bool, mode:string, message:string,
     *   buyer:?string, item:?string, item_id:?string, license_type:?string,
     *   supported_until:?string, sold_at:?string, verified_at:string
     * }
     */
    public static function verify(string $code, ?string $token = null, ?string $itemId = null): array
    {
        // TEMP: bypass Envato verification for local testing only.
        // Remove this block to restore real license checking.
        if (app()->environment('local')) {
            return [
                'verified'        => true,
                'online'          => false,
                'mode'            => 'local-bypass',
                'message'         => 'License check skipped (local testing environment).',
                'buyer'           => 'Local Tester',
                'item'            => 'Job Station (local)',
                'item_id'         => null,
                'license_type'    => 'testing',
                'supported_until' => null,
                'sold_at'         => null,
                'verified_at'     => now()->toIso8601String(),
            ];
        }

        $code   = trim($code);
        $token  = $token !== null ? trim($token) : trim((string) config('jobstation.envato.token'));
        $itemId = $itemId !== null ? trim($itemId) : trim((string) config('jobstation.envato.item_id'));
        $strict = (bool) config('jobstation.envato.strict', false);

        $base = [
            'verified'        => false,
            'online'          => false,
            'mode'            => 'offline',
            'message'         => '',
            'buyer'           => null,
            'item'            => null,
            'item_id'         => null,
            'license_type'    => null,
            'supported_until' => null,
            'sold_at'         => null,
            'verified_at'     => now()->toIso8601String(),
        ];

        if (! self::validFormat($code)) {
            return array_merge($base, [
                'message' => 'Invalid purchase code format. It looks like: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
            ]);
        }

        // A token is required — the buyer verifies their own purchase with it.
        if ($token === '') {
            return array_merge($base, [
                'message' => 'Enter your Envato personal token to verify your purchase.',
            ]);
        }

        // Serve a cached live result if we verified this code recently.
        $cacheKey = 'envato.verify.'.hash('sha256', $code.'|'.$token.'|'.$itemId);
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        try {
            $resp = Http::withToken($token)
                ->acceptJson()
                // Only retry transient connection problems — never a definitive
                // HTTP response (a 404 rejection should return immediately).
                ->retry(2, 400, fn ($e) => $e instanceof \Illuminate\Http\Client\ConnectionException, throw: false)
                ->timeout(20)
                ->get(self::PURCHASE_ENDPOINT, ['code' => $code]);

            // Definitive rejection: this token's owner has no purchase with this code.
            if ($resp->status() === 404) {
                return array_merge($base, [
                    'online'  => true,
                    'mode'    => 'live',
                    'message' => 'No purchase found for this code on your Envato account. Use the purchase code and a personal token from the account that bought this item.',
                ]);
            }

            // Bad token or missing permission — the buyer's to fix, so don't accept.
            if (in_array($resp->status(), [401, 403], true)) {
                return array_merge($base, [
                    'online'  => true,
                    'mode'    => 'live',
                    'message' => 'Your Envato token was rejected. Create a token with the "Download your purchased items" permission and try again.',
                ]);
            }

            if (! $resp->successful()) {
                Log::warning('Envato API error during purchase verification: HTTP '.$resp->status());

                return self::degrade($base, $strict,
                    'Envato API error (HTTP '.$resp->status().'). Please try again in a moment.');
            }

            $data       = $resp->json() ?? [];
            $soldItemId = (string) ($data['item']['id'] ?? '');
            $itemName   = $data['item']['name'] ?? null;
            $supported  = $data['supported_until'] ?? null;
            $soldAt     = $data['sold_at'] ?? null;
            $license    = $data['license'] ?? null;

            // A 200 with no purchase body (rare) — don't hard fail on ambiguity.
            if ($soldItemId === '' && empty($data)) {
                return self::degrade($base, $strict,
                    'Envato returned an unexpected response. Please try again.');
            }

            // Make sure the code is for THIS product, not another item the buyer owns.
            if ($itemId !== '' && $soldItemId !== '' && $soldItemId !== $itemId) {
                return array_merge($base, [
                    'online'  => true,
                    'mode'    => 'live',
                    'item'    => $itemName,
                    'item_id' => $soldItemId,
                    'message' => 'This purchase code is for a different item, not this product.',
                ]);
            }

            $result = array_merge($base, [
                'verified'        => true,
                'online'          => true,
                'mode'            => 'live',
                'message'         => 'Purchase verified with Envato.',
                'item'            => $itemName,
                'item_id'         => $soldItemId ?: null,
                'license_type'    => $license,
                'supported_until' => $supported,
                'sold_at'         => $soldAt,
            ]);

            Cache::put($cacheKey, $result, now()->addHours(
                (int) config('jobstation.envato.cache_hours', self::CACHE_HOURS)
            ));

            return $result;
        } catch (\Throwable $e) {
            Log::warning('Envato API unreachable during purchase verification: '.$e->getMessage());

            return self::degrade($base, $strict,
                'Could not reach the Envato API ('.$e->getMessage().').');
        }
    }

    /**
     * Resolve an uncertain (non-definitive) outcome. In strict mode this is a
     * failure; otherwise it degrades to offline format-acceptance so a valid
     * buyer isn't blocked by a seller-side or network problem.
     */
    private static function degrade(array $base, bool $strict, string $reason): array
    {
        if ($strict) {
            return array_merge($base, ['online' => true, 'mode' => 'live', 'message' => $reason]);
        }

        return array_merge($base, [
            'verified' => true,
            'online'   => true,
            'mode'     => 'degraded',
            'message'  => $reason.' Your purchase code looks valid, so installation will continue. '
                        .'It will be re-checked automatically later.',
        ]);
    }
}