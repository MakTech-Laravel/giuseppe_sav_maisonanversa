<?php

namespace App\Models;

use App\Models\Concerns\TranslatesWithDeepL;
use App\Support\Imagery;
use App\Support\Journal;
use Database\Factories\JournalArticleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class JournalArticle extends Model
{
    /** @use HasFactory<JournalArticleFactory> */
    use HasFactory, TranslatesWithDeepL;

    /**
     * @var list<string>
     */
    protected array $translatable = [
        'title',
        'excerpt',
        'body',
        'category',
        'date_label',
    ];

    /**
     * @var list<string>
     */
    protected array $translationExcept = [
        'author',
        'cover_path',
        'image_path',
        'slug',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'title',
        'excerpt',
        'body',
        'cover_path',
        'image_path',
        'category',
        'author',
        'date_label',
        'published_at',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
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
     * Catalog shape consumed by {@see Journal}.
     *
     * @return array{
     *     slug: string,
     *     asset: string|null,
     *     image_url: string|null,
     *     category: array{nl: string, en: string, fr: string},
     *     title: array{nl: string, en: string, fr: string},
     *     excerpt: array{nl: string, en: string, fr: string},
     *     author: string,
     *     date: array{nl: string, en: string, fr: string},
     *     body: array{nl: string, en: string, fr: string}
     * }
     */
    public function toCatalogArray(): array
    {
        return [
            'slug' => $this->slug,
            'asset' => $this->cover_path,
            'image_url' => $this->image_path !== null && $this->image_path !== ''
                ? Storage::disk('public')->url($this->image_path)
                : null,
            'category' => $this->localeCopy('category'),
            'title' => $this->localeCopy('title'),
            'excerpt' => $this->localeCopy('excerpt'),
            'author' => (string) ($this->author ?? 'Maison Anversa'),
            'date' => $this->localeCopy('date_label'),
            'body' => $this->localeCopy('body'),
        ];
    }

    public function resolvedImageUrl(): ?string
    {
        if ($this->image_path !== null && $this->image_path !== '') {
            return Storage::disk('public')->url($this->image_path);
        }

        if ($this->cover_path !== null && $this->cover_path !== '') {
            return Imagery::assetUrl($this->cover_path);
        }

        return null;
    }

    /**
     * @return array{nl: string, en: string, fr: string}
     */
    private function localeCopy(string $column): array
    {
        return [
            'nl' => (string) ($this->getAttribute($column) ?? ''),
            'en' => $this->translated($column, 'en'),
            'fr' => $this->translated($column, 'fr'),
        ];
    }
}
