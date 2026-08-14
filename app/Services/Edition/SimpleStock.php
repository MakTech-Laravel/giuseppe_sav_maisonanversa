<?php

namespace App\Services\Edition;

use App\Enums\ProductType;
use App\Exceptions\EditionSoldOutException;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class SimpleStock
{
    public function reserve(Product $product): void
    {
        if ($product->type !== ProductType::Simple) {
            return;
        }

        DB::transaction(function () use ($product): void {
            $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            if ((int) $locked->stock_quantity < 1) {
                throw new EditionSoldOutException;
            }

            $locked->decrement('stock_quantity');
            app(EditionInventory::class)->bust($locked);
        });
    }

    public function release(Product $product): void
    {
        if ($product->type !== ProductType::Simple) {
            return;
        }

        $product->increment('stock_quantity');
        app(EditionInventory::class)->bust($product->fresh());
    }
}
