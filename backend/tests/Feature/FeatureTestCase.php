<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Base for feature tests: fresh in-memory schema each test, and an "installed"
 * lock so the EnsureInstalled middleware lets HTTP requests through (instead of
 * redirecting everything to /install). The lock is only created/removed if this
 * process created it, so a real local install lock is never touched.
 *
 * Users are created with a faker-free helper so the suite runs whether or not
 * dev dependencies (fakerphp/faker) are installed.
 */
abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    private bool $createdInstallLock = false;

    protected function setUp(): void
    {
        parent::setUp();

        $lock = storage_path('installed');
        if (! file_exists($lock)) {
            file_put_contents($lock, json_encode(['test' => true]));
            $this->createdInstallLock = true;
        }
    }

    protected function tearDown(): void
    {
        if ($this->createdInstallLock && file_exists(storage_path('installed'))) {
            @unlink(storage_path('installed'));
        }

        parent::tearDown();
    }

    /**
     * Create a persisted user. coin_balance is non-mass-assignable, so it is
     * applied via forceFill after creation.
     */
    protected function makeUser(array $attributes = [], float $balance = 0): User
    {
        $uid = bin2hex(random_bytes(5));

        $user = User::create(array_merge([
            'firstname'      => 'Test',
            'lastname'       => 'User',
            'username'       => 'user_' . $uid,
            'email'          => 'user_' . $uid . '@example.test',
            'password'       => Hash::make('password'),
            'status'         => 1,
            'email_verified' => 1,
        ], $attributes));

        if ($balance != 0.0) {
            $user->forceFill(['coin_balance' => $balance])->save();
        }

        return $user;
    }
}
