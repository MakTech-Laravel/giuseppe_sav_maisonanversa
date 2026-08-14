<?php

namespace App\Services\Checkout;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Stripe\StripeCatalog;
use Illuminate\Validation\ValidationException;
use Laravel\Cashier\Checkout;
use RuntimeException;

class FoundingEditionCheckout
{
    public function __construct(private StripeCatalog $catalog) {}

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

        $product = $order->product ?? Product::founding();

        if ($product === null) {
            throw ValidationException::withMessages([
                'checkout' => __('This product is not available for checkout yet.'),
            ]);
        }

        $product = $this->catalog->sync($product);

        if (blank($product->stripe_price_id)) {
            throw ValidationException::withMessages([
                'checkout' => __('Unable to create a Stripe price for this product. Try again shortly.'),
            ]);
        }

        $sessionOptions = [
            'mode' => 'payment',
            'payment_method_types' => ['card', 'bancontact'],
            'success_url' => route('maison.checkout.success', ['locale' => $locale]).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('maison.checkout.cancel', ['locale' => $locale]).'?session_id={CHECKOUT_SESSION_ID}',
            'adaptive_pricing' => ['enabled' => false],
            'metadata' => [
                'order_id' => (string) $order->id,
                'product_id' => (string) $product->id,
                'product' => $product->slug,
                'currency' => 'eur',
                'edition_number' => (string) $order->edition_number,
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'product_id' => (string) $product->id,
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

        $session = $builder->create([$product->stripe_price_id => 1], $sessionOptions);

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
