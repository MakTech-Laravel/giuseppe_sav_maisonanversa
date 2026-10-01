<?php

namespace App\Services\Stripe;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\InvalidRequestException;

class StripeCustomerGuard
{
    /**
     * Clear a stored Stripe customer id when it no longer exists in the current Stripe account.
     *
     * Local/test keys and deleted Dashboard customers leave a stale users.stripe_id that
     * makes Cashier Checkout::customer() fail with "No such customer".
     */
    public function forgetIfMissing(User $user): void
    {
        if (! $user->hasStripeId()) {
            return;
        }

        try {
            $user->asStripeCustomer();
        } catch (InvalidRequestException $exception) {
            if (! $this->isMissingCustomer($exception)) {
                throw $exception;
            }

            Log::warning('Cleared missing Stripe customer id before checkout.', [
                'user_id' => $user->id,
                'stripe_id' => $user->stripe_id,
            ]);

            $user->forceFill([
                'stripe_id' => null,
                'pm_type' => null,
                'pm_last_four' => null,
            ])->save();
        }
    }

    private function isMissingCustomer(InvalidRequestException $exception): bool
    {
        return str_contains(strtolower($exception->getMessage()), 'no such customer');
    }
}
