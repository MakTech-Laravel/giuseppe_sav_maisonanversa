<?php

namespace App\Models;

use App\Enums\ProductSectionKey;
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
        'meta_title',
        'meta_description',
        'meta_keywords',
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
        'eyebrow',
        'hero_eyebrow',
        'hero_subtitle',
        'description',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_image',
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

    /**
     * @return HasMany<ProductSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(ProductSection::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * @return HasMany<ProductFaq, $this>
     */
    public function faqs(): HasMany
    {
        return $this->hasMany(ProductFaq::class)
            ->orderBy('sort_order')
            ->orderBy('id');
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

    public function parseEditionSequence(string $label): int
    {
        $digits = preg_replace('/\D+/', '', $label) ?? '';

        return $digits !== '' ? (int) $digits : 0;
    }

    public function formatEditionSkuForLabel(string $label): string
    {
        return $this->formatEditionSku($this->parseEditionSequence($label));
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
     *     sections: array<int, array<string, mixed>>,
     *     faqs: array<int, array{id: int, question: string, answer: string}>,
     *     amount: string,
     *     currency: string,
     *     expected_delivery_label: string,
     *     edition_total: int|null
     * }
     */
    public function toPageShare(?string $locale = null): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->translated('name', $locale),
            'eyebrow' => $this->translated('eyebrow', $locale),
            'hero_eyebrow' => $this->translated('hero_eyebrow', $locale),
            'hero_subtitle' => $this->translated('hero_subtitle', $locale),
            'description' => $this->translated('description', $locale),
            'status' => ($this->status ?? ProductStatus::Active)->value,
            'sort_order' => $this->sort_order ?? 0,
            'gallery' => $this->resolvedGallery(),
            'sections' => $this->visibleSectionShare($locale),
            'faqs' => $this->publishedFaqShare($locale),
            'amount' => (string) $this->amount,
            'currency' => $this->currency,
            'expected_delivery_label' => $this->translated('expected_delivery_label', $locale),
            'edition_total' => $this->edition_total,
        ];
    }

    /**
     * Visible sections in render order, keyed by section key for the frontend.
     *
     * @return list<array<string, mixed>>
     */
    public function visibleSectionShare(?string $locale = null): array
    {
        $this->loadMissing('sections.items');

        return $this->sections
            ->filter(fn (ProductSection $section): bool => $section->is_visible)
            ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
            ->map(fn (ProductSection $section): array => $section->toPageShare($locale))
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, question: string, answer: string}>
     */
    public function publishedFaqShare(?string $locale = null): array
    {
        $this->loadMissing('faqs');

        return $this->faqs
            ->filter(fn (ProductFaq $faq): bool => $faq->is_published)
            ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
            ->map(fn (ProductFaq $faq): array => $faq->toPageShare($locale))
            ->values()
            ->all();
    }

    /**
     * Items of a single section, regardless of its visibility flag.
     *
     * @return list<array<string, mixed>>
     */
    public function sectionItemShare(ProductSectionKey $key, ?string $locale = null): array
    {
        $this->loadMissing('sections.items');

        $section = $this->sections->first(
            fn (ProductSection $section): bool => $section->key === $key,
        );

        return $section?->toPageShare($locale)['items'] ?? [];
    }

    /**
     * @return array{id: int, slug: string, name: string, status: string, hero_subtitle: string, cover_asset: string|null, amount: string, display_amount: string, currency: string, type: string}
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
            'amount' => (string) $this->amount,
            'display_amount' => Money::format((string) $this->amount),
            'currency' => $this->currency,
            'type' => $this->type->value,
        ];
    }

    /**
     * Public URL for the product Open Graph image, or null when neither an
     * explicit OG file nor a gallery cover exists. MaisonSeo applies the house
     * default image when this returns null.
     */
    public function resolvedOgImageUrl(): ?string
    {
        $path = filled($this->og_image)
            ? (string) $this->og_image
            : (string) (($this->gallery ?? [])[0] ?? '');

        if ($path === '') {
            return null;
        }

        $url = self::resolveDisplayMediaUrl($path);

        return $url !== '' ? $url : null;
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
     * Shared checkout display for Inertia. Defaults to the founding SKU
     * (global storefront CTAs); pass a specific product for per-product
     * checkout contexts such as the products catalog detail page.
     *
     * @return array{productId: int|null, currency: string, amount: string, displayAmount: string, productName: string, deliveryLabel: string|null, productType: string}
     */
    public static function checkoutShare(?self $product = null): array
    {
        $product ??= static::founding();
        $amount = $product?->amount;
        $deliveryLabel = $product !== null
            ? ($product->translated('expected_delivery_label')
                ?: CommerceSetting::current()->translated('default_expected_delivery_label'))
            : CommerceSetting::current()->translated('default_expected_delivery_label');

        if ($amount === null) {
            return [
                'productId' => $product?->id,
                'productSlug' => $product?->slug,
                'currency' => 'eur',
                'amount' => '',
                'displayAmount' => '',
                'productName' => '',
                'deliveryLabel' => $deliveryLabel,
                'productType' => $product?->type?->value ?? ProductType::LimitedEdition->value,
            ];
        }

        return [
            'productId' => $product->id,
            'productSlug' => $product->slug,
            'currency' => $product->currency,
            'amount' => (string) $amount,
            'displayAmount' => Money::format((string) $amount),
            'productName' => $product->translated('name'),
            'deliveryLabel' => $deliveryLabel,
            'productType' => $product->type->value,
        ];
    }
}
