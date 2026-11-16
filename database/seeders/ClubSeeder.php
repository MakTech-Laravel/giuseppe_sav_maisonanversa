<?php

namespace Database\Seeders;

use App\Enums\ClubStatus;
use App\Enums\CornerPipelineStatus;
use App\Enums\SessionSport;
use App\Models\Club;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ClubSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $padel = SessionSport::Padel->value;
        $tennis = SessionSport::Tennis->value;

        $clubs = [
            [
                'name' => 'Padel 2000 Antwerp',
                'street' => 'Riverside 3',
                'postal_code' => '2050',
                'city' => 'Antwerp',
                'country' => 'BE',
                'sports' => [$padel],
                'is_partner' => true,
                'is_session_venue' => true,
                'show_on_corner_page' => true,
                'corner_pipeline_status' => CornerPipelineStatus::Open,
                'has_corner' => true,
                'corner_published' => true,
                'corner_title' => 'Padel Club Antwerp',
                'corner_body' => "Founding Club Corner · Demo racket available\n\n8 courts · 24 MA members · 4 sessions/week",
                'corner_location' => 'Antwerp, Belgium',
                'lat' => 51.2194,
                'lng' => 4.4025,
                'sort_order' => 0,
            ],
            [
                'name' => 'Riverside Padel Antwerp',
                'street' => 'Scheldekaai 22',
                'postal_code' => '2000',
                'city' => 'Antwerp',
                'country' => 'BE',
                'sports' => [$padel],
                'is_partner' => true,
                'is_session_venue' => true,
            ],
            [
                'name' => 'TC Kerkhoven',
                'street' => 'Kerkhovenlaan 12',
                'postal_code' => '3000',
                'city' => 'Leuven',
                'country' => 'BE',
                'sports' => [$tennis],
                'is_partner' => false,
                'is_session_venue' => true,
            ],
            [
                'name' => 'Padel Ganda',
                'street' => 'Havenlaan 88',
                'postal_code' => '9000',
                'city' => 'Ghent',
                'country' => 'BE',
                'sports' => [$padel, $tennis],
                'is_partner' => true,
                'is_session_venue' => true,
                'show_on_corner_page' => true,
                'corner_pipeline_status' => CornerPipelineStatus::Open,
                'has_corner' => true,
                'corner_published' => true,
                'corner_title' => 'Padel Ganda',
                'corner_body' => 'Club Corner · OPEN',
                'corner_location' => 'Ghent, Belgium',
                'lat' => 51.0543,
                'lng' => 3.7174,
                'sort_order' => 1,
            ],
            [
                'name' => 'Royal Brussels Padel',
                'street' => 'Avenue Louise 210',
                'postal_code' => '1050',
                'city' => 'Brussels',
                'country' => 'BE',
                'sports' => [$padel],
                'is_partner' => true,
                'is_session_venue' => true,
                'show_on_corner_page' => true,
                'corner_pipeline_status' => CornerPipelineStatus::Open,
                'has_corner' => true,
                'corner_published' => true,
                'corner_title' => 'Padel One Brussels',
                'corner_body' => "Club Corner · Heritage brochures available\n\n6 courts · 18 MA members · 2 sessions/week",
                'corner_location' => 'Brussels, Belgium',
                'lat' => 50.8503,
                'lng' => 4.3517,
                'sort_order' => 2,
            ],
            [
                'name' => 'Meuse Tennis Club',
                'street' => 'Quai de Meuse 4',
                'postal_code' => '4000',
                'city' => 'Luik',
                'country' => 'BE',
                'sports' => [$tennis],
                'is_partner' => false,
                'is_session_venue' => true,
            ],
            [
                'name' => 'Padel Rotterdam Kralingen',
                'street' => 'Kralingseweg 150',
                'postal_code' => '3062',
                'city' => 'Rotterdam',
                'country' => 'NL',
                'sports' => [$padel],
                'is_partner' => false,
                'is_session_venue' => true,
                'show_on_corner_page' => false,
            ],
            [
                'name' => 'Amsterdam Padel Club',
                'street' => 'Sportpark Sloten 1',
                'postal_code' => '1066',
                'city' => 'Amsterdam',
                'country' => 'NL',
                'sports' => [$padel, $tennis],
                'is_partner' => true,
                'is_session_venue' => true,
                'show_on_corner_page' => true,
                'corner_pipeline_status' => CornerPipelineStatus::Open,
                'has_corner' => true,
                'corner_published' => true,
                'corner_title' => 'Amsterdam Padel Club',
                'corner_body' => 'Club Corner · OPEN',
                'corner_location' => 'Amsterdam, Netherlands',
                'lat' => null,
                'lng' => null,
                'sort_order' => 3,
            ],
            [
                'name' => 'Paris Club Corner',
                'street' => null,
                'postal_code' => null,
                'city' => 'Paris',
                'country' => 'FR',
                'sports' => [$padel],
                'is_partner' => true,
                'is_session_venue' => false,
                'show_on_corner_page' => true,
                'corner_pipeline_status' => CornerPipelineStatus::Open,
                'has_corner' => true,
                'corner_published' => true,
                'corner_title' => 'Paris Club Corner',
                'corner_body' => 'Club Corner · OPEN',
                'corner_location' => 'Paris, France',
                'sort_order' => 4,
            ],
        ];

        Club::withoutEvents(function () use ($clubs): void {
            foreach ($clubs as $club) {
                Club::query()->updateOrCreate(
                    ['slug' => Str::slug($club['name'])],
                    [
                        ...$club,
                        'status' => ClubStatus::Approved,
                        'approved_at' => now(),
                        'show_on_corner_page' => $club['show_on_corner_page'] ?? false,
                        'corner_pipeline_status' => $club['corner_pipeline_status'] ?? null,
                        'has_corner' => $club['has_corner'] ?? false,
                        'corner_published' => $club['corner_published'] ?? false,
                        'corner_title' => $club['corner_title'] ?? null,
                        'corner_body' => $club['corner_body'] ?? null,
                        'corner_location' => $club['corner_location'] ?? null,
                        'lat' => $club['lat'] ?? null,
                        'lng' => $club['lng'] ?? null,
                        'sort_order' => $club['sort_order'] ?? 0,
                    ],
                );
            }
        });
    }
}
