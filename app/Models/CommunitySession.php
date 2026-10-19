<?php

namespace App\Models;

use App\Enums\SessionCourtStatus;
use App\Enums\SessionGender;
use App\Enums\SessionLevel;
use App\Enums\SessionLifecycle;
use App\Enums\SessionParticipantStatus;
use App\Enums\SessionScoreStatus;
use App\Enums\SessionSport;
use App\Models\Concerns\TranslatesWithDeepL;
use Carbon\CarbonInterface;
use Database\Factories\CommunitySessionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

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

    /** @var list<int> */
    public const DURATION_MINUTES = [20, 40, 60, 80, 90, 120, 150, 180];

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
        'cancellation_message',
        'reminder_sent_at',
        'open_spots_alert_sent_at',
        'match_card_sent_at',
        'scoreboard_locked_at',
        'finished_at',
    ];

    /**
     * Participants may submit scores from `ends_at` until this many days later.
     */
    public const SCORE_WINDOW_DAYS = 3;

    /**
     * Join requests close this many hours before the start.
     */
    public const REGISTRATION_CLOSES_HOURS = 2;

    /**
     * The organiser hears about free places this many hours before the start.
     */
    public const OPEN_SPOTS_ALERT_HOURS = 48;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'open_spots_alert_sent_at' => 'datetime',
            'match_card_sent_at' => 'datetime',
            'scoreboard_locked_at' => 'datetime',
            'finished_at' => 'datetime',
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
        static::creating(function (self $session): void {
            $session->share_token ??= Str::random(32);
        });

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

    /**
     * Every row, whatever the host decided. Use for "has this user asked?"
     * checks; `participants()` is the player list.
     */
    public function participantRecords(): HasMany
    {
        return $this->hasMany(CommunitySessionParticipant::class);
    }

    /**
     * Confirmed roster rows: play seats plus staff chat-only joins.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(CommunitySessionParticipant::class)
            ->where('status', SessionParticipantStatus::Confirmed->value);
    }

    /**
     * Confirmed players who occupy a play seat (excludes staff chat-only).
     */
    public function playParticipants(): HasMany
    {
        return $this->hasMany(CommunitySessionParticipant::class)
            ->where('status', SessionParticipantStatus::Confirmed->value)
            ->where('for_chat_only', false);
    }

    /**
     * Pending requests. Each one reserves a seat until the host decides.
     */
    public function joinRequests(): HasMany
    {
        return $this->hasMany(CommunitySessionParticipant::class)
            ->where('status', SessionParticipantStatus::Pending->value);
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'community_session_participants')
            ->wherePivot('status', SessionParticipantStatus::Confirmed->value)
            ->wherePivot('for_chat_only', false)
            ->withTimestamps();
    }

    public function scores(): HasMany
    {
        return $this->hasMany(CommunitySessionScore::class);
    }

    public function officialScore(): HasOne
    {
        return $this->hasOne(CommunitySessionScore::class)
            ->where('status', SessionScoreStatus::Approved->value);
    }

    /**
     * @return HasMany<CommunitySessionMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(CommunitySessionMessage::class);
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
        if (isset($this->play_participants_count)) {
            return (int) $this->play_participants_count;
        }

        if ($this->relationLoaded('participants')) {
            return $this->participants->where('for_chat_only', false)->count();
        }

        return $this->playParticipants()->count();
    }

    public function pendingCount(): int
    {
        return isset($this->join_requests_count)
            ? (int) $this->join_requests_count
            : $this->joinRequests()->count();
    }

    /**
     * Seats taken by confirmed players plus seats held by pending requests.
     */
    public function reservedSeats(): int
    {
        return $this->participantsCount() + $this->pendingCount();
    }

    /**
     * Every confirmed seat is taken.
     */
    public function isFull(): bool
    {
        return $this->participantsCount() >= $this->capacity;
    }

    /**
     * A new request still fits next to the confirmed players and the
     * requests already waiting.
     */
    public function acceptsRequests(): bool
    {
        return ! $this->isCancelled()
            && ! $this->isPast()
            && ! $this->isRegistrationClosed()
            && $this->reservedSeats() < $this->capacity;
    }

    public function registrationClosesAt(): CarbonInterface
    {
        return $this->starts_at->copy()->subHours(self::REGISTRATION_CLOSES_HOURS);
    }

    public function isRegistrationClosed(): bool
    {
        return now()->greaterThanOrEqualTo($this->registrationClosesAt());
    }

    /**
     * Players may leave, and the organiser may decide requests, until this moment.
     */
    public function hasStarted(): bool
    {
        return $this->starts_at !== null && $this->starts_at->lessThanOrEqualTo(now());
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

    /**
     * Seats still free to request.
     */
    public function openSlots(): int
    {
        return max(0, $this->capacity - $this->reservedSeats());
    }

    public function isFinished(): bool
    {
        return $this->finished_at !== null;
    }

    /**
     * The game is over but the scoreboard has not been closed yet.
     */
    public function isHeld(): bool
    {
        return ! $this->isCancelled() && ! $this->isFinished() && $this->isPast();
    }

    public function isScoreboardLocked(): bool
    {
        return $this->scoreboard_locked_at !== null;
    }

    public function scoreWindowClosesAt(): ?CarbonInterface
    {
        return $this->ends_at?->copy()->addDays(self::SCORE_WINDOW_DAYS);
    }

    /**
     * Participants (other than the host) may submit only inside this window.
     */
    public function isScoreWindowOpen(): bool
    {
        return $this->isHeld() && now()->lessThan($this->scoreWindowClosesAt());
    }

    /**
     * The public page anyone (guests included) can open to see and share the match card.
     */
    public function shareUrl(string $locale): string
    {
        return route('maison.sessions.share', ['locale' => $locale, 'shareToken' => $this->share_token]);
    }

    public function lifecycle(): SessionLifecycle
    {
        return match (true) {
            $this->isCancelled() => SessionLifecycle::Cancelled,
            $this->isFinished() => SessionLifecycle::Finished,
            $this->isPast() => SessionLifecycle::Held,
            default => SessionLifecycle::Scheduled,
        };
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
