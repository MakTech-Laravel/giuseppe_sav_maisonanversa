<?php

use App\Enums\ClubStatus;
use App\Enums\CornerPipelineStatus;
use App\Enums\SessionSport;
use App\Models\Club;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

/**
 * Align Club Corner network tiles with the client PDF city list:
 * Antwerp, Ghent, Brussels, Amsterdam, Paris (all OPEN). Remove Hamburg / Rotterdam
 * from the corner page. The sixth "Your city?" tile is rendered in the frontend.
 */
return new class extends Migration
{
    /**
     * @var list<array{name: string, city: string, country: string, sort_order: int, street?: string|null, postal_code?: string|null, lat?: float|null, lng?: float|null}>
     */
    private array $network = [
        [
            'name' => 'Padel 2000 Antwerp',
            'city' => 'Antwerp',
            'country' => 'BE',
            'sort_order' => 0,
            'street' => 'Riverside 3',
            'postal_code' => '2050',
            'lat' => 51.2194,
            'lng' => 4.4025,
        ],
        [
            'name' => 'Padel Ganda',
            'city' => 'Ghent',
            'country' => 'BE',
            'sort_order' => 1,
            'street' => 'Havenlaan 88',
            'postal_code' => '9000',
            'lat' => 51.0543,
            'lng' => 3.7174,
        ],
        [
            'name' => 'Royal Brussels Padel',
            'city' => 'Brussels',
            'country' => 'BE',
            'sort_order' => 2,
            'street' => 'Avenue Louise 210',
            'postal_code' => '1050',
            'lat' => 50.8503,
            'lng' => 4.3517,
        ],
        [
            'name' => 'Amsterdam Padel Club',
            'city' => 'Amsterdam',
            'country' => 'NL',
            'sort_order' => 3,
            'street' => 'Sportpark Sloten 1',
            'postal_code' => '1066',
            'lat' => null,
            'lng' => null,
        ],
        [
            'name' => 'Paris Club Corner',
            'city' => 'Paris',
            'country' => 'FR',
            'sort_order' => 4,
            'street' => null,
            'postal_code' => null,
            'lat' => null,
            'lng' => null,
        ],
    ];

    public function up(): void
    {
        Club::query()
            ->where(function ($query): void {
                $query->whereIn('city', ['Hamburg', 'Rotterdam', 'Get in touch'])
                    ->orWhereIn('name', ['Hamburg', 'Get in touch', 'Padel Rotterdam Kralingen']);
            })
            ->update([
                'show_on_corner_page' => false,
                'corner_pipeline_status' => null,
            ]);

        // Avoid seeding marketing clubs into RefreshDatabase feature tests.
        if (app()->runningUnitTests()) {
            return;
        }

        Club::query()->where('show_on_corner_page', true)->update([
            'show_on_corner_page' => false,
        ]);

        $padel = SessionSport::Padel->value;

        foreach ($this->network as $row) {
            $slug = Str::slug($row['name']);

            $club = Club::query()->where('slug', $slug)->first()
                ?? Club::query()->where('name', $row['name'])->first();

            $payload = [
                'name' => $row['name'],
                'slug' => $slug,
                'street' => $row['street'] ?? null,
                'postal_code' => $row['postal_code'] ?? null,
                'city' => $row['city'],
                'country' => $row['country'],
                'sports' => [$padel],
                'status' => ClubStatus::Approved,
                'approved_at' => now(),
                'is_partner' => true,
                'is_session_venue' => true,
                'show_on_corner_page' => true,
                'corner_pipeline_status' => CornerPipelineStatus::Open,
                'has_corner' => true,
                'corner_published' => true,
                'sort_order' => $row['sort_order'],
                'lat' => $row['lat'] ?? null,
                'lng' => $row['lng'] ?? null,
            ];

            if ($club === null) {
                Club::withoutEvents(fn () => Club::query()->create($payload));

                continue;
            }

            Club::withoutEvents(function () use ($club, $payload): void {
                $club->update($payload);
            });
        }
    }

    public function down(): void
    {
        // Irreversible content sync — re-seed from ClubSeeder if needed.
    }
};
