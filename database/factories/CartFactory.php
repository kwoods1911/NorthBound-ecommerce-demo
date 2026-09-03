<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Cart>
 */
class CartFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'session_id' => Str::random(40),
        ];
    }

    public function forUser(\App\Models\User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id, 'session_id' => null]);
    }
}
