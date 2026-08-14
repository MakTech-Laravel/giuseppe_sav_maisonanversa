<?php

namespace App\Models;

use Database\Factories\CommunitySessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunitySession extends Model
{
    /** @use HasFactory<CommunitySessionFactory> */
    use HasFactory;

    protected $fillable = ['host_id', 'starts_at', 'location', 'capacity', 'level', 'notes'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'capacity' => 'integer',
        ];
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(CommunitySessionParticipant::class);
    }
}
