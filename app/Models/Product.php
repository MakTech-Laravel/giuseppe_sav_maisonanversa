<?php

namespace App\Models;

use App\Enums\ProductType;
use App\Observers\ProductObserver;
use App\Support\Money;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[ObservedBy([ProductObserver::class])]
class Product extends Model
{
    public const FOUNDING_SLUG = 'heritage-no-001';

    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'type',
        'amount',
        'currency',
        'edition_total',
        'archive_edition_numbers',
        'stock_quantity',
        'is_published',
        'grants_founding_circle',
        'expected_delivery_label',
        'sold_out_behavior',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'amount' => 'decimal:2',
            'edition_total' => 'integer',
            'archive_edition_numbers' => 'array',
            'stock_quantity' => 'integer',
            'is_published' => 'boolean',
            'grants_founding_circle' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<EditionPiece, $this>
     */
    public function editionPieces(): HasMany
    {
        return $this->hasMany(EditionPiece::class);
    }

    public static function founding(): ?self
    {
        return static::query()->where('slug', self::FOUNDING_SLUG)->first();
    }

    public function isLimitedEdition(): bool
    {
        return $this->type === ProductType::LimitedEdition;
    }

    public function isSimple(): bool
    {
        return $this->type === ProductType::Simple;
    }

    /**
     * @return list<int>
     */
    public function archiveEditionNumberList(): array
    {
        return array_values(array_map(
            intval(...),
            $this->archive_edition_numbers ?? [],
        ));
    }

    public function skuPrefix(): string
    {
        $letters = strtoupper((string) preg_replace('/[^a-zA-Z]/', '', $this->slug));

        return Str::substr($letters !== '' ? $letters : 'PR', 0, 2);
    }

    public function amountInCents(): int
    {
        return Money::toCents((string) $this->amount);
    }

    /**
     * Shared checkout display for Inertia (public storefront = founding SKU).
     *
     * @return array{currency: string, amount: string, displayAmount: string, productName: string, deliveryLabel: string|null}
     */
    public static function checkoutShare(): array
    {
        $product = static::founding();
        $amount = $product?->amount;
        $deliveryLabel = $product?->expected_delivery_label
            ?? CommerceSetting::current()->default_expected_delivery_label;

        if ($amount === null) {
            return [
                'currency' => 'eur',
                'amount' => '',
                'displayAmount' => '',
                'productName' => '',
                'deliveryLabel' => $deliveryLabel,
            ];
        }

        return [
            'currency' => $product->currency,
            'amount' => (string) $amount,
            'displayAmount' => Money::format((string) $amount),
            'productName' => $product->name,
            'deliveryLabel' => $deliveryLabel,
        ];
    }
}
