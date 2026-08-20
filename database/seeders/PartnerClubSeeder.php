<?php

namespace Database\Seeders;

use App\Models\PartnerClub;
use Illuminate\Database\Seeder;

class PartnerClubSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['city' => 'Antwerpen', 'country' => 'België', 'status' => 'in_discussion'],
            ['city' => 'Brussel', 'country' => 'België', 'status' => 'open'],
            ['city' => 'Amsterdam', 'country' => 'Nederland', 'status' => 'open'],
            ['city' => 'Rotterdam', 'country' => 'Nederland', 'status' => 'open'],
            ['city' => 'Hamburg', 'country' => 'Duitsland', 'status' => 'open'],
            ['city' => 'Neem contact op', 'country' => 'Uw stad?', 'status' => 'active'],
        ];

        foreach ($rows as $sortOrder => $row) {
            PartnerClub::query()->updateOrCreate(
                ['city' => $row['city'], 'country' => $row['country']],
                [
                    'status' => $row['status'],
                    'sort_order' => $sortOrder,
                    'is_published' => true,
                ],
            );
        }
    }
}
