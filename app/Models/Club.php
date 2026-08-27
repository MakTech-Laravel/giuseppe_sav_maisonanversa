<?php

namespace App\Models;

use App\Enums\ClubStatus;
use App\Enums\SessionSport;
use Database\Factories\ClubFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * A padel/tennis venue members can attach a session to.
 *
 * Deliberately does NOT use TranslatesWithDeepL: the trait auto-discovers
 * string columns and would push club names and street addresses through DeepL.
 */
class Club extends Model
{
    /** @use HasFactory<ClubFactory> */
    use HasFactory;

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
            'approved_at' => 'datetime',
        ];
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
        return collect([$this->street, trim($this->postal_code.' '.$this->city)])
            ->filter(fn (?string $part): bool => filled(trim((string) $part)))
            ->implode(', ');
    }

    public function imageUrl(): ?string
    {
        return $this->image_path === null || $this->image_path === ''
            ? null
            : Storage::disk('public')->url($this->image_path);
    }

    /**
     * Shape shared with the member-facing session pages.
     *
     * @return array{id: int, name: string, city: string, address: string, is_partner: bool, image_url: string|null, sports: list<string>}
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
