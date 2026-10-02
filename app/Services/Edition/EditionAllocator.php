<?php

namespace App\Services\Edition;

use App\Enums\EditionPieceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\EditionSoldOutException;
use App\Exceptions\EditionUnavailableException;
use App\Models\EditionPiece;
use App\Models\Order;
use App\Models\OrderStatusEvent;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Stripe\CheckoutSessionExpirer;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class EditionAllocator
{
    public function __construct(
        private EditionInventory $inventory,
        private CheckoutSessionExpirer $checkoutSessions,
    ) {}

    public function holdForUser(User $user, Product $product, int $editionPieceId): EditionPiece
    {
        return DB::transaction(function () use ($user, $product, $editionPieceId): EditionPiece {
            $piece = EditionPiece::query()
                ->whereKey($editionPieceId)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            if ($piece === null || $piece->isArchive()) {
                throw new EditionUnavailableException;
            }

            $ownedByUser = $piece->isHeldBy($user);

            if ($piece->status !== EditionPieceStatus::Available && ! $ownedByUser) {
                throw new EditionUnavailableException;
            }

            $this->releaseOtherUserHolds($user, $product, $piece->id);

            $piece->fill([
                'status' => EditionPieceStatus::Reserved,
                'reserved_by_user_id' => $user->id,
                'reserved_until' => $this->reservedUntil(),
            ])->save();

            $this->inventory->bust($product);

            return $piece->refresh();
        });
    }

    public function hold(Order $order, ?int $editionPieceId = null): EditionPiece
    {
        return DB::transaction(function () use ($order, $editionPieceId): EditionPiece {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->edition_piece_id !== null) {
                $existing = EditionPiece::query()
                    ->whereKey($lockedOrder->edition_piece_id)
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null && $existing->status !== EditionPieceStatus::Archive) {
                    if ($existing->status === EditionPieceStatus::Available || $existing->status === EditionPieceStatus::Reserved) {
                        $existing->fill([
                            'status' => EditionPieceStatus::Reserved,
                            'order_id' => $lockedOrder->id,
                            'reserved_by_user_id' => $lockedOrder->user_id,
                            'reserved_until' => $this->reservedUntil(),
                        ])->save();

                        $this->inventory->bust($lockedOrder->product);
                    }

                    return $existing->refresh();
                }
            }

            $piece = $editionPieceId !== null
                ? $this->lockPreferredAvailable($lockedOrder, $editionPieceId)
                : $this->lockLowestAvailable($lockedOrder);

            $this->detachPreviousIncompleteOrder($piece, $lockedOrder);

            $piece->fill([
                'status' => EditionPieceStatus::Reserved,
                'order_id' => $lockedOrder->id,
                'reserved_by_user_id' => $lockedOrder->user_id,
                'reserved_until' => $this->reservedUntil(),
            ])->save();

            $lockedOrder->forceFill([
                'edition_piece_id' => $piece->id,
            ])->save();

            $this->inventory->bust($lockedOrder->product);

            return $piece->refresh();
        });
    }

    public function allocate(Order $order): EditionPiece
    {
        return DB::transaction(function () use ($order): EditionPiece {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->edition_piece_id !== null) {
                $held = EditionPiece::query()
                    ->whereKey($lockedOrder->edition_piece_id)
                    ->lockForUpdate()
                    ->first();

                if ($held !== null && $held->status === EditionPieceStatus::Allocated) {
                    return $held;
                }

                if ($held !== null && $held->status !== EditionPieceStatus::Archive) {
                    return $this->markAllocated($lockedOrder, $held);
                }
            }

            $heldForOrder = EditionPiece::query()
                ->where('order_id', $lockedOrder->id)
                ->where('status', EditionPieceStatus::Reserved)
                ->lockForUpdate()
                ->first();

            if ($heldForOrder !== null) {
                return $this->markAllocated($lockedOrder, $heldForOrder);
            }

            $available = EditionPiece::query()
                ->where('product_id', $lockedOrder->product_id)
                ->where('status', EditionPieceStatus::Available)
                ->orderBy('edition_number')
                ->lockForUpdate()
                ->first();

            if ($available === null) {
                throw new EditionSoldOutException;
            }

            return $this->markAllocated($lockedOrder, $available);
        });
    }

    public function release(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->first();

            if ($lockedOrder === null || $lockedOrder->edition_piece_id === null) {
                return;
            }

            $piece = EditionPiece::query()
                ->whereKey($lockedOrder->edition_piece_id)
                ->lockForUpdate()
                ->first();

            if ($piece === null || $piece->status === EditionPieceStatus::Allocated || $piece->isArchive()) {
                return;
            }

            $this->clearHold($piece);

            $lockedOrder->forceFill([
                'edition_piece_id' => null,
                'edition_number' => null,
            ])->save();

            $this->inventory->bust($lockedOrder->product);
        });
    }

    /**
     * Return a reserved or allocated piece to available stock after incomplete cancel/fail/expire.
     */
    public function releaseOnCancel(Order $order): void
    {
        $this->returnLinkedPieceToAvailable($order);
    }

    /**
     * Return a reserved or allocated piece to available stock after a refund.
     */
    public function releaseOnRefund(Order $order): void
    {
        $this->returnLinkedPieceToAvailable($order);
    }

    private function returnLinkedPieceToAvailable(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->first();

            if ($lockedOrder === null || $lockedOrder->edition_piece_id === null) {
                return;
            }

            $piece = EditionPiece::query()
                ->whereKey($lockedOrder->edition_piece_id)
                ->lockForUpdate()
                ->first();

            if ($piece === null || $piece->isArchive()) {
                return;
            }

            if (! in_array($piece->status, [EditionPieceStatus::Reserved, EditionPieceStatus::Allocated], true)) {
                return;
            }

            $piece->fill([
                'status' => EditionPieceStatus::Available,
                'order_id' => null,
                'reserved_by_user_id' => null,
                'reserved_until' => null,
                'allocated_at' => null,
            ])->save();

            $lockedOrder->forceFill([
                'edition_piece_id' => null,
                'edition_number' => null,
            ])->save();

            $this->inventory->bust($lockedOrder->product);
        });
    }

    public function releaseExpiredHolds(): int
    {
        $released = 0;

        EditionPiece::query()
            ->where('status', EditionPieceStatus::Reserved)
            ->whereNotNull('reserved_until')
            ->where('reserved_until', '<', now())
            ->orderBy('id')
            ->each(function (EditionPiece $piece) use (&$released): void {
                DB::transaction(function () use ($piece, &$released): void {
                    $locked = EditionPiece::query()->whereKey($piece->id)->lockForUpdate()->first();

                    if ($locked === null || $locked->status !== EditionPieceStatus::Reserved) {
                        return;
                    }

                    if ($locked->reserved_until === null || $locked->reserved_until->isFuture()) {
                        return;
                    }

                    $this->releaseHeldPiece($locked);
                    $released++;
                });
            });

        return $released;
    }

    private function holdMinutes(): int
    {
        return max(1, (int) config('maison.checkout.hold_minutes', 15));
    }

    private function reservedUntil(): CarbonInterface
    {
        return now()->addMinutes($this->holdMinutes());
    }

    private function releaseOtherUserHolds(User $user, Product $product, int $exceptPieceId): void
    {
        EditionPiece::query()
            ->where('product_id', $product->id)
            ->where('reserved_by_user_id', $user->id)
            ->where('status', EditionPieceStatus::Reserved)
            ->whereKeyNot($exceptPieceId)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->each(function (EditionPiece $held): void {
                $this->releaseHeldPiece($held);
            });
    }

    private function releaseHeldPiece(EditionPiece $locked): void
    {
        $orderId = $locked->order_id;
        $productId = $locked->product_id;

        $this->clearHold($locked);

        if ($orderId !== null) {
            /** @var Order|null $lockedOrder */
            $lockedOrder = Order::query()
                ->whereKey($orderId)
                ->lockForUpdate()
                ->first();

            if ($lockedOrder !== null && $lockedOrder->status === OrderStatus::Incomplete) {
                $sessionId = $lockedOrder->stripe_checkout_session_id;

                $lockedOrder->fill([
                    'status' => OrderStatus::Canceled,
                    'edition_piece_id' => null,
                ])->save();

                Payment::query()
                    ->where('order_id', $lockedOrder->id)
                    ->where('status', PaymentStatus::Pending)
                    ->update(['status' => PaymentStatus::Canceled->value]);

                OrderStatusEvent::query()->firstOrCreate(
                    [
                        'order_id' => $lockedOrder->id,
                        'status' => OrderStatus::Canceled,
                    ],
                    [
                        'message' => __('Reservering verlopen. De editie is opnieuw beschikbaar.'),
                        'user_id' => null,
                    ],
                );

                $this->checkoutSessions->expire(is_string($sessionId) ? $sessionId : null);
            } elseif ($lockedOrder !== null && $lockedOrder->edition_number === null) {
                $lockedOrder->forceFill(['edition_piece_id' => null])->save();
            }
        }

        if ($productId !== null) {
            $product = Product::query()->find($productId);

            if ($product !== null) {
                $this->inventory->bust($product);
            }
        }
    }

    private function clearHold(EditionPiece $piece): void
    {
        $piece->fill([
            'status' => EditionPieceStatus::Available,
            'order_id' => null,
            'reserved_by_user_id' => null,
            'reserved_until' => null,
        ])->save();
    }

    private function detachPreviousIncompleteOrder(EditionPiece $piece, Order $incoming): void
    {
        if ($piece->order_id === null || (int) $piece->order_id === (int) $incoming->id) {
            return;
        }

        /** @var Order|null $previous */
        $previous = Order::query()->whereKey($piece->order_id)->lockForUpdate()->first();

        if ($previous === null) {
            return;
        }

        if ((int) $previous->user_id !== (int) $incoming->user_id || $previous->status !== OrderStatus::Incomplete) {
            throw new EditionUnavailableException;
        }

        $sessionId = $previous->stripe_checkout_session_id;

        $previous->fill([
            'status' => OrderStatus::Canceled,
            'edition_piece_id' => null,
        ])->save();

        Payment::query()
            ->where('order_id', $previous->id)
            ->where('status', PaymentStatus::Pending)
            ->update(['status' => PaymentStatus::Canceled->value]);

        OrderStatusEvent::query()->firstOrCreate(
            [
                'order_id' => $previous->id,
                'status' => OrderStatus::Canceled,
            ],
            [
                'message' => __('Reservering verlopen. De editie is opnieuw beschikbaar.'),
                'user_id' => null,
            ],
        );

        $this->checkoutSessions->expire(is_string($sessionId) ? $sessionId : null);
    }

    private function lockPreferredAvailable(Order $order, int $editionPieceId): EditionPiece
    {
        $piece = EditionPiece::query()
            ->whereKey($editionPieceId)
            ->where('product_id', $order->product_id)
            ->lockForUpdate()
            ->first();

        if ($piece === null || $piece->isArchive()) {
            throw new EditionUnavailableException;
        }

        if ($piece->status === EditionPieceStatus::Available) {
            return $piece;
        }

        if ($piece->isHeldBy($order->user)) {
            return $piece;
        }

        throw new EditionUnavailableException;
    }

    private function lockLowestAvailable(Order $order): EditionPiece
    {
        $piece = EditionPiece::query()
            ->where('product_id', $order->product_id)
            ->where('status', EditionPieceStatus::Available)
            ->orderBy('edition_number')
            ->lockForUpdate()
            ->first();

        if ($piece === null) {
            throw new EditionSoldOutException;
        }

        return $piece;
    }

    private function markAllocated(Order $order, EditionPiece $piece): EditionPiece
    {
        $piece->fill([
            'status' => EditionPieceStatus::Allocated,
            'order_id' => $order->id,
            'reserved_by_user_id' => null,
            'reserved_until' => null,
            'allocated_at' => now(),
        ])->save();

        $order->forceFill([
            'edition_piece_id' => $piece->id,
            'edition_number' => $piece->sequenceNumber(),
        ])->save();

        $this->inventory->bust($order->product);

        return $piece->refresh();
    }
}
