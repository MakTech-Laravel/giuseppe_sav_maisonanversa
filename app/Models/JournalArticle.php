<?php

namespace App\Models;

use App\Models\Concerns\TranslatesWithDeepL;
use App\Support\Journal;
use Database\Factories\JournalArticleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
     * Catalog shape consumed by {@see Journal}.
     *
     * @return array{
     *     slug: string,
     *     asset: string,
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
            'asset' => $this->cover_path ?? 'antwerp-cityscape',
            'category' => $this->localeCopy('category'),
            'title' => $this->localeCopy('title'),
            'excerpt' => $this->localeCopy('excerpt'),
            'author' => (string) ($this->author ?? 'Maison Anversa'),
            'date' => $this->localeCopy('date_label'),
            'body' => $this->localeCopy('body'),
        ];
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
