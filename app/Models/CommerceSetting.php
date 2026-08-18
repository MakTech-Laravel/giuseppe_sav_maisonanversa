<?php

namespace App\Models;

use App\Models\Concerns\TranslatesWithDeepL;
use Illuminate\Database\Eloquent\Model;

class CommerceSetting extends Model
{
    use TranslatesWithDeepL;

    /**
     * @var list<string>
     */
    protected array $translatable = [
        'default_expected_delivery_label',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'shipping_estimate_min',
        'shipping_estimate_max',
        'shipping_eu_included',
        'default_expected_delivery_label',
        'prices_include_tax',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'shipping_estimate_min' => 'decimal:2',
            'shipping_estimate_max' => 'decimal:2',
            'shipping_eu_included' => 'boolean',
            'prices_include_tax' => 'boolean',
        ];
    }

    public static function current(): self
    {
        $existing = static::query()->first();

        if ($existing !== null) {
            return $existing;
        }

        return static::query()->create([
            'shipping_estimate_min' => '12.00',
            'shipping_estimate_max' => '18.00',
            'shipping_eu_included' => true,
            'default_expected_delivery_label' => 'Q1 2027 — subject to production',
            'prices_include_tax' => true,
        ]);
    }

    /**
     * Display-only commerce copy. Shipping is not a Stripe line item.
     *
     * @return array{
     *     shippingEstimateMin: string,
     *     shippingEstimateMax: string,
     *     shippingEuIncluded: bool,
     *     defaultExpectedDeliveryLabel: string|null,
     *     pricesIncludeTax: bool
     * }
     */
    public function toShare(): array
    {
        return [
            'shippingEstimateMin' => (string) $this->shipping_estimate_min,
            'shippingEstimateMax' => (string) $this->shipping_estimate_max,
            'shippingEuIncluded' => $this->shipping_eu_included,
            'defaultExpectedDeliveryLabel' => $this->translated('default_expected_delivery_label'),
            'pricesIncludeTax' => $this->prices_include_tax,
        ];
    }
}
