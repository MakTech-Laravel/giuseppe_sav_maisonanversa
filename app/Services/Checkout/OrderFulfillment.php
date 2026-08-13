<?php

namespace App\Services\Checkout;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderFulfillment
{
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

        return DB::transaction(function () use ($order, $session): Order {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === OrderStatus::Paid) {
                return $locked;
            }

            $locked->fill([
                'status' => OrderStatus::Paid,
                'stripe_checkout_session_id' => $session->id ?? $locked->stripe_checkout_session_id,
                'stripe_payment_intent_id' => $this->paymentIntentId($session) ?? $locked->stripe_payment_intent_id,
            ])->save();

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

            if ($locked->status === OrderStatus::Paid) {
                return $locked;
            }

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

        $order->update(['status' => OrderStatus::Canceled]);

        return $order->refresh();
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
