<?php

namespace App\Services\Checkout;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Throwable;

class CheckoutSessionReconciler
{
    public function __construct(private OrderFulfillment $fulfillment) {}

    /**
     * Mark a local incomplete order paid when Stripe reports that Checkout Session as paid.
     *
     * The browser redirect is not trusted. Fulfillment runs only after a server-side retrieve
     * shows payment_status paid and session metadata matches this order and payment.
     */
    public function reconcile(Order $order, string $sessionId): Order
    {
        if ($sessionId === '' || $order->status->isFulfillment() || $order->status !== OrderStatus::Incomplete) {
            return $order;
        }

        if (! filled(config('cashier.secret'))) {
            return $order;
        }

        $session = $this->retrieve($sessionId);

        if ($session === null || ($session->id ?? null) !== $sessionId) {
            return $order;
        }

        if (($session->payment_status ?? null) !== 'paid' || ! $this->metadataMatches($order, $session)) {
            return $order;
        }

        return $this->fulfillment->markPaidFromSession($session) ?? $order->refresh();
    }

    private function retrieve(string $sessionId): ?object
    {
        try {
            $session = Cashier::stripe()->checkout->sessions->retrieve($sessionId);
        } catch (Throwable $exception) {
            Log::warning('Stripe checkout session reconcile failed.', [
                'session_id' => $sessionId,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        $metadata = $this->metadata($session);

        return (object) [
            'id' => $session->id ?? null,
            'payment_status' => $session->payment_status ?? null,
            'payment_intent' => $this->paymentIntentId($session),
            'metadata' => $metadata,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function metadata(object $session): array
    {
        $metadata = $session->metadata ?? [];

        if (is_array($metadata)) {
            return $metadata;
        }

        if (is_object($metadata) && method_exists($metadata, 'toArray')) {
            $array = $metadata->toArray();

            return is_array($array) ? $array : [];
        }

        if (! is_object($metadata)) {
            return [];
        }

        return [
            'order_id' => $metadata->order_id ?? null,
            'payment_id' => $metadata->payment_id ?? null,
        ];
    }

    private function metadataMatches(Order $order, object $session): bool
    {
        $metadata = is_array($session->metadata ?? null) ? $session->metadata : [];
        $orderId = $metadata['order_id'] ?? null;
        $paymentId = $metadata['payment_id'] ?? null;

        if (! is_scalar($orderId) || (string) $orderId !== (string) $order->id) {
            return false;
        }

        if (! is_scalar($paymentId) || (string) $paymentId === '') {
            return false;
        }

        return $order->payments()->whereKey($paymentId)->exists();
    }

    private function paymentIntentId(object $session): ?string
    {
        $paymentIntent = $session->payment_intent ?? null;

        if (is_string($paymentIntent) && $paymentIntent !== '') {
            return $paymentIntent;
        }

        if (is_object($paymentIntent) && isset($paymentIntent->id) && is_string($paymentIntent->id)) {
            return $paymentIntent->id;
        }

        return null;
    }
}
