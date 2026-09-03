<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'color' => fake()->safeColorName(),
            'size' => fake()->randomElement(['Carry-On', 'Medium', 'Large', 'One Size']),
            'image_path' => 'variants/'.fake()->word().'.jpg',
            'price_override_cents' => null,
            'sku' => 'TGC-'.strtoupper(\Illuminate\Support\Str::random(8)),
            'sort_order' => fake()->numberBetween(0, 5),
        ];
    }
}
