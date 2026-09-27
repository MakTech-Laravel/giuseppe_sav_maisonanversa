<?php

namespace App\Models;

use App\Enums\RegisterVisibility;
use Database\Factories\FoundingCircleRegisterEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Live Founding Circle place for Heritage No.001.
 *
 * One row per member. `edition_number` is the same number as the racket,
 * certificate, and Heritage Passport. `name` is a snapshot taken when the
 * place was inscribed; the public register reads the account name instead.
 * Visibility, consent, and the row itself change when the member updates
 * their listing, an admin hides the entry, or the order is refunded.
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
        'register_visibility',
        'register_consent_at',
        'register_hidden_by_admin',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'register_visibility' => 'private',
        'register_hidden_by_admin' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'edition_number' => 'integer',
            'joined_at' => 'datetime',
            'register_visibility' => RegisterVisibility::class,
            'register_consent_at' => 'datetime',
            'register_hidden_by_admin' => 'boolean',
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

    public function showsPrivateLabel(): bool
    {
        return $this->register_hidden_by_admin
            || $this->register_visibility === RegisterVisibility::Private;
    }
}
