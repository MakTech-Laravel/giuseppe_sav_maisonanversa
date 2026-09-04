<?php

namespace App\Services\Checkout;

use App\Enums\GuardEnum;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RoleEnum;
use App\Jobs\Orders\SendOrderPaidAdminMail;
use App\Jobs\Orders\SendOrderPaidBuyerMail;
use App\Mail\SoldOutNotice;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\OrderStatusEvent;
use App\Models\Payment;
use App\Models\User;
use App\Services\Edition\EditionAllocator;
use App\Services\Edition\EditionInventory;
use App\Services\Edition\SimpleStock;
use App\Services\FoundingCircle\FoundingCircleRegistrar;
use App\Support\MailLocale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Cashier\Cashier;
use Spatie\Permission\Models\Role;
use Stripe\Exception\ApiErrorException;

class OrderFulfillment
{
    public function __construct(
        private EditionAllocator $allocator,
        private EditionInventory $inventory,
        private SimpleStock $simpleStock,
        private FoundingCircleRegistrar $registrar,
    ) {}

    /**
     * Mark payment + order paid from a Stripe Checkout Session (idempotent).
     */
    public function markPaidFromSession(object $session): ?Order
    {
        $payment = $this->findPaymentForSession($session);

        if ($payment === null) {
            return null;
        }

        if (($session->payment_status ?? null) !== 'paid') {
            return $payment->order;
        }

        $order = $payment->order;
        $alreadyPaid = $order->status->isFulfillment();

        $fulfilled = DB::transaction(function () use ($payment, $order, $session): Order {
            /** @var Payment $lockedPayment */
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $locked->loadMissing('product');

            $intentId = $this->paymentIntentId($session);
            $sessionId = is_string($session->id ?? null) ? $session->id : $lockedPayment->stripe_checkout_session_id;

            $lockedPayment->fill([
                'status' => PaymentStatus::Paid,
                'stripe_checkout_session_id' => $sessionId,
                'stripe_payment_intent_id' => $intentId ?? $lockedPayment->stripe_payment_intent_id,
            ])->save();

            if ($locked->status->isFulfillment()) {
                $locked->fill([
                    'stripe_checkout_session_id' => $sessionId ?? $locked->stripe_checkout_session_id,
                    'stripe_payment_intent_id' => $intentId ?? $locked->stripe_payment_intent_id,
                ])->save();

                return $locked->refresh();
            }

            if ($locked->product?->isLimitedEdition()) {
                $this->allocator->allocate($locked);
            }

            $locked->fill([
                'status' => OrderStatus::Paid,
                'stripe_checkout_session_id' => $sessionId ?? $locked->stripe_checkout_session_id,
                'stripe_payment_intent_id' => $intentId ?? $locked->stripe_payment_intent_id,
            ])->save();

            OrderStatusEvent::query()->firstOrCreate(
                [
                    'order_id' => $locked->id,
                    'status' => OrderStatus::Paid,
                ],
                [
                    'message' => __('Betaling ontvangen. Uw bestelling is aangemaakt.'),
                    'user_id' => null,
                ],
            );

            $locked->refresh();
            $locked->loadMissing('product', 'user');

            if ($locked->user !== null && $locked->product?->grants_founding_circle) {
                Role::findOrCreate(RoleEnum::FOUNDING_CIRCLE->value, GuardEnum::WEB->value);
                $locked->user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);
                $this->registrar->register($locked->user, $locked);
            }

            return $locked;
        });

        if (! $alreadyPaid) {
            SendOrderPaidBuyerMail::dispatch($fulfilled);
            SendOrderPaidAdminMail::dispatch($fulfilled);

            if ($this->inventory->snapshot($fulfilled->product)['soldOut']) {
                $this->notifySoldOut();
            }
        }

