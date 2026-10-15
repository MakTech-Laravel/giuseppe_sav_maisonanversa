<?php

namespace App\Models;

use App\Enums\SessionMatchFormat;
use App\Enums\SessionMatchType;
use App\Enums\SessionScoreStatus;
use App\Support\MatchScore;
use Database\Factories\CommunitySessionScoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One score submission. Every submission is its own row so the history is
 * kept; at most one row per session is Approved, and that row is the
 * official result.
 *
 * @property list<int> $team_a
 * @property list<int> $team_b
 * @property list<array{a: int, b: int, tiebreak_a?: int|null, tiebreak_b?: int|null}> $sets
 * @property SessionMatchType $match_type
 * @property SessionMatchFormat $format
 */
class CommunitySessionScore extends Model
{
    /** @use HasFactory<CommunitySessionScoreFactory> */
    use HasFactory;

    protected $fillable = [
        'community_session_id',
        'submitted_by',
        'match_type',
        'format',
        'team_a',
        'team_b',
        'sets',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $attributes = [
        'match_type' => 'doubles',
        'format' => 'best_of_3',
    ];

    protected function casts(): array
    {
        return [
            'match_type' => SessionMatchType::class,
            'format' => SessionMatchFormat::class,
            'team_a' => 'array',
            'team_b' => 'array',
            'sets' => 'array',
            'status' => SessionScoreStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CommunitySession::class, 'community_session_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * The side that won more sets, or null when no side is ahead.
     *
     * @return 'a'|'b'|null
     */
    public function winner(): ?string
    {
        return MatchScore::winner($this->format, $this->sets);
    }

    /**
     * "6-4, 6-7(5), [10-7]" — digits only, so it reads the same in every locale.
     */
    public function setsLine(): string
    {
        return MatchScore::line($this->format, $this->sets);
    }
}
