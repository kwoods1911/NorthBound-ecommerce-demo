<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        $unitPrice = fake()->numberBetween(2500, 30000);
        $quantity = fake()->numberBetween(1, 3);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'product_name' => fake()->words(3, true),
            'variant_label' => null,
            'sku' => null,
            'unit_price_cents' => $unitPrice,
            'unit_cost_cents' => (int) round($unitPrice * 0.4),
            'quantity' => $quantity,
            'line_total_cents' => $unitPrice * $quantity,
        ];
    }
}
