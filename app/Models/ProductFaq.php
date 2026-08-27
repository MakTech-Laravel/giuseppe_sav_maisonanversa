<?php

namespace App\Models;

use App\Models\Concerns\TranslatesWithDeepL;
use Database\Factories\ProductFaqFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductFaq extends Model
{
    /** @use HasFactory<ProductFaqFactory> */
    use HasFactory, TranslatesWithDeepL;

    /**
     * @var list<string>
     */
    protected array $translatable = [
        'question',
        'answer',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'question',
        'answer',
        'sort_order',
        'is_published',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_published' => 'boolean',
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
     * @param  Builder<ProductFaq>  $query
     * @return Builder<ProductFaq>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
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
     * @return array{id: int, question: string, answer: string}
     */
    public function toPageShare(?string $locale = null): array
    {
        return [
            'id' => $this->id,
            'question' => $this->translated('question', $locale),
            'answer' => $this->translated('answer', $locale),
        ];
    }
}
