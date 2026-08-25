<?php

namespace App\Models;

use App\Enums\ProductSectionKey;
use App\Models\Concerns\TranslatesWithDeepL;
use Database\Factories\ProductSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductSection extends Model
{
    /** @use HasFactory<ProductSectionFactory> */
    use HasFactory, TranslatesWithDeepL;

    /**
     * @var list<string>
     */
    protected array $translatable = [
        'eyebrow',
        'heading',
        'subheading',
        'intro',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'key',
        'eyebrow',
        'heading',
        'subheading',
        'intro',
        'image_path',
        'image_key',
        'is_visible',
        'include_house_card',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'key' => ProductSectionKey::class,
            'is_visible' => 'boolean',
            'include_house_card' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<ProductSectionItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProductSectionItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
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
     * Browser-ready image URL, preferring an upload over an imagery asset key.
     */
    public function resolvedImage(): ?string
    {
        if (filled($this->image_path)) {
            return Product::resolveMediaUrl((string) $this->image_path);
        }

        return filled($this->image_key) ? (string) $this->image_key : null;
    }

    /**
     * @return array{key: string, eyebrow: string, heading: string, subheading: string, intro: string, image: string|null, include_house_card: bool, sort_order: int, items: list<array<string, mixed>>}
     */
    public function toPageShare(?string $locale = null): array
    {
        return [
            'key' => $this->key->value,
            'eyebrow' => $this->translated('eyebrow', $locale),
            'heading' => $this->translated('heading', $locale),
            'subheading' => $this->translated('subheading', $locale),
            'intro' => $this->translated('intro', $locale),
            'image' => $this->resolvedImage(),
            'include_house_card' => (bool) $this->include_house_card,
            'sort_order' => $this->sort_order ?? 0,
            'items' => $this->items
                ->map(fn (ProductSectionItem $item): array => $item->toPageShare($locale))
                ->values()
                ->all(),
        ];
    }
}
