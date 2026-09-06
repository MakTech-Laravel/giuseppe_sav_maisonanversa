<?php

namespace App\Models;

use App\Enums\FoundingCircleClaimSource;
use App\Enums\FoundingCircleClaimStatus;
use Database\Factories\FoundingCircleClaimFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @use HasFactory<FoundingCircleClaimFactory>
 */
class FoundingCircleClaim extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'edition_piece_id',
        'order_id',
        'edition_number',
        'status',
        'source',
        'admin_note',
        'reviewed_by_id',
        'reviewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'edition_number' => 'integer',
            'status' => FoundingCircleClaimStatus::class,
            'source' => FoundingCircleClaimSource::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<EditionPiece, $this>
     */
    public function editionPiece(): BelongsTo
    {
        return $this->belongsTo(EditionPiece::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    /**
     * @param  Builder<FoundingCircleClaim>  $query
     * @return Builder<FoundingCircleClaim>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', FoundingCircleClaimStatus::Pending);
    }

    /**
     * @param  Builder<FoundingCircleClaim>  $query
     * @return Builder<FoundingCircleClaim>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', FoundingCircleClaimStatus::Approved);
    }

    /**
     * @param  Builder<FoundingCircleClaim>  $query
     * @return Builder<FoundingCircleClaim>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            FoundingCircleClaimStatus::Pending,
            FoundingCircleClaimStatus::Approved,
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === FoundingCircleClaimStatus::Pending;
    }

    public function paddedEditionNumber(): string
    {
        return str_pad((string) $this->edition_number, 3, '0', STR_PAD_LEFT);
    }
}
