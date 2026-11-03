<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use Database\Factories\OpeningOfferFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The product the homepage opening offers for reservation.
 *
 * Admins manage these rows; at most one is active at a time, so the opening shows
 * exactly one product. Saving an active offer deactivates the others.
 */
class OpeningOffer extends Model
{
    /** @use HasFactory<OpeningOfferFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (OpeningOffer $offer): void {
            $becameActive = $offer->is_active
                && ($offer->wasRecentlyCreated || $offer->wasChanged('is_active'));

            if ($becameActive) {
                static::query()
                    ->whereKeyNot($offer->getKey())
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }
        });
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @param  Builder<OpeningOffer>  $query
     * @return Builder<OpeningOffer>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<OpeningOffer>  $query
     * @return Builder<OpeningOffer>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Products an offer may point at: numbered limited editions that are live.
     *
     * @return Builder<Product>
     */
    public static function eligibleProducts(): Builder
    {
        return Product::query()
            ->where('type', ProductType::LimitedEdition->value)
            ->where('is_published', true)
            ->where('status', ProductStatus::Active->value);
    }

    public static function isEligible(Product $product): bool
    {
        return $product->type === ProductType::LimitedEdition
            && $product->is_published
            && ($product->status ?? ProductStatus::Active) === ProductStatus::Active;
    }

    /** Whether the product can still be offered; if not, the opening shows no register. */
    public function isOfferable(): bool
    {
        return $this->product !== null && static::isEligible($this->product);
    }

    /** The offer the opening shows right now, or null when there is none. */
    public static function current(): ?self
    {
        $offer = static::query()->active()->ordered()->with('product')->first();

        return $offer?->isOfferable() ? $offer : null;
    }
}
