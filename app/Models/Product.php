<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Concerns\TranslatesWithDeepL;
use App\Observers\ProductObserver;
use App\Support\Imagery;
use App\Support\Money;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[ObservedBy([ProductObserver::class])]
class Product extends Model
{
    public const FOUNDING_SLUG = 'heritage-no-001';

    /** @use HasFactory<ProductFactory> */
    use HasFactory, TranslatesWithDeepL;

    /**
     * @var list<string>
     */
    protected array $translatable = [
        'name',
        'eyebrow',
        'hero_eyebrow',
        'hero_subtitle',
        'description',
        'expected_delivery_label',
    ];

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
        'edition_number_prefix',
        'edition_number_postfix',
        'archive_edition_numbers',
        'stock_quantity',
        'is_published',
        'grants_founding_circle',
        'expected_delivery_label',
        'sold_out_behavior',
        'gallery',
        'specs',
        'materials',
        'unboxing_steps',
        'includes',
        'guarantees',
        'trust_badges',
        'eyebrow',
        'hero_eyebrow',
        'hero_subtitle',
        'description',
        'status',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'status' => ProductStatus::class,
            'amount' => 'decimal:2',
            'edition_total' => 'integer',
            'archive_edition_numbers' => 'array',
            'gallery' => 'array',
            'specs' => 'array',
            'materials' => 'array',
            'unboxing_steps' => 'array',
            'includes' => 'array',
            'guarantees' => 'array',
            'trust_badges' => 'array',
            'stock_quantity' => 'integer',
            'is_published' => 'boolean',
            'grants_founding_circle' => 'boolean',
            'sort_order' => 'integer',
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
        if (filled($this->edition_number_prefix)) {
            return (string) $this->edition_number_prefix;
        }

        $letters = strtoupper((string) preg_replace('/[^a-zA-Z]/', '', $this->slug));

