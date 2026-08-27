<?php

namespace Database\Factories;

use App\Enums\ProductSectionKey;
use App\Models\Product;
use App\Models\ProductSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductSection>
 */
class ProductSectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = fake()->randomElement(ProductSectionKey::cases());

        return [
            'product_id' => Product::factory(),
            'key' => $key->value,
            'eyebrow' => fake()->words(2, true),
            'heading' => fake()->sentence(3),
            'subheading' => fake()->sentence(4),
            'intro' => fake()->paragraph(),
            'image_path' => null,
            'image_key' => null,
            'is_visible' => true,
            'sort_order' => $key->defaultSortOrder(),
        ];
    }

    public function forKey(ProductSectionKey $key): static
    {
        return $this->state(fn (): array => [
            'key' => $key->value,
            'sort_order' => $key->defaultSortOrder(),
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn (): array => [
            'is_visible' => false,
        ]);
    }
}
