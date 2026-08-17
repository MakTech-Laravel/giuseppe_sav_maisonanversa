<?php

namespace Database\Factories;

use App\Models\CommunityCourt;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommunityCourt> */
class CommunityCourtFactory extends Factory
{
    protected $model = CommunityCourt::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'body' => fake()->paragraph(),
            'location' => fake()->city().', België',
            'lat' => fake()->randomFloat(7, 50.8, 51.3),
            'lng' => fake()->randomFloat(7, 4.0, 4.6),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_published' => false,
        ]);
    }

    public function comingSoon(): static
    {
        return $this->state(fn (array $attributes): array => [
            'lat' => null,
            'lng' => null,
        ]);
    }
}
