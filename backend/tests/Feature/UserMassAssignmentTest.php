<?php

namespace Tests\Feature;

use App\Models\User;

class UserMassAssignmentTest extends FeatureTestCase
{
    public function test_coin_balance_is_not_mass_assignable_via_fill(): void
    {
        $user = new User();
        $user->fill(['firstname' => 'Mallory', 'coin_balance' => 999999]);

        $this->assertNull($user->coin_balance);
        $this->assertSame('Mallory', $user->firstname);
    }

    public function test_create_ignores_coin_balance_but_forcefill_sets_it(): void
    {
        $user = User::create([
            'firstname'    => 'Eve',
            'lastname'     => 'Tester',
            'username'     => 'eve_' . uniqid(),
            'email'        => uniqid() . '@example.com',
            'password'     => 'secret-hash',
            'coin_balance' => 500, // attacker-style injection — must be ignored
        ]);

        $this->assertSame(0.0, (float) $user->fresh()->coin_balance);

        // Trusted server code can still set it explicitly.
        $user->forceFill(['coin_balance' => 500])->save();
        $this->assertSame(500.0, (float) $user->fresh()->coin_balance);
    }
}
