<?php

namespace Database\Factories;

use App\Enums\RegisterVisibility;
use App\Models\FoundingCircleRegisterEntry;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FoundingCircleRegisterEntry>
 */
class FoundingCircleRegisterEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'order_id' => null,
            'name' => fake()->name(),
            'edition_number' => fake()->unique()->numberBetween(1, 100),
            'joined_at' => now(),
            'register_visibility' => RegisterVisibility::Private,
            'register_consent_at' => null,
            'register_hidden_by_admin' => false,
        ];
    }
}
