<?php

namespace Database\Factories;

use App\Models\CommunityEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommunityEvent> */
class CommunityEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'starts_at' => now()->addMonth(),
            'location' => 'Antwerp',
            'capacity' => 20,
        ];
    }
}
