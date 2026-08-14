<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\Stripe\StripeCatalog;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductObserver
{
    /**
     * After a local product is created, provision Stripe Product + Price IDs.
     */
    public function created(Product $product): void
    {
        $this->syncSafely($product);
    }

    /**
     * Keep Stripe in sync when the local name or amount changes.
     */
    public function updated(Product $product): void
    {
        if (! $product->wasChanged(['name', 'amount', 'currency'])) {
            return;
        }

        $this->syncSafely($product);
    }

    private function syncSafely(Product $product): void
    {
        try {
            app(StripeCatalog::class)->sync($product);
        } catch (Throwable $exception) {
            Log::error('Stripe catalog sync failed.', [
                'product_id' => $product->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
