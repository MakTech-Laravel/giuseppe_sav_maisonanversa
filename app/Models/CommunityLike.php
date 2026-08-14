<?php

namespace App\Models;

use Database\Factories\CommunityLikeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityLike extends Model
{
    /** @use HasFactory<CommunityLikeFactory> */
    use HasFactory;

    protected $fillable = ['community_post_id', 'user_id'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class, 'community_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
