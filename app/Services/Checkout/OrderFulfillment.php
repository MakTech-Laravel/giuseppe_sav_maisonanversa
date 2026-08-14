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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class OrderFulfillment
{
    public function __construct(
        private EditionAllocator $allocator,
        private EditionInventory $inventory,
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

            if (in_array($locked->status, [OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Delivered], true)) {
                return $locked;
            }

            $this->allocator->allocate($locked);

            $locked->fill([
                'status' => OrderStatus::Paid,
                'stripe_checkout_session_id' => $session->id ?? $locked->stripe_checkout_session_id,
                'stripe_payment_intent_id' => $this->paymentIntentId($session) ?? $locked->stripe_payment_intent_id,
            ])->save();

            $locked->refresh();

            if ($locked->user !== null) {
                Role::findOrCreate(RoleEnum::FOUNDING_CIRCLE->value, GuardEnum::WEB->value);
                $locked->user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);
            }

            return $locked;
        });

        if (! $alreadyPaid) {
            Mail::to($fulfilled->email)
                ->locale($fulfilled->locale)
                ->queue(new OrderConfirmation($fulfilled));

            if ($this->inventory->snapshot()['soldOut']) {
                $this->notifySoldOut();
            }
        }

        return $fulfilled;
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

            if (in_array($locked->status, [OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Delivered], true)) {
                return $locked;
            }

            $this->allocator->release($locked);

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

        $this->allocator->release($order);

        $order->update(['status' => OrderStatus::Canceled]);

        return $order->refresh();
    }

    public function markExpiredBySessionId(?string $sessionId): ?Order
    {
        return $this->markCanceledBySessionId($sessionId);
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
