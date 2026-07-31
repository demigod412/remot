<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'firstname'      => fake()->firstName(),
            'lastname'       => fake()->lastName(),
            'username'       => fake()->unique()->userName(),
            'email'          => fake()->unique()->safeEmail(),
            'password'       => static::$password ??= Hash::make('password'),
            'status'         => 1,
            'email_verified' => 1,
        ];
    }

    /** Mark the account suspended. */
    public function banned(): static
    {
        return $this->state(fn () => ['status' => 0]);
    }

    /** KYC verified. */
    public function kycVerified(): static
    {
        return $this->state(fn () => ['kyc_status' => 1]);
    }

    /**
     * Give the user a starting coin balance. coin_balance is intentionally
     * non-mass-assignable, so it must be set via forceFill after creation.
     */
    public function balance(float|int $coins): static
    {
        return $this->afterCreating(
            fn (User $user) => $user->forceFill(['coin_balance' => $coins])->save()
        );
    }
}
