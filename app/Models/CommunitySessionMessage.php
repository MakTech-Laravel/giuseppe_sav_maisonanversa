<?php

namespace App\Models;

use Database\Factories\CommunitySessionMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunitySessionMessage extends Model
{
    /** @use HasFactory<CommunitySessionMessageFactory> */
    use HasFactory;

    protected $fillable = [
        'community_session_id',
        'user_id',
        'body',
        'is_system',
        'reported_by_id',
        'reported_at',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'reported_at' => 'datetime',
        ];
    }

    public function isReported(): bool
    {
        return $this->reported_at !== null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_id');
    }

    /**
     * @return BelongsTo<CommunitySession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(CommunitySession::class, 'community_session_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<CommunitySessionMessageAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(CommunitySessionMessageAttachment::class, 'message_id');
    }
}
