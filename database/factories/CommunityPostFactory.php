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
        $posts = [
            'Open training this Thursday at Padel Club Antwerpen. Two spots still available for members who want a faster evening session.',
            'Morning rally at the riverside courts was excellent today. We are collecting preferred times for the next Founding Circle session.',
            'Small note from the house: bring indoor layers for the late session. The terrace opens after play for coffee and recovery.',
        ];

        return [
            'author_id' => User::factory(),
            'content' => $posts[array_rand($posts)],
            'is_official' => false,
            'status' => 'published',
        ];
    }

    public function official(): static
    {
        return $this->state(fn (): array => ['is_official' => true]);
    }
}
