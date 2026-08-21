<?php

namespace Database\Seeders;

use App\Models\DressingItem;
use Illuminate\Database\Seeder;

class DressingItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['slug' => 'padel-polo', 'name' => 'Padel Polo', 'category' => 'Apparel', 'image_key' => 'room-dressing', 'status' => 'coming_soon'],
            ['slug' => 'court-short', 'name' => 'Court Short', 'category' => 'Apparel', 'image_key' => 'room-dressing', 'status' => 'coming_soon'],
            ['slug' => 'warm-up-jacket', 'name' => 'Warm-up Jacket', 'category' => 'Apparel', 'image_key' => 'room-dressing', 'status' => 'coming_soon'],
            ['slug' => 'court-cap', 'name' => 'Court Cap', 'category' => 'Accessories', 'image_key' => 'room-dressing', 'status' => 'coming_soon'],
            ['slug' => 'sport-handdoek', 'name' => 'Sport Handdoek', 'category' => 'Accessories', 'image_key' => 'room-dressing', 'status' => 'coming_soon'],
            ['slug' => 'padel-grip', 'name' => 'Padel Grip', 'category' => 'Accessories', 'image_key' => 'room-dressing', 'status' => 'coming_soon'],
        ];

        foreach ($rows as $sortOrder => $row) {
            DressingItem::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    ...$row,
                    'sort_order' => $sortOrder,
                    'is_published' => true,
                ],
            );
        }
    }
}
