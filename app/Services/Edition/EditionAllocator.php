<?php

namespace App\Services\Edition;

use App\Enums\EditionPieceStatus;
use App\Exceptions\EditionSoldOutException;
use App\Models\EditionPiece;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class EditionAllocator
{
    public function hold(Order $order): EditionPiece
    {
        return DB::transaction(function () use ($order): EditionPiece {
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
                            'reserved_until' => now()->addMinutes(30),
                        ])->save();

                        app(EditionInventory::class)->bust($lockedOrder->product);
                    }

                    return $existing->refresh();
                }
            }

            $piece = EditionPiece::query()
                ->where('product_id', $lockedOrder->product_id)
                ->where('status', EditionPieceStatus::Available)
                ->orderBy('edition_number')
                ->lockForUpdate()
                ->first();

            if ($piece === null) {
                throw new EditionSoldOutException;
            }

            $piece->fill([
                'status' => EditionPieceStatus::Reserved,
                'order_id' => $lockedOrder->id,
                'reserved_until' => now()->addMinutes(30),
            ])->save();

            $lockedOrder->forceFill([
                'edition_piece_id' => $piece->id,
            ])->save();

            app(EditionInventory::class)->bust($lockedOrder->product);

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

            $piece->fill([
                'status' => EditionPieceStatus::Available,
                'order_id' => null,
                'reserved_until' => null,
            ])->save();

            $lockedOrder->forceFill([
                'edition_piece_id' => null,
                'edition_number' => null,
            ])->save();

            app(EditionInventory::class)->bust($lockedOrder->product);
        });
    }

    /**
     * Return a reserved or allocated piece to available stock after a refund.
     */
    public function releaseOnRefund(Order $order): void
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
                'reserved_until' => null,
                'allocated_at' => null,
            ])->save();

            $lockedOrder->forceFill([
                'edition_piece_id' => null,
                'edition_number' => null,
            ])->save();

            app(EditionInventory::class)->bust($lockedOrder->product);
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

                    $orderId = $locked->order_id;
                    $productId = $locked->product_id;

                    $locked->fill([
                        'status' => EditionPieceStatus::Available,
                        'order_id' => null,
                        'reserved_until' => null,
                    ])->save();

                    if ($orderId !== null) {
                        Order::query()
                            ->whereKey($orderId)
                            ->whereNull('edition_number')
                            ->update(['edition_piece_id' => null]);
                    }

                    $released++;

                    if ($productId !== null) {
                        $product = Product::query()->find($productId);

                        if ($product !== null) {
                            app(EditionInventory::class)->bust($product);
                        }
                    }
                });
            });

        return $released;
    }

    private function markAllocated(Order $order, EditionPiece $piece): EditionPiece
    {
        $piece->fill([
            'status' => EditionPieceStatus::Allocated,
            'order_id' => $order->id,
            'reserved_until' => null,
            'allocated_at' => now(),
        ])->save();

        $order->forceFill([
            'edition_piece_id' => $piece->id,
            'edition_number' => $piece->edition_number,
        ])->save();

        app(EditionInventory::class)->bust($order->product);

        return $piece->refresh();
    }
}
