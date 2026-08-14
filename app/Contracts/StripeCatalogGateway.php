<?php

namespace App\Contracts;

interface StripeCatalogGateway
{
    /**
     * @param  array<string, string>  $metadata
     */
    public function createProduct(string $name, array $metadata = []): string;

    /**
     * Update the Stripe product name. Returns false when the product no longer exists.
     */
    public function updateProduct(string $stripeProductId, string $name): bool;

    public function createPrice(string $stripeProductId, int $unitAmount, string $currency): string;

    public function archivePrice(string $stripePriceId): void;

    public function setDefaultPrice(string $stripeProductId, string $stripePriceId): void;

    /**
     * Stripe Price unit_amount in cents, or null when the price is missing.
     */
    public function retrievePriceUnitAmount(string $stripePriceId): ?int;
}
