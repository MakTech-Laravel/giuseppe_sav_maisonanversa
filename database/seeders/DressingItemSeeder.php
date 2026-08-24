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
            ['slug' => 'padel-polo', 'name' => 'Padel Polo', 'category' => 'Apparel', 'description' => 'Ademende technische polo in chocoladebruin, gesneden voor volledige bewegingsvrijheid op de baan.', 'image_key' => 'room-dressing', 'status' => 'coming_soon'],
            ['slug' => 'court-short', 'name' => 'Court Short', 'category' => 'Apparel', 'description' => 'Lichtgewicht short met stretch inzet en verborgen zakken, ontworpen voor snelle richtingswisselingen.', 'image_key' => 'room-dressing', 'status' => 'coming_soon'],
            ['slug' => 'warm-up-jacket', 'name' => 'Warm-up Jacket', 'category' => 'Apparel', 'description' => 'Antiek gouden warming-up jack met matte afwerking, voor en na de wedstrijd.', 'image_key' => 'room-dressing', 'status' => 'coming_soon'],
            ['slug' => 'court-cap', 'name' => 'Court Cap', 'category' => 'Accessories', 'description' => 'Gestructureerde pet in vintage crème met gegraveerd embleem.', 'image_key' => 'room-dressing', 'status' => 'coming_soon'],
            ['slug' => 'sport-handdoek', 'name' => 'Sport Handdoek', 'category' => 'Accessories', 'description' => 'Zware katoenen handdoek met geborduurd wapenschild, sneldrogend.', 'image_key' => 'room-dressing', 'status' => 'coming_soon'],
            ['slug' => 'padel-grip', 'name' => 'Padel Grip', 'category' => 'Accessories', 'description' => 'Premium overgrip met subtiele textuur voor optimale controle.', 'image_key' => 'room-dressing', 'status' => 'coming_soon'],
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