        return $fulfilled;
    }

    /**
     * Create a Stripe refund (when a PaymentIntent exists), restore inventory, and mark refunded.
     *
     * @throws ApiErrorException
     */
    public function refund(Order $order): Order
    {
        if ($order->status === OrderStatus::Refunded) {
            return $order;
        }

        $order->loadMissing('latestPayment');
        $intentId = $order->latestPayment?->stripe_payment_intent_id
            ?: $order->stripe_payment_intent_id;

        if (filled($intentId)) {
            Cashier::stripe()->refunds->create([
                'payment_intent' => $intentId,
            ]);
        }

        return $this->markRefunded($order);
    }

    /**
     * Mark an order refunded from a Stripe charge.refunded webhook (idempotent, no Stripe call).
     */
    public function markRefundedFromPaymentIntent(?string $paymentIntentId): ?Order
    {
        if ($paymentIntentId === null || $paymentIntentId === '') {
            return null;
        }

        $payment = Payment::query()
            ->where('stripe_payment_intent_id', $paymentIntentId)
            ->first();

        if ($payment !== null) {
            return $this->markRefunded($payment->order);
        }

        $order = Order::query()
            ->where('stripe_payment_intent_id', $paymentIntentId)
            ->first();

        if ($order === null) {
            return null;
        }

        return $this->markRefunded($order);
    }

    /**
     * Restore inventory and set payment + order to refunded (idempotent).
     */
    public function markRefunded(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $locked->loadMissing('product', 'latestPayment');

            if ($locked->status === OrderStatus::Refunded) {
                return $locked;
            }

            $this->releaseInventoryOnRefund($locked);

            if ($locked->latestPayment !== null) {
                $locked->latestPayment->fill([
                    'status' => PaymentStatus::Refunded,
                ])->save();
            }

            $locked->fill(['status' => OrderStatus::Refunded])->save();

            OrderStatusEvent::query()->firstOrCreate(
                [
                    'order_id' => $locked->id,
                    'status' => OrderStatus::Refunded,
                ],
                [
                    'message' => __('Betaling terugbetaald.'),
                    'user_id' => null,
                ],
            );

            return $locked->refresh();
        });
    }

    /**
     * Mark payment + order failed from a Stripe Checkout Session (idempotent).
     */
    public function markFailedFromSession(object $session): ?Order
    {
        $payment = $this->findPaymentForSession($session);

        if ($payment === null) {
            return null;
        }

        return DB::transaction(function () use ($payment, $session): Order {
            /** @var Payment $lockedPayment */
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            /** @var Order $locked */
            $locked = Order::query()->whereKey($payment->order_id)->lockForUpdate()->firstOrFail();
            $locked->loadMissing('product');

            if ($locked->status->isFulfillment()) {
                return $locked;
            }

            $this->releaseInventory($locked);

            $intentId = $this->paymentIntentId($session);
            $sessionId = is_string($session->id ?? null) ? $session->id : $lockedPayment->stripe_checkout_session_id;

            $lockedPayment->fill([
                'status' => PaymentStatus::Failed,
                'stripe_checkout_session_id' => $sessionId,
                'stripe_payment_intent_id' => $intentId ?? $lockedPayment->stripe_payment_intent_id,
            ])->save();

            $locked->fill([
                'status' => OrderStatus::Failed,
                'stripe_checkout_session_id' => $sessionId ?? $locked->stripe_checkout_session_id,
                'stripe_payment_intent_id' => $intentId ?? $locked->stripe_payment_intent_id,
            ])->save();

            OrderStatusEvent::query()->firstOrCreate(
                [
                    'order_id' => $locked->id,
                    'status' => OrderStatus::Failed,
                ],
                [
                    'message' => __('Betaling mislukt. De reservering is vrijgegeven.'),
                    'user_id' => null,
                ],
            );

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

        $payment = Payment::query()
            ->where('stripe_checkout_session_id', $sessionId)
            ->first();

        $order = $payment?->order
            ?? Order::query()->where('stripe_checkout_session_id', $sessionId)->first();

        if ($order === null || $order->status !== OrderStatus::Incomplete) {
            return $order;
        }

        return DB::transaction(function () use ($order, $payment, $sessionId): Order {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $locked->loadMissing('product');

            if ($locked->status !== OrderStatus::Incomplete) {
                return $locked;
            }

            $this->releaseInventory($locked);

            if ($payment !== null) {
                /** @var Payment $lockedPayment */
                $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

                if ($lockedPayment->status === PaymentStatus::Pending) {
                    $lockedPayment->fill([
                        'status' => PaymentStatus::Canceled,
                        'stripe_checkout_session_id' => $sessionId,
                    ])->save();
                }
            } else {
                Payment::query()
                    ->where('order_id', $locked->id)
                    ->where('status', PaymentStatus::Pending)
                    ->update(['status' => PaymentStatus::Canceled->value]);
            }

            $locked->fill(['status' => OrderStatus::Canceled])->save();

            OrderStatusEvent::query()->firstOrCreate(
                [
                    'order_id' => $locked->id,
                    'status' => OrderStatus::Canceled,
                ],
                [
                    'message' => __('Checkout geannuleerd. De reservering is vrijgegeven.'),
                    'user_id' => null,
                ],
            );

            return $locked->refresh();
        });
    }

    public function markExpiredBySessionId(?string $sessionId): ?Order
    {
        return $this->markCanceledBySessionId($sessionId);
    }

    /**
     * Admin or system cancel of an incomplete order: release inventory and cancel pending payment.
     */
    public function cancelIncomplete(Order $order, string $message, ?User $admin = null): Order
    {
        return DB::transaction(function () use ($order, $message, $admin): Order {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $locked->loadMissing('product');

            if ($locked->status !== OrderStatus::Incomplete) {
                return $locked->refresh();
            }

            $this->releaseInventory($locked);

            Payment::query()
                ->where('order_id', $locked->id)
                ->where('status', PaymentStatus::Pending)
                ->update(['status' => PaymentStatus::Canceled->value]);

            $locked->fill(['status' => OrderStatus::Canceled])->save();

            OrderStatusEvent::query()->create([
                'order_id' => $locked->id,
                'status' => OrderStatus::Canceled,
                'message' => $message,
                'user_id' => $admin?->id,
            ]);

            return $locked->refresh();
        });
    }

    private function releaseInventory(Order $order): void
    {
        if ($order->product?->isLimitedEdition()) {
            $this->allocator->release($order);

            return;
        }

        if ($order->product?->isSimple() && $order->status === OrderStatus::Incomplete) {
            $this->simpleStock->release($order->product);
        }
    }

    private function releaseInventoryOnRefund(Order $order): void
    {
        if ($order->product?->isLimitedEdition()) {
            $this->allocator->releaseOnRefund($order);

            return;
        }

        if ($order->product?->isSimple()) {
            $this->simpleStock->releaseOnRefund($order->product);
        }
    }

    private function notifySoldOut(): void
    {
        NewsletterSubscriber::query()
            ->where('status', 'subscribed')
            ->each(fn (NewsletterSubscriber $subscriber) => Mail::to($subscriber->email)
                ->locale(MailLocale::resolve($subscriber->locale))
                ->queue(new SoldOutNotice($subscriber)));
    }

    private function findPaymentForSession(object $session): ?Payment
    {
        $metadata = $session->metadata ?? [];
        $paymentId = null;
        $orderId = null;

        if (is_array($metadata)) {
            $paymentId = $metadata['payment_id'] ?? null;
            $orderId = $metadata['order_id'] ?? null;
        } elseif (is_object($metadata)) {
            $paymentId = $metadata['payment_id'] ?? $metadata->payment_id ?? null;
            $orderId = $metadata['order_id'] ?? $metadata->order_id ?? null;
        }

        if ($paymentId !== null && $paymentId !== '') {
            $payment = Payment::query()->with('order')->find($paymentId);

            if ($payment !== null) {
                return $payment;
            }
        }

        $sessionId = $session->id ?? null;

        if (is_string($sessionId) && $sessionId !== '') {
            $payment = Payment::query()
                ->with('order')
                ->where('stripe_checkout_session_id', $sessionId)
                ->first();

            if ($payment !== null) {
                return $payment;
            }
        }

        $order = null;

        if ($orderId !== null && $orderId !== '') {
            $order = Order::query()->find($orderId);
        }

        if ($order === null && is_string($sessionId) && $sessionId !== '') {
            $order = Order::query()
                ->where('stripe_checkout_session_id', $sessionId)
                ->first();
        }

        if ($order === null) {
            return null;
        }

        $payment = $order->latestPayment;

        if ($payment !== null) {
            return $payment->loadMissing('order');
        }

        return Payment::query()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::Pending,
            'amount' => $order->amount,
            'currency' => $order->currency,
            'provider' => 'stripe',
            'stripe_checkout_session_id' => is_string($sessionId) ? $sessionId : $order->stripe_checkout_session_id,
            'stripe_payment_intent_id' => $order->stripe_payment_intent_id,
        ])->load('order');
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
