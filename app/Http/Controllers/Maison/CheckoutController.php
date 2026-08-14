<?php

namespace App\Http\Controllers\Maison;

use App\Enums\OrderStatus;
use App\Exceptions\EditionSoldOutException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Maison\CheckoutRequest;
use App\Models\Order;
use App\Services\Checkout\FoundingEditionCheckout;
use App\Services\Checkout\OrderFulfillment;
use App\Services\Edition\EditionAllocator;
use App\Services\Edition\EditionInventory;
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
     * Start a Stripe Checkout session for the Founding Edition (EUR).
     */
    public function store(
        CheckoutRequest $request,
        string $locale,
        FoundingEditionCheckout $checkout,
        EditionAllocator $allocator,
        EditionInventory $inventory,
    ): SymfonyResponse {
        if ($inventory->snapshot()['available'] === 0) {
            throw ValidationException::withMessages([
                'checkout' => __('Heritage No.001 is uitverkocht.'),
            ]);
        }

        try {
            $checkoutUrl = DB::transaction(function () use ($request, $locale, $checkout, $allocator): string {
                $data = $request->validated();

                $order = Order::query()->create([
                    'user_id' => $request->user()?->id,
                    'status' => OrderStatus::Incomplete,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'locale' => $locale,
                    'phone' => $data['phone'] ?? null,
                    'monogram' => $data['monogram'] ?? null,
                    'gift_wrap' => (bool) ($data['gift_wrap'] ?? false),
                    'gift_message' => $data['gift_message'] ?? null,
                    'currency' => 'eur',
                    'amount' => (int) config('maison.checkout.amount'),
                ]);

                $allocator->hold($order);

                $session = $checkout->create($order, $locale, $request->user());

                $order->update([
                    'stripe_checkout_session_id' => $session['session_id'],
                ]);

                return $session['url'];
            });
        } catch (EditionSoldOutException) {
            throw ValidationException::withMessages([
                'checkout' => __('Heritage No.001 is uitverkocht.'),
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
                        : Order::query()->where('stripe_checkout_session_id', $sessionId)->first();
                }
            } catch (ApiErrorException|Throwable) {
                $order = Order::query()
                    ->where('stripe_checkout_session_id', $sessionId)
                    ->first();
            }
        }

        return Inertia::render('maison/checkout-success', [
            'paid' => $paid,
            'editionNumber' => $order?->edition_number !== null
                ? (string) $order->edition_number
                : null,
            'orderId' => $order?->id,
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
