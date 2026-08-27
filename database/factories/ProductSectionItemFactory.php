<?php

namespace Database\Factories;

use App\Models\ProductSection;
use App\Models\ProductSectionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductSectionItem>
 */
class ProductSectionItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_section_id' => ProductSection::factory(),
            'number_label' => str_pad((string) fake()->numberBetween(1, 9), 2, '0', STR_PAD_LEFT),
            'icon' => fake()->randomElement(['◆', '◇', '◈', '★', '✓']),
            'title' => fake()->words(3, true),
            'body' => fake()->sentence(),
            'image_path' => null,
            'sort_order' => 0,
        ];
    }
}
