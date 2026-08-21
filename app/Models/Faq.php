<?php

namespace App\Models;

use App\Enums\FaqContext;
use App\Models\Concerns\TranslatesWithDeepL;
use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    /** @use HasFactory<FaqFactory> */
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
        'context',
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
            'context' => FaqContext::class,
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Faq>  $query
     * @return Builder<Faq>
     */
    public function scopeForContextPublished(Builder $query, FaqContext|string $context): Builder
    {
        $value = $context instanceof FaqContext ? $context->value : $context;

        return $query
            ->where('context', $value)
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * @return Collection<int, Faq>
     */
    public static function publishedFor(FaqContext|string $context): Collection
    {
        return static::query()->forContextPublished($context)->get();
    }
}
