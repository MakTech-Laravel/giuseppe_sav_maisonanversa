<?php

namespace Database\Factories;

use App\Models\CommunityLike;
use App\Models\CommunityPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommunityLike> */
class CommunityLikeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'community_post_id' => CommunityPost::factory(),
            'user_id' => User::factory(),
        ];
    }
}
