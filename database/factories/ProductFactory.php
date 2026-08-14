<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'amount' => '249.00',
            'currency' => 'eur',
        ];
    }

    public function founding(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Heritage No.001 — Founding Edition',
            'slug' => Product::FOUNDING_SLUG,
            'amount' => '249.00',
            'currency' => 'eur',
        ]);
    }
}
