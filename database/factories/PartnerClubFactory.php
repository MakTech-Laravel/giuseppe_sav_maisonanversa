<?php

namespace Database\Factories;

use App\Models\PartnerClub;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartnerClub>
 */
class PartnerClubFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'city' => fake()->city(),
            'country' => fake()->country(),
            'status' => fake()->randomElement(['in_discussion', 'open', 'active']),
            'sort_order' => fake()->numberBetween(0, 10),
            'is_published' => true,
        ];
    }
}