        return Str::substr($letters !== '' ? $letters : 'PR', 0, 2);
    }

    public function editionNumberPadWidth(): int
    {
        return max(3, strlen((string) max(1, (int) ($this->edition_total ?? 1))));
    }

    public function formatEditionDigits(int $number): string
    {
        return str_pad((string) $number, $this->editionNumberPadWidth(), '0', STR_PAD_LEFT);
    }

    public function formatEditionLabel(int $number): string
    {
        return ($this->edition_number_prefix ?? '').$this->formatEditionDigits($number).($this->edition_number_postfix ?? '');
    }

    public function formatEditionSku(int $number): string
    {
        $label = $this->formatEditionLabel($number);

        if ($label !== $this->formatEditionDigits($number)) {
            return $label;
        }

        return $this->skuPrefix().'-'.$this->formatEditionDigits($number);
    }

    public function amountInCents(): int
    {
        return Money::toCents((string) $this->amount);
    }

    /**
     * @return list<string>
     */
    public function translationTargetLocales(): array
    {
        return config('maison.locales');
    }

    public function translationUsesAutoDetect(): bool
    {
        return true;
    }

    public function translated(string $column, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $source = (string) ($this->getAttribute($column) ?? '');

        $this->loadMissing('translations');

        $row = $this->translations->first(
            fn (Translation $translation): bool => $translation->locale === $locale
                && $translation->column === $column,
        );

        return filled($row?->value) ? (string) $row->value : $source;
    }

    /**
     * @return array{
     *     id: int,
     *     slug: string,
     *     name: string,
     *     eyebrow: string,
     *     hero_eyebrow: string,
     *     hero_subtitle: string,
     *     description: string,
     *     status: string,
     *     sort_order: int,
     *     gallery: array<int, string>,
     *     specs: array<int, array{label: string, value: string}>,
     *     materials: array<int, array{num: string, name: string, desc: string}>,
     *     unboxing_steps: array<int, array{num: string, title: string, desc: string}>,
     *     includes: array<int, string>,
     *     guarantees: array<int, array{icon: string, text: string}>,
     *     trust_badges: array<int, array{icon: string, text: string}>,
     *     amount: string,
     *     currency: string,
     *     expected_delivery_label: string,
     *     edition_total: int|null
     * }
     */
    public function toPageShare(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->translated('name'),
            'eyebrow' => $this->translated('eyebrow'),
            'hero_eyebrow' => $this->translated('hero_eyebrow'),
            'hero_subtitle' => $this->translated('hero_subtitle'),
            'description' => $this->translated('description'),
            'status' => ($this->status ?? ProductStatus::Active)->value,
            'sort_order' => $this->sort_order ?? 0,
            'gallery' => $this->resolvedGallery(),
            'specs' => $this->specs ?? [],
            'materials' => $this->materials ?? [],
            'unboxing_steps' => $this->unboxing_steps ?? [],
            'includes' => $this->includes ?? [],
            'guarantees' => $this->guarantees ?? [],
            'trust_badges' => $this->trust_badges ?? [],
            'amount' => (string) $this->amount,
            'currency' => $this->currency,
            'expected_delivery_label' => $this->translated('expected_delivery_label'),
            'edition_total' => $this->edition_total,
        ];
    }

    /**
     * @return array{id: int, slug: string, name: string, status: string, hero_subtitle: string, cover_asset: string|null}
     */
    public function toCardShare(): array
    {
        $gallery = $this->resolvedGallery();

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->translated('name'),
            'status' => ($this->status ?? ProductStatus::Active)->value,
            'hero_subtitle' => $this->translated('hero_subtitle'),
            'cover_asset' => $gallery[0] ?? null,
        ];
    }

    /**
     * Resolve gallery items to either imagery asset keys or public URLs.
     *
     * @return list<string>
     */
    public function resolvedGallery(): array
    {
        return array_values(array_map(
            fn (mixed $item): string => self::resolveMediaUrl(is_string($item) ? $item : ''),
            $this->gallery ?? [],
        ));
    }

    public static function resolveMediaUrl(string $item): string
    {
        if ($item === '') {
            return $item;
        }

        if (str_starts_with($item, 'http://') || str_starts_with($item, 'https://') || str_starts_with($item, '/')) {
            return $item;
        }

        if (str_contains($item, '/')) {
            return Storage::disk('public')->url($item);
        }

        return $item;
    }

    /**
     * Browser-ready URL for admin previews and uploaded-file components.
     * Asset keys from the imagery manifest resolve to `/images/...` paths.
     */
    public static function resolveDisplayMediaUrl(string $item): string
    {
        if ($item === '') {
            return $item;
        }

        if (str_starts_with($item, 'http://') || str_starts_with($item, 'https://') || str_starts_with($item, '/')) {
            return $item;
        }

        if (str_contains($item, '/')) {
            return Storage::disk('public')->url($item);
        }

        return Imagery::assetUrl($item) ?? $item;
    }

    /**
     * Shared checkout display for Inertia (public storefront = founding SKU).
     *
     * @return array{productId: int|null, currency: string, amount: string, displayAmount: string, productName: string, deliveryLabel: string|null}
     */
    public static function checkoutShare(): array
    {
        $product = static::founding();
        $amount = $product?->amount;
        $deliveryLabel = $product !== null
            ? ($product->translated('expected_delivery_label')
                ?: CommerceSetting::current()->translated('default_expected_delivery_label'))
            : CommerceSetting::current()->translated('default_expected_delivery_label');

        if ($amount === null) {
            return [
                'productId' => $product?->id,
                'currency' => 'eur',
                'amount' => '',
                'displayAmount' => '',
                'productName' => '',
                'deliveryLabel' => $deliveryLabel,
            ];
        }

        return [
            'productId' => $product->id,
            'currency' => $product->currency,
            'amount' => (string) $amount,
            'displayAmount' => Money::format((string) $amount),
            'productName' => $product->translated('name'),
            'deliveryLabel' => $deliveryLabel,
        ];
    }
}
