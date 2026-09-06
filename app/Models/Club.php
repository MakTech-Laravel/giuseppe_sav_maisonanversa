<?php

namespace App\Models;

use App\Enums\ClubStatus;
use App\Enums\CornerPipelineStatus;
use App\Enums\SessionSport;
use App\Models\Concerns\TranslatesWithDeepL;
use Database\Factories\ClubFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * A padel/tennis venue members can attach a session to, plus optional Club Corner
 * and /corner marketing attributes.
 *
 * DeepL is limited to corner_* copy via $translatable. Venue proper nouns
 * (name, street, city, country) are never translated.
 */
class Club extends Model
{
    /** @use HasFactory<ClubFactory> */
    use HasFactory, TranslatesWithDeepL;

    /**
     * @var list<string>
     */
    protected array $translatable = [
        'corner_title',
        'corner_body',
        'corner_location',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'sports',
        'street',
        'postal_code',
        'city',
        'country',
        'lat',
        'lng',
        'website',
        'phone',
        'image_path',
        'status',
        'is_partner',
        'is_session_venue',
        'show_on_corner_page',
        'corner_pipeline_status',
        'has_corner',
        'corner_published',
        'corner_title',
        'corner_body',
        'corner_location',
        'sort_order',
        'submitted_by_id',
        'approved_by_id',
        'approved_at',
        'merged_into_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sports' => 'array',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'status' => ClubStatus::class,
            'is_partner' => 'boolean',
            'is_session_venue' => 'boolean',
            'show_on_corner_page' => 'boolean',
            'corner_pipeline_status' => CornerPipelineStatus::class,
            'has_corner' => 'boolean',
            'corner_published' => 'boolean',
            'sort_order' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public function translationTargetLocales(): array
    {
        return config('maison.locales');
    }

    public function translationUsesAutoDetect(): bool
    {
        return true;
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_id');
    }

    public function duplicates(): HasMany
    {
        return $this->hasMany(self::class, 'merged_into_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CommunitySession::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', ClubStatus::Approved);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeSessionVenues(Builder $query): Builder
    {
        return $query->approved()->where('is_session_venue', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeCornerPage(Builder $query): Builder
    {
        return $query->where('show_on_corner_page', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublishedCorners(Builder $query): Builder
    {
        return $query->where('has_corner', true)
            ->where('corner_published', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * Name, city and postal code all match — members search however they think.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeMatching(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        return $query->where(function (Builder $inner) use ($like): void {
            $inner->where('name', 'like', $like)
                ->orWhere('city', 'like', $like)
                ->orWhere('postal_code', 'like', $like)
                ->orWhere('street', 'like', $like);
        });
    }

    public function isApproved(): bool
    {
        return $this->status === ClubStatus::Approved;
    }

    public function supports(SessionSport $sport): bool
    {
        return in_array($sport->value, (array) $this->sports, true);
    }

    public function addressLine(): string
    {
        return collect([$this->street, trim(($this->postal_code ?? '').' '.($this->city ?? ''))])
            ->filter(fn (?string $part): bool => filled(trim((string) $part)))
            ->implode(', ');
    }

    public function imageUrl(): ?string
    {
        return $this->image_path === null || $this->image_path === ''
            ? null
            : Storage::disk('public')->url($this->image_path);
    }

    public function countryLabel(): string
    {
        return match (mb_strtoupper((string) $this->country)) {
            'BE' => 'België',
            'NL' => 'Nederland',
            'DE' => 'Duitsland',
            'FR' => 'Frankrijk',
            default => (string) $this->country,
        };
    }

    public function cornerTitle(?string $locale = null): string
    {
        $translated = $this->translated('corner_title', $locale);

        return filled($translated) ? $translated : (string) $this->name;
    }

    public function cornerBody(?string $locale = null): string
    {
        return $this->translated('corner_body', $locale);
    }

    public function cornerLocation(?string $locale = null): string
    {
        $translated = $this->translated('corner_location', $locale);

        if (filled($translated)) {
            return $translated;
        }

        return collect([$this->city, $this->countryLabel()])
            ->filter(fn (?string $part): bool => filled($part))
            ->implode(', ');
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

    /**
     * Shape shared with the member-facing session pages.
     *
     * @return array{id: int, name: string, city: string|null, address: string, is_partner: bool, image_url: string|null, sports: list<string>}
     */
    public function toCardArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'city' => $this->city,
            'address' => $this->addressLine(),
            'is_partner' => $this->is_partner,
            'image_url' => $this->imageUrl(),
            'sports' => array_values((array) $this->sports),
        ];
    }
}
