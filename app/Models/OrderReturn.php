<?php

namespace App\Models;

use App\Enums\ReturnReason;
use App\Enums\ReturnStatus;
use Database\Factories\OrderReturnFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderReturn extends Model
{
    /** @use HasFactory<OrderReturnFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'number',
        'order_id',
        'user_id',
        'status',
        'reason',
        'comment',
        'maison_pays_return_shipping',
        'expected_refund_amount',
        'refund_amount',
        'refund_reason',
        'stripe_refund_id',
        'carrier',
        'tracking_number',
        'requested_at',
        'ship_by',
        'refund_due_by',
        'received_at',
        'resolved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReturnStatus::class,
            'reason' => ReturnReason::class,
            'maison_pays_return_shipping' => 'boolean',
            'expected_refund_amount' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'requested_at' => 'datetime',
            'ship_by' => 'datetime',
            'refund_due_by' => 'datetime',
            'received_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ReturnItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class);
    }

    /**
     * @return HasMany<ReturnPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(ReturnPhoto::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<ReturnEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(ReturnEvent::class)->orderBy('created_at');
    }

    public function includesFoundingRacket(): bool
    {
        $this->loadMissing('order.product', 'items.product');

        if ($this->order?->product?->slug === Product::FOUNDING_SLUG) {
            return true;
        }

        return $this->items->contains(
            fn (ReturnItem $item): bool => $item->product?->slug === Product::FOUNDING_SLUG,
        );
    }
}
