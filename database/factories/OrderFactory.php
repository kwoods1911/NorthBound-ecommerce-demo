<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(5000, 80000);
        $shipping = fake()->randomElement([0, 0, 0, 1500]);

        return [
            'user_id' => User::factory(),
            'order_number' => 'TGC-'.fake()->unique()->numerify('########'),
            'status' => OrderStatus::Pending,
            'email' => fake()->safeEmail(),
            'shipping_name' => fake()->name(),
            'shipping_line1' => fake()->streetAddress(),
            'shipping_line2' => null,
            'shipping_city' => fake()->city(),
            'shipping_state' => fake()->stateAbbr(),
            'shipping_postal_code' => fake()->postcode(),
            'shipping_country' => 'US',
            'subtotal_cents' => $subtotal,
            'shipping_cents' => $shipping,
            'tax_cents' => 0,
            'total_cents' => $subtotal + $shipping,
            'stripe_payment_intent_id' => null,
        ];
    }

    public function guest(): static
    {
        return $this->state(fn () => ['user_id' => null]);
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            ...$status->timestampColumn() ? [$status->timestampColumn() => now()] : [],
        ]);
    }
}
