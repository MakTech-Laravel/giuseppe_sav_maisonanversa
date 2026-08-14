<?php

namespace Database\Seeders;

use App\Enums\ProductType;
use App\Models\Product;
use App\Services\Edition\LimitedEditionLedger;
use Illuminate\Database\Seeder;

class EditionPieceSeeder extends Seeder
{
    /**
     * Provision numbered pieces for every limited-edition product.
     */
    public function run(): void
    {
        $ledger = app(LimitedEditionLedger::class);

        Product::query()
            ->where('type', ProductType::LimitedEdition)
            ->get()
            ->each(fn (Product $product) => $ledger->sync($product));
    }
}
