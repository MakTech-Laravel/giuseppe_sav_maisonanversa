<?php

namespace App\Services\Stripe;

use Laravel\Cashier\Cashier;
use Throwable;

class CheckoutSessionExpirer
{
    /**
     * Best-effort expire an open Stripe Checkout session so a late payment cannot complete.
     */
    public function expire(?string $sessionId): void
    {
        if (! filled($sessionId) || ! filled(config('cashier.secret'))) {
            return;
        }

        try {
            Cashier::stripe()->checkout->sessions->expire($sessionId);
        } catch (Throwable) {
            // Session may already be expired, completed, or unreachable.
        }
    }
}
