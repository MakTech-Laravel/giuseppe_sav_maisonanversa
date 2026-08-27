<?php

namespace Database\Factories;

use App\Enums\ClubStatus;
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
}
