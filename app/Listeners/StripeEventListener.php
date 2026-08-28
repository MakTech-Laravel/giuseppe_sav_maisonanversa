<?php

namespace App\Listeners;

use App\Services\Checkout\OrderFulfillment;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

class StripeEventListener
{
    public function __construct(private OrderFulfillment $fulfillment) {}

    /**
     * Handle received Stripe webhooks from Cashier.
     */
    public function handle(WebhookReceived $event): void
    {
        $type = $event->payload['type'] ?? null;

        if (! is_string($type)) {
            return;
        }

        $object = $event->payload['data']['object'] ?? [];
        $sessionId = is_array($object) ? ($object['id'] ?? null) : null;
        $metadata = is_array($object) ? ($object['metadata'] ?? []) : [];

        Log::info('Stripe webhook received.', [
            'type' => $type,
            'session_id' => is_string($sessionId) ? $sessionId : null,
            'order_id' => is_array($metadata) ? ($metadata['order_id'] ?? null) : null,
            'payment_id' => is_array($metadata) ? ($metadata['payment_id'] ?? null) : null,
        ]);

        match ($type) {
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded' => $this->handleCheckoutPaid($event->payload),
            'checkout.session.async_payment_failed' => $this->handleCheckoutFailed($event->payload),
            'checkout.session.expired' => $this->handleCheckoutExpired($event->payload),
            'charge.refunded' => $this->handleChargeRefunded($event->payload),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleCheckoutPaid(array $payload): void
    {
        $session = $this->sessionFromPayload($payload);

        if ($session === null) {
            return;
        }

        if (($session->payment_status ?? null) !== 'paid') {
            Log::info('Stripe checkout session ignored (not paid).', [
                'session_id' => $session->id ?? null,
                'payment_status' => $session->payment_status ?? null,
            ]);

            return;
        }

        $order = $this->fulfillment->markPaidFromSession($session);

        Log::info('Stripe checkout session marked paid.', [
            'session_id' => $session->id ?? null,
            'order_id' => $order?->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleCheckoutFailed(array $payload): void
    {
        $session = $this->sessionFromPayload($payload);

        if ($session === null) {
            return;
        }

        $order = $this->fulfillment->markFailedFromSession($session);

        Log::info('Stripe checkout session marked failed.', [
            'session_id' => $session->id ?? null,
            'order_id' => $order?->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleCheckoutExpired(array $payload): void
    {
        $session = $this->sessionFromPayload($payload);

        if ($session === null) {
            return;
        }

        $order = $this->fulfillment->markExpiredBySessionId($session->id ?? null);

        Log::info('Stripe checkout session marked expired/canceled.', [
            'session_id' => $session->id ?? null,
            'order_id' => $order?->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleChargeRefunded(array $payload): void
    {
        $object = $payload['data']['object'] ?? null;

        if (! is_array($object)) {
            return;
        }

        $paymentIntent = $object['payment_intent'] ?? null;

        if (! is_string($paymentIntent) || $paymentIntent === '') {
            return;
        }

        $order = $this->fulfillment->markRefundedFromPaymentIntent($paymentIntent);

        Log::info('Stripe charge marked refunded.', [
            'payment_intent' => $paymentIntent,
            'order_id' => $order?->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sessionFromPayload(array $payload): ?object
    {
        $object = $payload['data']['object'] ?? null;

        if (! is_array($object) || ! isset($object['id'])) {
            return null;
        }

        return (object) [
            'id' => $object['id'],
            'payment_status' => $object['payment_status'] ?? null,
            'payment_intent' => $object['payment_intent'] ?? null,
            'metadata' => $object['metadata'] ?? [],
        ];
    }
}
