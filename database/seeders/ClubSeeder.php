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
                'city' => 'Antwerpen',
                'country' => 'BE',
                'sports' => [$padel],
                'is_partner' => true,
                'is_session_venue' => true,
                'show_on_corner_page' => true,
                'corner_pipeline_status' => CornerPipelineStatus::InDiscussion,
                'has_corner' => true,
                'corner_published' => true,
                'corner_title' => 'Padel Club Antwerpen',
                'corner_body' => "Founding Club Corner · Demo racket beschikbaar\n\n8 banen · 24 MA-leden · 4 sessies/week",
                'corner_location' => 'Antwerpen, België',
                'lat' => 51.2194,
                'lng' => 4.4025,
                'sort_order' => 0,
            ],
            [
                'name' => 'Riverside Padel Antwerp',
                'street' => 'Scheldekaai 22',
                'postal_code' => '2000',
                'city' => 'Antwerpen',
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
                'city' => 'Gent',
                'country' => 'BE',
                'sports' => [$padel, $tennis],
                'is_partner' => false,
                'is_session_venue' => true,
            ],
            [
                'name' => 'Royal Brussels Padel',
                'street' => 'Avenue Louise 210',
                'postal_code' => '1050',
                'city' => 'Brussel',
                'country' => 'BE',
                'sports' => [$padel],
                'is_partner' => true,
                'is_session_venue' => true,
                'show_on_corner_page' => true,
                'corner_pipeline_status' => CornerPipelineStatus::Open,
                'has_corner' => true,
                'corner_published' => true,
                'corner_title' => 'Padel One Brussels',
                'corner_body' => "Club Corner · Heritage-paspoorten beschikbaar\n\n6 banen · 18 MA-leden · 2 sessies/week",
                'corner_location' => 'Brussel, België',
                'lat' => 50.8503,
                'lng' => 4.3517,
                'sort_order' => 1,
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
                'show_on_corner_page' => true,
                'corner_pipeline_status' => CornerPipelineStatus::Open,
                'has_corner' => true,
                'corner_published' => true,
                'corner_title' => 'Padel Rotterdam',
                'corner_body' => 'Binnenkort · Q2 2027',
                'corner_location' => 'Rotterdam, Nederland',
                'lat' => null,
                'lng' => null,
                'sort_order' => 3,
            ],
            [
                'name' => 'Amsterdam Padel Club',
                'street' => 'Sportpark Sloten 1',
                'postal_code' => '1066',
                'city' => 'Amsterdam',
                'country' => 'NL',
                'sports' => [$padel, $tennis],
                'is_partner' => false,
                'is_session_venue' => true,
                'show_on_corner_page' => true,
                'corner_pipeline_status' => CornerPipelineStatus::Open,
                'has_corner' => true,
                'corner_published' => true,
                'corner_title' => 'Amsterdam Padel Club',
                'corner_body' => 'Binnenkort · Q2 2027',
                'corner_location' => 'Amsterdam, Nederland',
                'lat' => null,
                'lng' => null,
                'sort_order' => 2,
            ],
            [
                'name' => 'Hamburg',
                'street' => null,
                'postal_code' => null,
                'city' => 'Hamburg',
                'country' => 'DE',
                'sports' => [$padel],
                'is_partner' => false,
                'is_session_venue' => false,
                'show_on_corner_page' => true,
                'corner_pipeline_status' => CornerPipelineStatus::Open,
                'sort_order' => 4,
            ],
            [
                'name' => 'Neem contact op',
                'street' => null,
                'postal_code' => null,
                'city' => 'Neem contact op',
                'country' => 'BE',
                'sports' => [$padel],
                'is_partner' => false,
                'is_session_venue' => false,
                'show_on_corner_page' => true,
                'corner_pipeline_status' => CornerPipelineStatus::Active,
                'sort_order' => 5,
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
