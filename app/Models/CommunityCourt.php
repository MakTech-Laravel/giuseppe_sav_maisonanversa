<?php

namespace App\Models;

use App\Models\Concerns\TranslatesWithDeepL;
use Database\Factories\CommunityCourtFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunityCourt extends Model
{
    /** @use HasFactory<CommunityCourtFactory> */
    use HasFactory, TranslatesWithDeepL;

    /**
     * @var list<string>
     */
    protected array $translatable = [
        'title',
        'body',
        'location',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'body',
        'location',
        'lat',
        'lng',
        'sort_order',
        'is_published',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Map lat/lng onto the decorative community map (Benelux-ish frame).
     *
     * @return array{top: string, left: string}|null
     */
    public function mapPinPosition(): ?array
    {
        if ($this->lat === null || $this->lng === null) {
            return null;
        }

        $lat = (float) $this->lat;
        $lng = (float) $this->lng;

        $minLat = 49.0;
        $maxLat = 54.5;
        $minLng = 2.0;
        $maxLng = 12.0;

        $top = (($maxLat - $lat) / ($maxLat - $minLat)) * 100;
        $left = (($lng - $minLng) / ($maxLng - $minLng)) * 100;

        $top = max(8, min(88, $top));
        $left = max(8, min(88, $left));

        return [
            'top' => round($top, 1).'%',
            'left' => round($left, 1).'%',
        ];
    }
}
