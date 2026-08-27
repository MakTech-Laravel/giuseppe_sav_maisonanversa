<?php

namespace Database\Seeders;

use App\Enums\ClubStatus;
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
            ['name' => 'Padel 2000 Antwerp', 'street' => 'Riverside 3', 'postal_code' => '2050', 'city' => 'Antwerpen', 'sports' => [$padel], 'is_partner' => true],
            ['name' => 'Riverside Padel Antwerp', 'street' => 'Scheldekaai 22', 'postal_code' => '2000', 'city' => 'Antwerpen', 'sports' => [$padel], 'is_partner' => true],
            ['name' => 'TC Kerkhoven', 'street' => 'Kerkhovenlaan 12', 'postal_code' => '3000', 'city' => 'Leuven', 'sports' => [$tennis], 'is_partner' => false],
            ['name' => 'Padel Ganda', 'street' => 'Havenlaan 88', 'postal_code' => '9000', 'city' => 'Gent', 'sports' => [$padel, $tennis], 'is_partner' => false],
            ['name' => 'Royal Brussels Padel', 'street' => 'Avenue Louise 210', 'postal_code' => '1050', 'city' => 'Brussel', 'sports' => [$padel], 'is_partner' => true],
            ['name' => 'Meuse Tennis Club', 'street' => 'Quai de Meuse 4', 'postal_code' => '4000', 'city' => 'Luik', 'sports' => [$tennis], 'is_partner' => false],
            ['name' => 'Padel Rotterdam Kralingen', 'street' => 'Kralingseweg 150', 'postal_code' => '3062', 'city' => 'Rotterdam', 'sports' => [$padel], 'is_partner' => false],
            ['name' => 'Amsterdam Padel Club', 'street' => 'Sportpark Sloten 1', 'postal_code' => '1066', 'city' => 'Amsterdam', 'sports' => [$padel, $tennis], 'is_partner' => false],
        ];

        foreach ($clubs as $club) {
            Club::query()->updateOrCreate(
                ['slug' => Str::slug($club['name'])],
                [
                    ...$club,
                    'country' => 'BE',
                    'status' => ClubStatus::Approved,
                    'approved_at' => now(),
                ],
            );
        }
    }
}
