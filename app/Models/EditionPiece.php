<?php

namespace App\Models;

use App\Enums\EditionPieceStatus;
use App\Enums\FoundingCircleClaimStatus;
use Database\Factories\EditionPieceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EditionPiece extends Model
{
    /** @use HasFactory<EditionPieceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'edition_number',
        'status',
        'order_id',
        'reserved_until',
        'allocated_at',
        'verification_token',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EditionPieceStatus::class,
            'reserved_until' => 'datetime',
            'allocated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EditionPiece $piece): void {
            if (blank($piece->verification_token)) {
                $piece->verification_token = (string) Str::uuid();
            }
        });
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return HasMany<FoundingCircleClaim, $this>
     */
    public function foundingCircleClaims(): HasMany
    {
        return $this->hasMany(FoundingCircleClaim::class);
    }

    public function approvedClaim(): ?FoundingCircleClaim
    {
        if ($this->relationLoaded('foundingCircleClaims')) {
            return $this->foundingCircleClaims
                ->first(fn (FoundingCircleClaim $claim): bool => $claim->status === FoundingCircleClaimStatus::Approved);
        }

        return $this->foundingCircleClaims()
            ->approved()
            ->with('user')
            ->latest('reviewed_at')
            ->first();
    }

    public function isArchive(): bool
    {
        return $this->status === EditionPieceStatus::Archive;
    }

    public function formattedNumber(): string
    {
        if ($this->product !== null) {
            $sequence = $this->product->parseEditionSequence($this->edition_number);

            if ($this->edition_number === $this->product->formatEditionLabel($sequence)) {
                return $this->edition_number;
            }

            return $this->product->formatEditionDigits($sequence);
        }

        return (string) $this->edition_number;
    }

    public function sequenceNumber(): int
    {
        if ($this->product !== null) {
            return $this->product->parseEditionSequence($this->edition_number);
        }

        return (int) preg_replace('/\D+/', '', $this->edition_number);
    }
}
