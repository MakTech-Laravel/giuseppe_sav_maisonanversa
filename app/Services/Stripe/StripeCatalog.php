<?php

namespace App\Services\Stripe;

use App\Contracts\StripeCatalogGateway;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class StripeCatalog
{
    public function __construct(private StripeCatalogGateway $gateway) {}

    /**
     * Ensure the local product has a Stripe Product and Price, creating or
     * replacing them when missing or when the amount has changed.
     */
    public function sync(Product $product): Product
    {
        if (! filled(config('cashier.secret'))) {
            return $product;
        }

        return DB::transaction(function () use ($product): Product {
            $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            $this->ensureStripeProduct($locked);
            $this->ensureStripePrice($locked);

            $locked->saveQuietly();

            return $locked;
        });
    }

    private function ensureStripeProduct(Product $product): void
    {
        if (blank($product->stripe_product_id)) {
            $product->stripe_product_id = $this->gateway->createProduct(
                $product->name,
                $this->metadata($product),
            );

            return;
        }

        if ($this->gateway->updateProduct($product->stripe_product_id, $product->name)) {
            return;
        }

        $product->stripe_product_id = $this->gateway->createProduct(
            $product->name,
            $this->metadata($product),
        );
        $product->stripe_price_id = null;
    }

    private function ensureStripePrice(Product $product): void
    {
        $currentAmount = filled($product->stripe_price_id)
            ? $this->gateway->retrievePriceUnitAmount($product->stripe_price_id)
            : null;

        $unitAmount = $product->amountInCents();

        if ($currentAmount !== null && $currentAmount === $unitAmount) {
            return;
        }

        $oldPriceId = $product->stripe_price_id;

        $newPriceId = $this->gateway->createPrice(
            $product->stripe_product_id,
            $unitAmount,
            $product->currency,
        );

        $this->gateway->setDefaultPrice($product->stripe_product_id, $newPriceId);

        if (filled($oldPriceId) && $oldPriceId !== $newPriceId) {
            $this->gateway->archivePrice($oldPriceId);
        }

        $product->stripe_price_id = $newPriceId;
    }

    /**
     * @return array<string, string>
     */
    private function metadata(Product $product): array
    {
        return [
            'local_product_id' => (string) $product->id,
            'slug' => $product->slug,
        ];
    }
}
