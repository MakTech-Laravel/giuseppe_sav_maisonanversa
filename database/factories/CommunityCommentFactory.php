<?php

namespace Database\Factories;

use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommunityComment> */
class CommunityCommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'community_post_id' => CommunityPost::factory(),
            'author_id' => User::factory(),
            'body' => fake()->sentence(),
        ];
    }
}
