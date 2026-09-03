<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(3, true);
        $priceCents = fake()->numberBetween(4900, 39900);

        return [
            'category_id' => Category::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(8)),
            'description' => fake()->paragraph(),
            'image_path' => 'products/'.fake()->word().'.jpg',
            'price_cents' => $priceCents,
            'cost_price_cents' => (int) round($priceCents * 0.4),
            'discount_price_cents' => null,
            'stock_quantity' => fake()->numberBetween(0, 120),
            'sales_count' => fake()->numberBetween(0, 500),
        ];
    }

    public function discounted(): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_price_cents' => (int) round($attributes['price_cents'] * 0.8),
        ]);
    }
}
