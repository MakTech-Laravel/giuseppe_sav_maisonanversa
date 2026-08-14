<?php

namespace App\Services\Stripe;

use App\Contracts\StripeCatalogGateway;
use Laravel\Cashier\Cashier;
use Stripe\Exception\InvalidRequestException;

class CashierStripeCatalogGateway implements StripeCatalogGateway
{
    /**
     * @param  array<string, string>  $metadata
     */
    public function createProduct(string $name, array $metadata = []): string
    {
        $product = Cashier::stripe()->products->create([
            'name' => $name,
            'metadata' => $metadata,
        ]);

        return $product->id;
    }

    public function updateProduct(string $stripeProductId, string $name): bool
    {
        try {
            Cashier::stripe()->products->update($stripeProductId, [
                'name' => $name,
            ]);
        } catch (InvalidRequestException) {
            return false;
        }

        return true;
    }

    public function createPrice(string $stripeProductId, int $unitAmount, string $currency): string
    {
        $price = Cashier::stripe()->prices->create([
            'product' => $stripeProductId,
            'currency' => strtolower($currency),
            'unit_amount' => $unitAmount,
        ]);

        return $price->id;
    }

    public function archivePrice(string $stripePriceId): void
    {
        try {
            Cashier::stripe()->prices->update($stripePriceId, [
                'active' => false,
            ]);
        } catch (InvalidRequestException) {
            // Already gone or never existed.
        }
    }

    public function setDefaultPrice(string $stripeProductId, string $stripePriceId): void
    {
        try {
            Cashier::stripe()->products->update($stripeProductId, [
                'default_price' => $stripePriceId,
            ]);
        } catch (InvalidRequestException) {
            // Product may have been removed; checkout will recreate on the next sync.
        }
    }

    public function retrievePriceUnitAmount(string $stripePriceId): ?int
    {
        try {
            $price = Cashier::stripe()->prices->retrieve($stripePriceId);
        } catch (InvalidRequestException) {
            return null;
        }

        return $price->unit_amount !== null ? (int) $price->unit_amount : null;
    }
}
