<?php

namespace App\Models;

use App\Models\Concerns\TranslatesWithDeepL;
use Database\Factories\ProductSectionItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSectionItem extends Model
{
    /** @use HasFactory<ProductSectionItemFactory> */
    use HasFactory, TranslatesWithDeepL;

    /**
     * @var list<string>
     */
    protected array $translatable = [
        'title',
        'body',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_section_id',
        'number_label',
        'icon',
        'title',
        'body',
        'image_path',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ProductSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(ProductSection::class, 'product_section_id');
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
     * @return array{id: int, number_label: string, icon: string, title: string, body: string, image: string|null}
     */
    public function toPageShare(?string $locale = null): array
    {
        return [
            'id' => $this->id,
            'number_label' => (string) ($this->number_label ?? ''),
            'icon' => (string) ($this->icon ?? ''),
            'title' => $this->translated('title', $locale),
            'body' => $this->translated('body', $locale),
            'image' => filled($this->image_path)
                ? Product::resolveMediaUrl((string) $this->image_path)
                : null,
        ];
    }
}
