<?php

namespace App\Models;

use Database\Factories\EventRsvpFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRsvp extends Model
{
    /** @use HasFactory<EventRsvpFactory> */
    use HasFactory;

    protected $fillable = ['community_event_id', 'user_id'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(CommunityEvent::class, 'community_event_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
