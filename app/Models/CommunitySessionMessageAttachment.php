<?php

namespace App\Models;

use Database\Factories\CommunitySessionMessageAttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CommunitySessionMessageAttachment extends Model
{
    /** @use HasFactory<CommunitySessionMessageAttachmentFactory> */
    use HasFactory;

    protected $fillable = [
        'message_id',
        'path',
        'original_name',
        'mime',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<CommunitySessionMessage, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(CommunitySessionMessage::class, 'message_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
