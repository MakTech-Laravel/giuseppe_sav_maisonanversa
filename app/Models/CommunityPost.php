<?php

namespace App\Models;

use App\Models\Concerns\TranslatesWithDeepL;
use Database\Factories\CommunityPostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityPost extends Model
{
    /** @use HasFactory<CommunityPostFactory> */
    use HasFactory, TranslatesWithDeepL;

    /** @var list<string> */
    protected array $translatable = ['content'];

    protected $fillable = ['author_id', 'content', 'is_official', 'status', 'hidden_at'];

    protected function casts(): array
    {
        return [
            'is_official' => 'boolean',
            'hidden_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CommunityComment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CommunityLike::class);
    }

    public function hides(): HasMany
    {
        return $this->hasMany(CommunityPostHide::class);
    }

    public function isAdminHidden(): bool
    {
        return $this->status === 'hidden' || $this->hidden_at !== null;
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
}
