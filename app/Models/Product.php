<?php

namespace App\Models;

use App\Observers\ProductObserver;
use App\Support\Money;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'amount',
        'currency',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
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

    public function amountInCents(): int
    {
        return Money::toCents((string) $this->amount);
    }

    /**
     * Shared checkout display for Inertia (Founding Edition).
     *
     * @return array{currency: string, amount: string, displayAmount: string, productName: string}
     */
    public static function checkoutShare(): array
    {
        $product = static::founding();
        $amount = $product?->amount ?? '249.00';

        return [
            'currency' => $product?->currency ?? 'eur',
            'amount' => (string) $amount,
            'displayAmount' => Money::format((string) $amount),
            'productName' => $product?->name ?? 'Heritage No.001 — Founding Edition',
        ];
    }
}
