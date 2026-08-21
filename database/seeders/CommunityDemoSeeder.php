<?php

namespace Database\Seeders;

use App\Models\CommunityEvent;
use App\Models\CommunityPost;
use App\Models\CommunitySession;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommunityDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $host = User::query()->first() ?? User::factory()->create([
            'name' => 'Founding Host',
            'email' => 'founding.host@example.com',
        ]);

        CommunityPost::factory()->count(2)->create([
            'author_id' => $host->id,
            'status' => 'published',
        ]);

        CommunityEvent::factory()->create([
            'title' => 'Founding Circle Padel Morning',
            'location' => 'Padel Club Antwerpen',
            'starts_at' => now()->addWeek(),
        ]);

        CommunitySession::factory()->create([
            'host_id' => $host->id,
            'location' => 'Padel Club Antwerpen',
            'starts_at' => now()->addDays(3),
        ]);
    }
}
