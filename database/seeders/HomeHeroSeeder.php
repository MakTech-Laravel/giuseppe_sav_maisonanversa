<?php

namespace Database\Seeders;

use App\Models\HomeHero;
use Illuminate\Database\Seeder;

class HomeHeroSeeder extends Seeder
{
    /**
     * Previous brand lines that should be upgraded to the official English line.
     *
     * @var list<string>
     */
    private const LEGACY_TAGLINES = [
        'European Heritage Sports and Lifestyle House · Gebouwd voor generaties.',
        'European Heritage Sports and Lifestyle House',
    ];

    /**
     * Seed the singleton homepage hero with the current Dutch copy.
     */
    public function run(): void
    {
        if (! HomeHero::query()->exists()) {
            HomeHero::query()->create(HomeHero::defaults());

            return;
        }

        $brandTagline = HomeHero::defaults()['tagline'];

        HomeHero::query()
            ->whereIn('tagline', self::LEGACY_TAGLINES)
            ->update(['tagline' => $brandTagline]);
    }
}
