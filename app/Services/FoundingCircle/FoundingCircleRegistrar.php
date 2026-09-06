<?php

namespace App\Services\FoundingCircle;

use App\Models\FoundingCircleRegisterEntry;
use App\Models\Order;
use App\Models\User;

/**
 * Writes to the permanent Founding Circle name register.
 *
 * The register is append-only: once a member has an entry it is never
 * updated or deleted, regardless of later role removal, refunds, or
 * profile changes. `register()` is safe to call repeatedly for the same
 * user (e.g. idempotent order webhooks, repeated admin assignment).
 */
class FoundingCircleRegistrar
{
    public function register(
        User $user,
        ?Order $order = null,
        ?int $editionNumber = null,
        ?int $productId = null,
    ): FoundingCircleRegisterEntry {
        return FoundingCircleRegisterEntry::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'product_id' => $order?->product_id ?? $productId,
                'order_id' => $order?->id,
                'name' => $user->name,
                'edition_number' => $order?->edition_number ?? $editionNumber,
                'joined_at' => now(),
            ],
        );
    }
}
