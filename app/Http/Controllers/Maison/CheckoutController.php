<?php

namespace App\Http\Controllers\Maison;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\EditionSoldOutException;
use App\Exceptions\EditionUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Maison\CheckoutRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Checkout\OrderFulfillment;
use App\Services\Checkout\ProductCheckout;
use App\Services\Edition\EditionAllocator;
use App\Services\Edition\EditionInventory;
use App\Services\Edition\SimpleStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Cashier\Cashier;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class CheckoutController extends Controller
{
    /**
     * Start a Stripe Checkout session for a published product (EUR).
     *
     * Omitting product_id falls back to the founding SKU (heritage / home CTA).
     */
    public function store(
        CheckoutRequest $request,
        string $locale,
        ProductCheckout $checkout,
        EditionAllocator $allocator,
        EditionInventory $inventory,
    ): SymfonyResponse {
        $product = $request->resolveProduct();

        if ($product === null || $inventory->snapshot($product)['available'] === 0) {
            throw ValidationException::withMessages([
                'checkout' => __(':product is uitverkocht.', [
                    'product' => $product?->translated('name') ?? __('Heritage No.001'),
                ]),
            ]);
        }

        try {
            $checkoutUrl = DB::transaction(function () use ($request, $locale, $checkout, $allocator, $product): string {
                $data = $request->validated();

                $order = Order::query()->create([
                    'user_id' => $request->user()->id,
                    'product_id' => $product->id,
                    'status' => OrderStatus::Incomplete,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'locale' => $locale,
                    'phone' => $data['phone'] ?? null,
                    'shipping_line1' => $data['shipping_line1'],
                    'shipping_line2' => $data['shipping_line2'] ?? null,
                    'shipping_city' => $data['shipping_city'],
                    'shipping_postal_code' => $data['shipping_postal_code'],
                    'shipping_country' => $data['shipping_country'],
                    'monogram' => null,
                    'gift_wrap' => (bool) ($data['gift_wrap'] ?? false),
                    'gift_message' => $data['gift_message'] ?? null,
                    'currency' => $product->currency,
                    'amount' => $product->amount,
                ]);

                if ($product->isLimitedEdition()) {
                    $allocator->hold($order, isset($data['edition_piece_id']) ? (int) $data['edition_piece_id'] : null);
                } else {
                    app(SimpleStock::class)->reserve($product);
                }

                $payment = Payment::query()->create([
                    'order_id' => $order->id,
                    'status' => PaymentStatus::Pending,
                    'amount' => $product->amount,
                    'currency' => $product->currency,
                    'provider' => 'stripe',
                ]);

                $session = $checkout->create($order, $locale, $request->user(), $payment);

                $payment->update([
                    'stripe_checkout_session_id' => $session['session_id'],
                ]);

                $order->update([
                    'stripe_checkout_session_id' => $session['session_id'],
                ]);

                return $session['url'];
            });
        } catch (EditionSoldOutException) {
            throw ValidationException::withMessages([
                'checkout' => __(':product is uitverkocht.', [
                    'product' => $product->translated('name'),
                ]),
            ]);
        } catch (EditionUnavailableException) {
            throw ValidationException::withMessages([
                'edition_piece_id' => __('Dit editienummer is niet meer beschikbaar. Kies een ander nummer.'),
            ]);
        }

        return Inertia::location($checkoutUrl);
    }

    /**
     * Landing page after a successful Stripe payment.
     */
    public function success(
        Request $request,
        string $locale,
        OrderFulfillment $fulfillment,
    ): Response {
        $sessionId = $request->string('session_id')->toString();
        $order = null;
        $paid = false;

        if ($sessionId !== '' && filled(config('cashier.secret'))) {
            try {
                $session = Cashier::stripe()->checkout->sessions->retrieve($sessionId);

                if (($session->payment_status ?? null) === 'paid') {
                    $order = $fulfillment->markPaidFromSession($session);
                    $paid = $order?->isPaid() ?? false;
                } else {
                    $orderId = $session->metadata['order_id'] ?? null;
                    $order = $orderId
                        ? Order::query()->find($orderId)
                        : Order::query()
                            ->where('stripe_checkout_session_id', $sessionId)
                            ->orWhereHas('payments', fn ($query) => $query->where('stripe_checkout_session_id', $sessionId))
                            ->first();
                }
            } catch (ApiErrorException|Throwable) {
                $order = Order::query()
                    ->where('stripe_checkout_session_id', $sessionId)
                    ->orWhereHas('payments', fn ($query) => $query->where('stripe_checkout_session_id', $sessionId))
                    ->first();
            }
        }

        return Inertia::render('maison/checkout-success', [
            'paid' => $paid,
            'editionNumber' => $order?->edition_number !== null
                ? (string) $order->edition_number
                : null,
            'orderId' => $order?->id,
            'orderReference' => $order?->reference(),
        ]);
    }

    /**
     * Landing page when the customer cancels Stripe Checkout.
     */
    public function cancel(
        Request $request,
        string $locale,
        OrderFulfillment $fulfillment,
    ): Response {
        $sessionId = $request->string('session_id')->toString();

        if ($sessionId !== '') {
            $fulfillment->markCanceledBySessionId($sessionId);
        }

        return Inertia::render('maison/checkout-cancel');
    }
}
