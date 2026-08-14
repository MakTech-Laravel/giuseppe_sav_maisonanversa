<?php

namespace Database\Factories;

use App\Enums\ProductType;
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
            'type' => ProductType::Simple,
            'amount' => '99.99',
            'currency' => 'eur',
            'stock_quantity' => 10,
            'is_published' => true,
            'grants_founding_circle' => false,
        ];
    }

    public function founding(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Heritage No.001 — Founding Edition',
            'slug' => Product::FOUNDING_SLUG,
            'type' => ProductType::LimitedEdition,
            'amount' => '249.00',
            'currency' => 'eur',
            'edition_total' => 100,
            'archive_edition_numbers' => [1],
            'stock_quantity' => null,
            'is_published' => true,
            'grants_founding_circle' => true,
            'expected_delivery_label' => 'Q1 2027 — subject to production',
        ]);
    }

    public function limitedEdition(int $total = 10): static
    {
        return $this->state(fn (): array => [
            'type' => ProductType::LimitedEdition,
            'edition_total' => $total,
            'archive_edition_numbers' => [],
            'stock_quantity' => null,
            'grants_founding_circle' => false,
        ]);
    }
}
