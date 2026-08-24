<?php

namespace App\Models;

use App\Enums\CommunityEventStatus;
use App\Models\Concerns\TranslatesWithDeepL;
use Database\Factories\CommunityEventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class CommunityEvent extends Model
{
    /** @use HasFactory<CommunityEventFactory> */
    use HasFactory, TranslatesWithDeepL;

    /**
     * @var list<string>
     */
    protected array $translatable = [
        'title',
        'description',
        'location',
    ];

    protected $fillable = [
        'title',
        'description',
        'starts_at',
        'location',
        'capacity',
        'status',
        'thumbnail',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'capacity' => 'integer',
            'status' => CommunityEventStatus::class,
        ];
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(EventRsvp::class);
    }

    public function thumbnailUrl(): ?string
    {
        if ($this->thumbnail === null || $this->thumbnail === '') {
            return null;
        }

        return Storage::disk('public')->url($this->thumbnail);
    }

    public function isFull(): bool
    {
        if ($this->capacity === null) {
            return false;
        }

        $count = isset($this->rsvps_count)
            ? (int) $this->rsvps_count
            : $this->rsvps()->count();

        return $count >= $this->capacity;
    }

    /**
     * @param  Builder<CommunityEvent>  $query
     * @return Builder<CommunityEvent>
     */
    public function scopeStatus(Builder $query, CommunityEventStatus|string $status): Builder
    {
        $value = $status instanceof CommunityEventStatus ? $status->value : $status;

        return $query->where('status', $value);
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
