<?php

namespace App\Models;

use App\Enums\SessionCourtStatus;
use App\Enums\SessionGender;
use App\Enums\SessionLevel;
use App\Enums\SessionSport;
use App\Models\Concerns\TranslatesWithDeepL;
use Database\Factories\CommunitySessionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A member-hosted game looking for players.
 *
 * Notes follow the FAQ / community-post pattern: DeepL auto-detects the
 * language the host typed and fills nl, en and fr. Club names stay off
 * DeepL because they are proper nouns.
 */
class CommunitySession extends Model
{
    /** @use HasFactory<CommunitySessionFactory> */
    use HasFactory, TranslatesWithDeepL;

    /** @var list<string> */
    protected array $translatable = ['notes'];

    protected $fillable = [
        'host_id',
        'sport',
        'club_id',
        'starts_at',
        'duration_minutes',
        'ends_at',
        'court_status',
        'capacity',
        'level',
        'gender',
        'notes',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'capacity' => 'integer',
            'duration_minutes' => 'integer',
            'sport' => SessionSport::class,
            'level' => SessionLevel::class,
            'gender' => SessionGender::class,
            'court_status' => SessionCourtStatus::class,
        ];
    }

    /**
     * `ends_at` is stored rather than derived so "past" filtering is a plain
     * indexed comparison. Keep it in sync on every write.
     */
    protected static function booted(): void
    {
        static::saving(function (self $session): void {
            if ($session->starts_at === null) {
                return;
            }

            $session->ends_at = $session->starts_at->copy()
                ->addMinutes($session->duration_minutes ?? 90);
        });
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(CommunitySessionParticipant::class);
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'community_session_participants')
            ->withTimestamps();
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereNull('cancelled_at')->where('ends_at', '>', now());
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->where('ends_at', '<=', now());
    }

    public function participantsCount(): int
    {
        return isset($this->participants_count)
            ? (int) $this->participants_count
            : $this->participants()->count();
    }

    public function isFull(): bool
    {
        return $this->participantsCount() >= $this->capacity;
    }

    public function isPast(): bool
    {
        return $this->ends_at !== null && $this->ends_at->isPast();
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function isHostedBy(User $user): bool
    {
        return $this->host_id === $user->id;
    }

    public function openSlots(): int
    {
        return max(0, $this->capacity - $this->participantsCount());
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
