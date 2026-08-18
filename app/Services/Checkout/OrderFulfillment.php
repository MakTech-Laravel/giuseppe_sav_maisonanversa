<?php

namespace App\Services\Checkout;

use App\Enums\GuardEnum;
use App\Enums\OrderStatus;
use App\Enums\RoleEnum;
use App\Mail\OrderConfirmation;
use App\Mail\SoldOutNotice;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Services\Edition\EditionAllocator;
use App\Services\Edition\EditionInventory;
use App\Services\Edition\SimpleStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Cashier\Cashier;
use Spatie\Permission\Models\Role;
use Stripe\Exception\ApiErrorException;

class OrderFulfillment
{
    public function __construct(
        private EditionAllocator $allocator,
        private EditionInventory $inventory,
        private SimpleStock $simpleStock,
    ) {}

    /**
     * Mark an order paid from a Stripe Checkout Session (idempotent).
     */
    public function markPaidFromSession(object $session): ?Order
    {
        $order = $this->findOrderForSession($session);

        if ($order === null) {
            return null;
        }

        if (($session->payment_status ?? null) !== 'paid') {
            return $order;
        }

        $alreadyPaid = $order->status === OrderStatus::Paid
            || $order->status === OrderStatus::Shipped
            || $order->status === OrderStatus::Delivered;

        $fulfilled = DB::transaction(function () use ($order, $session): Order {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $locked->loadMissing('product');

            if (in_array($locked->status, [OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Delivered], true)) {
                return $locked;
            }

            if ($locked->product?->isLimitedEdition()) {
                $this->allocator->allocate($locked);
            }

            $locked->fill([
                'status' => OrderStatus::Paid,
                'stripe_checkout_session_id' => $session->id ?? $locked->stripe_checkout_session_id,
                'stripe_payment_intent_id' => $this->paymentIntentId($session) ?? $locked->stripe_payment_intent_id,
            ])->save();

            $locked->refresh();
            $locked->loadMissing('product', 'user');

            if ($locked->user !== null && $locked->product?->grants_founding_circle) {
                Role::findOrCreate(RoleEnum::FOUNDING_CIRCLE->value, GuardEnum::WEB->value);
                $locked->user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);
            }

            return $locked;
        });

        if (! $alreadyPaid) {
            Mail::to($fulfilled->email)
                ->locale($fulfilled->locale)
                ->queue(new OrderConfirmation($fulfilled));

            if ($this->inventory->snapshot($fulfilled->product)['soldOut']) {
                $this->notifySoldOut();
            }
        }

        return $fulfilled;
    }

    /**
     * Create a Stripe refund (when a PaymentIntent exists), restore inventory, and mark refunded.
     *
     * @throws ApiErrorException
     */
    public function refund(Order $order): Order
    {
        if ($order->status === OrderStatus::Refunded) {
            return $order;
        }

        if (filled($order->stripe_payment_intent_id)) {
            Cashier::stripe()->refunds->create([
                'payment_intent' => $order->stripe_payment_intent_id,
            ]);
        }

        return $this->markRefunded($order);
    }

    /**
     * Mark an order refunded from a Stripe charge.refunded webhook (idempotent, no Stripe call).
     */
    public function markRefundedFromPaymentIntent(?string $paymentIntentId): ?Order
    {
        if ($paymentIntentId === null || $paymentIntentId === '') {
            return null;
        }

        $order = Order::query()
            ->where('stripe_payment_intent_id', $paymentIntentId)
            ->first();

        if ($order === null) {
            return null;
        }

        return $this->markRefunded($order);
    }

    /**
     * Restore inventory and set status to refunded (idempotent).
     */
    public function markRefunded(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $locked->loadMissing('product');

            if ($locked->status === OrderStatus::Refunded) {
                return $locked;
            }

            $this->releaseInventoryOnRefund($locked);

            $locked->fill(['status' => OrderStatus::Refunded])->save();

            return $locked->refresh();
        });
    }

    /**
     * Mark an order failed from a Stripe Checkout Session (idempotent).
     */
    public function markFailedFromSession(object $session): ?Order
    {
        $order = $this->findOrderForSession($session);

        if ($order === null) {
            return null;
        }

        return DB::transaction(function () use ($order, $session): Order {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $locked->loadMissing('product');

            if (in_array($locked->status, [OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Delivered], true)) {
                return $locked;
            }

            $this->releaseInventory($locked);

            $locked->fill([
                'status' => OrderStatus::Failed,
                'stripe_checkout_session_id' => $session->id ?? $locked->stripe_checkout_session_id,
                'stripe_payment_intent_id' => $this->paymentIntentId($session) ?? $locked->stripe_payment_intent_id,
            ])->save();

            return $locked->refresh();
        });
    }

    /**
     * Best-effort cancel for an incomplete checkout session.
     */
    public function markCanceledBySessionId(?string $sessionId): ?Order
    {
        if ($sessionId === null || $sessionId === '') {
            return null;
        }

        $order = Order::query()
            ->where('stripe_checkout_session_id', $sessionId)
            ->first();

        if ($order === null || $order->status !== OrderStatus::Incomplete) {
            return $order;
        }

        $order->loadMissing('product');
        $this->releaseInventory($order);

        $order->update(['status' => OrderStatus::Canceled]);

        return $order->refresh();
    }

    public function markExpiredBySessionId(?string $sessionId): ?Order
    {
        return $this->markCanceledBySessionId($sessionId);
    }

    private function releaseInventory(Order $order): void
    {
        if ($order->product?->isLimitedEdition()) {
            $this->allocator->release($order);

            return;
        }

        if ($order->product?->isSimple() && $order->status === OrderStatus::Incomplete) {
            $this->simpleStock->release($order->product);
        }
    }

    private function releaseInventoryOnRefund(Order $order): void
    {
        if ($order->product?->isLimitedEdition()) {
            $this->allocator->releaseOnRefund($order);

            return;
        }

        if ($order->product?->isSimple()) {
            $this->simpleStock->releaseOnRefund($order->product);
        }
    }

    private function notifySoldOut(): void
    {
        NewsletterSubscriber::query()
            ->where('status', 'subscribed')
            ->each(fn (NewsletterSubscriber $subscriber) => Mail::to($subscriber->email)
                ->locale($subscriber->locale)
                ->queue(new SoldOutNotice($subscriber)));
    }

    private function findOrderForSession(object $session): ?Order
    {
        $metadata = $session->metadata ?? [];
        $orderId = null;

        if (is_array($metadata)) {
            $orderId = $metadata['order_id'] ?? null;
        } elseif (is_object($metadata)) {
            $orderId = $metadata['order_id'] ?? $metadata->order_id ?? null;
        }

        if ($orderId !== null && $orderId !== '') {
            $order = Order::query()->find($orderId);

            if ($order !== null) {
                return $order;
            }
        }

        $sessionId = $session->id ?? null;

        if ($sessionId === null || $sessionId === '') {
            return null;
        }

        return Order::query()
            ->where('stripe_checkout_session_id', $sessionId)
            ->first();
    }

    private function paymentIntentId(object $session): ?string
    {
        $paymentIntent = $session->payment_intent ?? null;

        if (is_string($paymentIntent) && $paymentIntent !== '') {
            return $paymentIntent;
        }

        if (is_object($paymentIntent) && isset($paymentIntent->id)) {
            return (string) $paymentIntent->id;
        }

        return null;
    }
}
