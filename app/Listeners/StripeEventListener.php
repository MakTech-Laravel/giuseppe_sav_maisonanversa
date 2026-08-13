<?php

namespace App\Listeners;

use App\Services\Checkout\OrderFulfillment;
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

        match ($type) {
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded' => $this->handleCheckoutPaid($event->payload),
            'checkout.session.async_payment_failed' => $this->handleCheckoutFailed($event->payload),
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
            return;
        }

        $this->fulfillment->markPaidFromSession($session);
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

        $this->fulfillment->markFailedFromSession($session);
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
