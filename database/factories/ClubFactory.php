<?php

namespace Database\Factories;

use App\Enums\ClubStatus;
use App\Enums\CornerPipelineStatus;
use App\Enums\SessionSport;
use App\Models\Club;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Club> */
class ClubFactory extends Factory
{
    public function definition(): array
    {
        $clubs = [
            ['name' => 'Padel 2000 Antwerp', 'street' => 'Riverside 3', 'postal_code' => '2050', 'city' => 'Antwerpen'],
            ['name' => 'TC Kerkhoven', 'street' => 'Kerkhovenlaan 12', 'postal_code' => '3000', 'city' => 'Leuven'],
            ['name' => 'Padel Ganda', 'street' => 'Havenlaan 88', 'postal_code' => '9000', 'city' => 'Gent'],
            ['name' => 'Royal Brussels Padel', 'street' => 'Avenue Louise 210', 'postal_code' => '1050', 'city' => 'Brussel'],
            ['name' => 'Meuse Tennis Club', 'street' => 'Quai de Meuse 4', 'postal_code' => '4000', 'city' => 'Luik'],
        ];

        $club = $clubs[array_rand($clubs)];
        $name = $club['name'].' '.fake()->unique()->numberBetween(1, 9999);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'sports' => [SessionSport::Padel->value, SessionSport::Tennis->value],
            'street' => $club['street'],
            'postal_code' => $club['postal_code'],
            'city' => $club['city'],
            'country' => 'BE',
            'lat' => null,
            'lng' => null,
            'website' => null,
            'phone' => null,
            'image_path' => null,
            'status' => ClubStatus::Approved,
            'is_partner' => false,
            'is_session_venue' => true,
            'show_on_corner_page' => false,
            'corner_pipeline_status' => null,
            'has_corner' => false,
            'corner_published' => false,
            'corner_title' => null,
            'corner_body' => null,
            'corner_location' => null,
            'sort_order' => 0,
            'submitted_by_id' => null,
            'approved_by_id' => null,
            'approved_at' => now(),
            'merged_into_id' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => ClubStatus::Pending,
            'submitted_by_id' => User::factory(),
            'approved_at' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => ClubStatus::Rejected,
            'approved_at' => null,
        ]);
    }

    public function partner(): static
    {
        return $this->state(fn (): array => ['is_partner' => true]);
    }

    public function cornerPage(): static
    {
        return $this->state(fn (): array => [
            'show_on_corner_page' => true,
            'corner_pipeline_status' => CornerPipelineStatus::Open,
            'is_session_venue' => false,
        ]);
    }

    public function publishedCorner(): static
    {
        return $this->state(fn (): array => [
            'has_corner' => true,
            'corner_published' => true,
            'corner_title' => 'Club Corner',
            'corner_body' => 'Founding Club Corner',
            'corner_location' => 'Antwerpen, België',
            'lat' => 51.2194,
            'lng' => 4.4025,
        ]);
    }

    public function comingSoonCorner(): static
    {
        return $this->state(fn (): array => [
            'has_corner' => true,
            'corner_published' => true,
            'corner_title' => 'Coming soon',
            'corner_body' => 'Binnenkort',
            'corner_location' => 'Amsterdam, Nederland',
            'lat' => null,
            'lng' => null,
        ]);
    }
}
