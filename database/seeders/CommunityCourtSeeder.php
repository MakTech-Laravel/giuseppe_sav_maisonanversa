<?php

namespace Database\Seeders;

use App\Models\CommunityCourt;
use Illuminate\Database\Seeder;

class CommunityCourtSeeder extends Seeder
{
    public function run(): void
    {
        $courts = [
            [
                'title' => 'Padel Club Antwerpen',
                'body' => "Founding Club Corner · Demo racket beschikbaar\n\n8 courts · 24 MA leden · 4 sessies/week",
                'location' => 'Antwerpen, België',
                'lat' => 51.2194,
                'lng' => 4.4025,
                'sort_order' => 0,
                'is_published' => true,
            ],
            [
                'title' => 'Padel One Brussels',
                'body' => "Club Corner · Heritage Paspoorten beschikbaar\n\n6 courts · 18 MA leden · 2 sessies/week",
                'location' => 'Brussel, België',
                'lat' => 50.8503,
                'lng' => 4.3517,
                'sort_order' => 1,
                'is_published' => true,
            ],
            [
                'title' => 'Amsterdam Padel Club',
                'body' => 'Binnenkort · Q2 2027',
                'location' => 'Amsterdam, Nederland',
                'lat' => null,
                'lng' => null,
                'sort_order' => 2,
                'is_published' => true,
            ],
            [
                'title' => 'Padel Rotterdam',
                'body' => 'Binnenkort · Q2 2027',
                'location' => 'Rotterdam, Nederland',
                'lat' => null,
                'lng' => null,
                'sort_order' => 3,
                'is_published' => true,
            ],
        ];

        CommunityCourt::withoutEvents(function () use ($courts): void {
            foreach ($courts as $court) {
                CommunityCourt::query()->updateOrCreate(
                    ['title' => $court['title']],
                    $court,
                );
            }
        });
    }
}
