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
        $comments = [
            'Count me in if a court opens.',
            'Excellent session. I would join the next morning slot as well.',
            'The Antwerp club works perfectly for me.',
            'Happy to bring a guest if that helps fill the group.',
        ];

        return [
            'community_post_id' => CommunityPost::factory(),
            'author_id' => User::factory(),
            'body' => $comments[array_rand($comments)],
        ];
    }
}
