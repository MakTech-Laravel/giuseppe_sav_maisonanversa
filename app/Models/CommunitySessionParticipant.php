<?php

namespace App\Models;

use App\Enums\SessionParticipantStatus;
use Database\Factories\CommunitySessionParticipantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunitySessionParticipant extends Model
{
    /** @use HasFactory<CommunitySessionParticipantFactory> */
    use HasFactory;

    protected $fillable = [
        'community_session_id',
        'user_id',
        'status',
        'for_chat_only',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SessionParticipantStatus::class,
            'for_chat_only' => 'boolean',
            'decided_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CommunitySession::class, 'community_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === SessionParticipantStatus::Pending;
    }

    public function isConfirmed(): bool
    {
        return $this->status === SessionParticipantStatus::Confirmed;
    }
}
