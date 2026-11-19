<?php

namespace Database\Factories;

use App\Models\HeroImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HeroImage>
 */
class HeroImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'image_path' => 'hero/'.fake()->word().'.jpg',
            'headline' => fake()->sentence(4),
            'subheading' => fake()->sentence(10),
            'cta_label' => 'Shop the collection',
            'cta_url' => '/new-arrivals',
            'is_active' => false,
            'sort_order' => fake()->numberBetween(0, 5),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}
