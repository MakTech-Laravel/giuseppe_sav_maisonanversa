<?php

namespace App\Models;

use App\Enums\InquiryType;
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
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'user_id',
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
}
