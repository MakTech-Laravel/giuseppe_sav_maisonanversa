<?php

namespace Database\Factories;

use App\Models\DressingItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DressingItem>
 */
class DressingItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'category' => fake()->randomElement(['Apparel', 'Accessories']),
            'description' => fake()->sentence(12),
            'image_key' => fake()->randomElement(['room-dressing', 'heritage-001-front']),
            'status' => fake()->randomElement(['coming_soon', 'available']),
            'sort_order' => fake()->numberBetween(0, 10),
            'is_published' => true,
        ];
    }
}
