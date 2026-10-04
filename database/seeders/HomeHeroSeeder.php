<?php

namespace Database\Seeders;

use App\Models\HomeHero;
use Illuminate\Database\Seeder;

class HomeHeroSeeder extends Seeder
{
    /**
     * Seed the singleton homepage hero with the current Dutch copy.
     */
    public function run(): void
    {
        if (HomeHero::query()->exists()) {
            return;
        }

        HomeHero::query()->create(HomeHero::defaults());
    }
}
