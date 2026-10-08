<?php

namespace Database\Seeders;

use App\Models\StoryPage;
use Illuminate\Database\Seeder;

class StoryPageSeeder extends Seeder
{
    /**
     * Seed the singleton story page with the current Dutch copy.
     */
    public function run(): void
    {
        if (StoryPage::query()->exists()) {
            return;
        }

        StoryPage::query()->create(StoryPage::defaults());
    }
}
