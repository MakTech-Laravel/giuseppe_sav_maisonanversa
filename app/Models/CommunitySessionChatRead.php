<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunitySessionChatRead extends Model
{
    protected $fillable = [
        'user_id',
        'community_session_id',
        'last_read_at',
    ];

    protected function casts(): array
    {
        return [
            'last_read_at' => 'datetime',
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
     * @return BelongsTo<CommunitySession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(CommunitySession::class, 'community_session_id');
    }
}
