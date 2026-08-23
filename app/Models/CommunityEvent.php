<?php

namespace App\Models;

use App\Models\Concerns\TranslatesWithDeepL;
use Database\Factories\CommunityEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class CommunityEvent extends Model
{
    /** @use HasFactory<CommunityEventFactory> */
    use HasFactory, TranslatesWithDeepL;

    protected $fillable = [
        'title',
        'description',
        'starts_at',
        'location',
        'capacity',
        'thumbnail',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'capacity' => 'integer',
        ];
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(EventRsvp::class);
    }

    public function thumbnailUrl(): ?string
    {
        if ($this->thumbnail === null || $this->thumbnail === '') {
            return null;
        }

        return Storage::disk('public')->url($this->thumbnail);
    }

    public function isFull(): bool
    {
        if ($this->capacity === null) {
            return false;
        }

        $count = isset($this->rsvps_count)
            ? (int) $this->rsvps_count
            : $this->rsvps()->count();

        return $count >= $this->capacity;
    }
}
