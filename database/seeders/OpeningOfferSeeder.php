<?php

namespace Database\Seeders;

use App\Models\OpeningOffer;
use App\Models\Product;
use Illuminate\Database\Seeder;

class OpeningOfferSeeder extends Seeder
{
    /**
     * The opening offers the founding product until an admin chooses another.
     */
    public function run(): void
    {
        $founding = Product::founding();

        if ($founding === null) {
            return;
        }

        OpeningOffer::query()->firstOrCreate(
            ['product_id' => $founding->id],
            ['is_active' => ! OpeningOffer::query()->active()->exists(), 'sort_order' => 0],
        );
    }
}
