<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductFaq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductFaq>
 */
class ProductFaqFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'question' => fake()->sentence().'?',
            'answer' => fake()->paragraph(),
            'sort_order' => 0,
            'is_published' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'is_published' => false,
        ]);
    }
}
