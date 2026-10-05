<?php

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inviter_user_id' => User::factory(),
            'email' => fake()->unique()->safeEmail(),
            'expires_at' => now()->addDays(7),
        ];
    }

    /**
     * Indicate that the invitation has already been consumed.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'accepted_at' => now(),
            'accepted_user_id' => User::factory(),
        ]);
    }

    /**
     * Indicate that the invitation has been revoked by its inviter.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked_at' => now(),
        ]);
    }

    /**
     * Indicate that the invitation is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => Carbon::now()->subDay(),
        ]);
    }
}
