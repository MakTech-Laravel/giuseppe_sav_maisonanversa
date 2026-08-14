<?php

namespace App\Models;

use App\Enums\EditionPieceStatus;
use Database\Factories\EditionPieceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
            'edition_number' => 'integer',
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

    public function isArchive(): bool
    {
        return $this->status === EditionPieceStatus::Archive;
    }

    public function formattedNumber(): string
    {
        return str_pad((string) $this->edition_number, 3, '0', STR_PAD_LEFT);
    }
}
