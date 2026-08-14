<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'edition_piece_id',
        'status',
        'name',
        'email',
        'locale',
        'phone',
        'edition_number',
        'monogram',
        'gift_wrap',
        'gift_message',
        'currency',
        'amount',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'shipped_at',
        'delivered_at',
        'follow_up_sent_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'edition_number' => 'integer',
            'gift_wrap' => 'boolean',
            'amount' => 'integer',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'follow_up_sent_at' => 'datetime',
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
     * @return BelongsTo<EditionPiece, $this>
     */
    public function editionPiece(): BelongsTo
    {
        return $this->belongsTo(EditionPiece::class);
    }

    public function isPaid(): bool
    {
        return in_array($this->status, [
            OrderStatus::Paid,
            OrderStatus::Shipped,
            OrderStatus::Delivered,
        ], true);
    }

    public function reference(): string
    {
        return 'MA-'.($this->created_at?->format('Y') ?? now()->format('Y')).'-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }
}
