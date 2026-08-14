<?php

namespace Database\Seeders;

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
                'amount' => '249.00',
                'currency' => 'eur',
            ],
        );
    }
}
