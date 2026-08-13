<?php

namespace App\Services\Checkout;

use App\Models\Order;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Cashier\Checkout;
use RuntimeException;

class FoundingEditionCheckout
{
    /**
     * Create a Stripe Checkout session for a pending Founding Edition order (EUR).
     *
     * @return array{url: string, session_id: string}
     */
    public function create(Order $order, string $locale, ?User $user = null): array
    {
        $this->ensureEuroOnly();

        if (! filled(config('cashier.secret'))) {
            throw ValidationException::withMessages([
                'checkout' => __('Stripe is not configured yet. Add your Stripe keys to continue.'),
            ]);
        }

        $priceId = (string) config('maison.checkout.price_id');

        if ($priceId === '') {
            throw ValidationException::withMessages([
                'checkout' => __('Stripe Price ID is not configured. Set MAISON_STRIPE_PRICE_ID.'),
            ]);
        }

        $sessionOptions = [
            'mode' => 'payment',
            'success_url' => route('maison.checkout.success', ['locale' => $locale]).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('maison.checkout.cancel', ['locale' => $locale]).'?session_id={CHECKOUT_SESSION_ID}',
            'adaptive_pricing' => ['enabled' => false],
            'metadata' => [
                'order_id' => (string) $order->id,
                'product' => 'founding_edition',
                'currency' => 'eur',
                'edition_number' => (string) $order->edition_number,
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'edition_number' => (string) $order->edition_number,
                ],
            ],
        ];

        if ($user === null) {
            $sessionOptions['customer_email'] = $order->email;
        }

        $builder = $user !== null
            ? Checkout::customer($user)
            : Checkout::guest();

        $session = $builder->create([$priceId => 1], $sessionOptions);

        return [
            'url' => $session->url,
            'session_id' => $session->id,
        ];
    }

    private function ensureEuroOnly(): void
    {
        $currency = strtolower((string) config('cashier.currency', 'eur'));

        if ($currency !== 'eur') {
            throw new RuntimeException('Maison Anversa checkout only supports EUR.');
        }
    }
}
