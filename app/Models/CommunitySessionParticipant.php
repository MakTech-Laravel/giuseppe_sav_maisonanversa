<?php

namespace App\Models;

use Database\Factories\CommunitySessionParticipantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunitySessionParticipant extends Model
{
    /** @use HasFactory<CommunitySessionParticipantFactory> */
    use HasFactory;

    protected $fillable = ['community_session_id', 'user_id'];

    public function session(): BelongsTo
    {
        return $this->belongsTo(CommunitySession::class, 'community_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
