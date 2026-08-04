<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Route;

/**
 * Every GET page must render without a server error.
 *
 * The Blade layer is where nearly every bug in this codebase has lived, and until
 * now nothing exercised it. A parse error, an undefined variable, a foreach over a
 * string, a call to an accessor that does not exist — none of that shows up in a
 * unit test, and all of it takes a page to a white 500.
 *
 * Real examples this would have caught:
 *   admin/payment-channels/{id}/edit   foreach() over a string
 *   dashboard/wallet/cashout/preview   Blade comment inside a @php block
 *
 * Deliberately shallow: it asserts pages do not CRASH, not that they are correct.
 * 200, 302, 403 and 404 all pass; only 5xx fails. That is a low bar, and pages have
 * failed it.
 *
 * Runs the real DatabaseSeeder so detail pages get genuine records to render — a
 * 404 from an invented id would prove nothing, and it was exactly a seeded record
 * that exposed the payment-channel bug.
 *
 * ONE test rather than one per route, on purpose: the failure message lists every
 * broken page at once, which is more useful than stopping at the first.
 */
class RouteSmokeTest extends FeatureTestCase
{
    /**
     * Routes left alone. Each reason is about the route itself, not about the
     * inconvenience of fixing what it reveals.
     */
    private const SKIP_PREFIXES = [
        'logout'        => 'ends the session mid-suite',
        'user.logout'   => 'ends the session mid-suite',
        'admin.logout'  => 'ends the session mid-suite',
        'install'       => 'installer, gated by the install lock',
        'ipn'           => 'gateway callback, not browsable',
        'sitemap'       => 'generated file, not a view',
        'debugbar'      => 'dev tooling',
        'sanctum'       => 'framework internals',
        'ignition'      => 'framework internals',
        'livewire'      => 'framework internals',
        'telescope'     => 'dev tooling',
        'horizon'       => 'dev tooling',
    ];

    public function test_no_get_route_returns_a_server_error(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = $this->makeUser([
            'kyc_status'           => 1,
            'must_change_password' => false,
        ], 500);
        $user->forceFill(['usd_balance' => 500])->save();

        $admin = Admin::query()->first() ?? Admin::create([
            'name'     => 'Smoke Admin',
            'email'    => 'smoke_admin@example.test',
            'username' => 'smoke_admin',
            'password' => bcrypt('password'),
        ]);

        $failures = [];
        $checked   = 0;
        $skipped   = 0;

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if (! $name || ! in_array('GET', $route->methods(), true)) {
                continue;
            }

            if ($this->isSkipped($name)) {
                $skipped++;
                continue;
            }

            // Only routes we can build a real URL for. Parameterised routes are
            // resolved from seeded records where the name maps to something concrete.
            try {
                $url = route($name, $this->parametersFor($route->uri()), false);
            } catch (\Throwable $e) {
                $skipped++;
                continue;
            }

            if (str_contains($url, '{')) {
                $skipped++;
                continue;
            }

            $request = str_starts_with($name, 'admin.')
                ? $this->actingAs($admin, 'admin')
                : (str_starts_with($name, 'user.') ? $this->actingAs($user, 'web') : $this);

            try {
                $response = $request->get($url);
                $status   = $response->getStatusCode();
            } catch (\Throwable $e) {
                $failures[] = sprintf('%s  (%s)  threw %s: %s', $name, $url, class_basename($e), $e->getMessage());
                continue;
            }

            $checked++;

            if ($status >= 500) {
                // Report the underlying exception, not just the status. "returned 500"
                // sends you hunting; "foreach() argument must be of type array, string
                // given at plugins/index.blade.php:48" is the actual answer.
                $detail = '';
                if ($response->exception) {
                    $detail = sprintf(
                        "\n        %s: %s\n        at %s:%d",
                        class_basename($response->exception),
                        $response->exception->getMessage(),
                        str_replace(base_path() . '/', '', $response->exception->getFile()),
                        $response->exception->getLine()
                    );
                }

                $failures[] = sprintf('%s  (%s)  returned %d%s', $name, $url, $status, $detail);
            }
        }

        $this->assertGreaterThan(20, $checked, 'Expected to reach a meaningful number of routes.');

        $this->assertSame(
            [],
            $failures,
            sprintf(
                "%d page(s) returned a server error (checked %d, skipped %d):\n  - %s",
                count($failures),
                $checked,
                $skipped,
                implode("\n  - ", $failures)
            )
        );
    }

    private function isSkipped(string $name): bool
    {
        foreach (array_keys(self::SKIP_PREFIXES) as $prefix) {
            if ($name === $prefix || str_starts_with($name, $prefix . '.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve URL parameters from seeded data.
     *
     * Passing the wrong kind of id is harmless here: a findOrFail gives a 404, which
     * passes. What matters is that ids EXIST for the tables where a real record
     * changes what the view does.
     */
    private function parametersFor(string $uri): array
    {
        if (! preg_match_all('/\{(\w+)\??\}/', $uri, $matches)) {
            return [];
        }

        $params = [];

        foreach ($matches[1] as $param) {
            $value = $this->resolveParameter($param, $uri);

            if ($value === null) {
                // Force the caller's str_contains('{') check to skip this route.
                return [];
            }

            $params[$param] = $value;
        }

        return $params;
    }

    private function resolveParameter(string $param, string $uri): int|string|null
    {
        // Table guessed from the URI segment, so /admin/payment-channels/{id}/edit
        // resolves against payment_channels rather than a random id.
        $segment = explode('/', trim($uri, '/'))[1] ?? '';
        $table   = str_replace('-', '_', $segment);

        if (in_array($param, ['id', 'slug'], true) && $table !== '') {
            $column = $param === 'slug' ? 'slug' : 'id';

            try {
                $value = \Illuminate\Support\Facades\DB::table($table)->value($column);
                if ($value !== null) {
                    return $value;
                }
            } catch (\Throwable $e) {
                // Not a real table, fall through.
            }
        }

        return match ($param) {
            'locale' => 'en',
            default  => null,
        };
    }
}
