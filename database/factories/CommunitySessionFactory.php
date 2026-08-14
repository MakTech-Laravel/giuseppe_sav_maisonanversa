<?php

namespace Database\Factories;

use App\Models\CommunitySession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommunitySession> */
class CommunitySessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'host_id' => User::factory(),
            'starts_at' => now()->addDays(7),
            'location' => 'Antwerp',
            'capacity' => 4,
            'level' => 'open',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
