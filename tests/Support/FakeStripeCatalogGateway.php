<?php

namespace Tests\Support;

use App\Contracts\StripeCatalogGateway;

class FakeStripeCatalogGateway implements StripeCatalogGateway
{
    /**
     * @var array<string, string>
     */
    public array $products = [];

    /**
     * @var array<string, array{product: string, amount: int, currency: string}>
     */
    public array $prices = [];

    /**
     * @var list<string>
     */
    public array $archived = [];

    private int $productSequence = 0;

    private int $priceSequence = 0;

    /**
     * @param  array<string, string>  $metadata
     */
    public function createProduct(string $name, array $metadata = []): string
    {
        $id = 'prod_fake_'.(++$this->productSequence);
        $this->products[$id] = $name;

        return $id;
    }

    public function updateProduct(string $stripeProductId, string $name): bool
    {
        if (! array_key_exists($stripeProductId, $this->products)) {
            return false;
        }

        $this->products[$stripeProductId] = $name;

        return true;
    }

    public function createPrice(string $stripeProductId, int $unitAmount, string $currency): string
    {
        $id = 'price_fake_'.(++$this->priceSequence);
        $this->prices[$id] = [
            'product' => $stripeProductId,
            'amount' => $unitAmount,
            'currency' => $currency,
        ];

        return $id;
    }

    public function archivePrice(string $stripePriceId): void
    {
        $this->archived[] = $stripePriceId;
        unset($this->prices[$stripePriceId]);
    }

    public function setDefaultPrice(string $stripeProductId, string $stripePriceId): void
    {
        //
    }

    public function retrievePriceUnitAmount(string $stripePriceId): ?int
    {
        if (! array_key_exists($stripePriceId, $this->prices)) {
            return null;
        }

        return $this->prices[$stripePriceId]['amount'];
    }
}
