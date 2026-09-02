<?php

namespace App\Models;

use Database\Factories\FoundingCircleRegisterEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Permanent, append-only ledger of everyone who has ever earned Founding
 * Circle membership. Entries are never updated or deleted by the app —
 * `name` is a snapshot taken at join time and is not re-synced if the
 * user later changes their name, and the row survives role removal or
 * order refunds. Foreign keys are nulled (not cascaded) if the related
 * user/product/order is hard-deleted, so the historical record remains.
 *
 * @use HasFactory<FoundingCircleRegisterEntryFactory>
 */
class FoundingCircleRegisterEntry extends Model
{
    use HasFactory;

    protected $table = 'founding_circle_register';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'name',
        'edition_number',
        'joined_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'edition_number' => 'integer',
            'joined_at' => 'datetime',
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
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
