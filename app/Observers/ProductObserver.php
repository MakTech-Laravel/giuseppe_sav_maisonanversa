<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\Edition\LimitedEditionLedger;
use App\Services\Stripe\StripeCatalog;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductObserver
{
    /**
     * After a local product is created, provision Stripe IDs and numbered stock.
     */
    public function created(Product $product): void
    {
        $this->syncLedgerSafely($product);
        $this->syncStripeSafely($product);
    }

    /**
     * Keep Stripe and the edition ledger in sync when catalog fields change.
     */
    public function updated(Product $product): void
    {
        if ($product->wasChanged(['edition_total', 'archive_edition_numbers', 'type'])) {
            $this->syncLedgerSafely($product);
        }

        if ($product->wasChanged(['name', 'amount', 'currency'])) {
            $this->syncStripeSafely($product);
        }
    }

    private function syncLedgerSafely(Product $product): void
    {
        try {
            app(LimitedEditionLedger::class)->sync($product);
        } catch (Throwable $exception) {
            Log::error('Limited edition ledger sync failed.', [
                'product_id' => $product->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function syncStripeSafely(Product $product): void
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
