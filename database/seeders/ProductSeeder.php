<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed the Founding Edition catalog product. Stripe IDs are created on
     * first purchase or when Stripe keys are present after create/update.
     */
    public function run(): void
    {
        Product::query()->firstOrCreate(
            ['slug' => Product::FOUNDING_SLUG],
            [
                'name' => 'Heritage No.001 — Founding Edition',
                'type' => ProductType::LimitedEdition,
                'amount' => '249.00',
                'currency' => 'eur',
                'edition_total' => 100,
                'archive_edition_numbers' => [1],
                'is_published' => true,
                'grants_founding_circle' => true,
                'expected_delivery_label' => 'Q1 2027 — ONDER VOORBEHOUD VAN PRODUCTIE',
                'status' => ProductStatus::Active,
                'sort_order' => 0,
            ],
        );
    }
}
