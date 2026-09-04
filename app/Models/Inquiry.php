<?php

namespace App\Models;

use App\Enums\InquiryType;
use Carbon\CarbonInterface;
use Database\Factories\InquiryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inquiry extends Model
{
    /** @use HasFactory<InquiryFactory> */
    use HasFactory;

    /**
     * Priority Founding Circle inquiries must receive a reply within this many hours.
     */
    public const SLA_HOURS = 12;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'user_id',
        'priority',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'meta',
        'locale',
        'ip',
        'device_token',
        'seen_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => InquiryType::class,
            'priority' => 'boolean',
            'meta' => 'array',
            'seen_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeSeen(Builder $query): Builder
    {
        return $query->whereNotNull('seen_at');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeUnseen(Builder $query): Builder
    {
        return $query->whereNull('seen_at');
    }

    public function isSeen(): bool
    {
        return $this->seen_at !== null;
    }

    public function markSeen(): void
    {
        if ($this->seen_at !== null) {
            return;
        }

        $this->update(['seen_at' => now()]);
    }

    /**
     * The moment by which a priority inquiry must be answered, per the
     * 12-hour Founding Circle support SLA. Null for non-priority inquiries.
     */
    public function slaDueAt(): ?CarbonInterface
    {
        if (! $this->priority || $this->created_at === null) {
            return null;
        }

        return $this->created_at->addHours(self::SLA_HOURS);
    }

    public function isSlaBreached(): bool
    {
        $dueAt = $this->slaDueAt();

        return $dueAt !== null && ! $this->isSeen() && $dueAt->isPast();
    }

    /**
     * @return array{priority: bool, sla_due_at: string|null, sla_breached: bool}
     */
    public function slaShare(): array
    {
        return [
            'priority' => $this->priority,
            'sla_due_at' => $this->slaDueAt()?->toIso8601String(),
            'sla_breached' => $this->isSlaBreached(),
        ];
    }
}
