<?php

namespace Database\Factories;

use App\Models\CommunityPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunityPost>
 */
class CommunityPostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'author_id' => User::factory(),
            'content' => fake()->paragraph(),
            'is_official' => false,
            'status' => 'published',
        ];
    }

    public function official(): static
    {
        return $this->state(fn (): array => ['is_official' => true]);
    }
}
