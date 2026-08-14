<?php

namespace App\Models;

use Database\Factories\CommunityReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityReport extends Model
{
    /** @use HasFactory<CommunityReportFactory> */
    use HasFactory;

    protected $fillable = ['community_post_id', 'reporter_id', 'reason', 'status'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class, 'community_post_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}
