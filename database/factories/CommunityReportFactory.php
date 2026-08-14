<?php

namespace Database\Factories;

use App\Models\CommunityPost;
use App\Models\CommunityReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommunityReport> */
class CommunityReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'community_post_id' => CommunityPost::factory(),
            'reporter_id' => User::factory(),
            'reason' => 'inappropriate',
            'status' => 'open',
        ];
    }
}
