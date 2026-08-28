<?php

namespace App\Services\Checkout;

use App\Enums\OrderStatus;
use App\Jobs\Orders\SendOrderStatusUpdatedBuyerMail;
use App\Models\Order;
use App\Models\OrderStatusEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\ApiErrorException;

class OrderStatusService
{
    public function __construct(private OrderFulfillment $fulfillment) {}

    /**
     * Transition an order to a new fulfillment status with an admin message.
     *
     * @throws ApiErrorException
     * @throws ValidationException
     */
    public function transition(
        Order $order,
        OrderStatus $status,
        string $message,
        ?User $admin = null,
    ): Order {
        $message = trim($message);

        if ($message === '') {
            throw ValidationException::withMessages([
                'message' => __('Voeg a.u.b. een bericht toe voor de koper.'),
            ]);
        }

        if ($status === OrderStatus::Refunded) {
            $refunded = $this->fulfillment->refund($order);
            $event = $this->recordEvent($refunded, $status, $message, $admin);
            SendOrderStatusUpdatedBuyerMail::dispatch($refunded, $event);

            return $refunded->load('statusEvents');
        }

        if ($status === OrderStatus::Canceled) {
            $this->assertTransitionAllowed($order, $status);

            $canceled = $this->fulfillment->cancelIncomplete($order, $message, $admin);
            $event = $canceled->statusEvents()->latest('id')->first();

            if ($event !== null) {
                SendOrderStatusUpdatedBuyerMail::dispatch($canceled, $event);
            }

            return $canceled->load('statusEvents');
        }

        $this->assertTransitionAllowed($order, $status);

        /** @var array{0: Order, 1: OrderStatusEvent} $result */
        $result = DB::transaction(function () use ($order, $status, $message, $admin): array {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            $this->assertTransitionAllowed($locked, $status);

            $locked->fill(['status' => $status]);

            if ($status === OrderStatus::Processing && $locked->processing_at === null) {
                $locked->processing_at = now();
            }

            if ($status === OrderStatus::Shipped && $locked->shipped_at === null) {
                $locked->shipped_at = now();
            }

            if ($status === OrderStatus::Delivered && $locked->delivered_at === null) {
                $locked->delivered_at = now();
            }

            $locked->save();

            $event = $this->recordEvent($locked, $status, $message, $admin);

            return [$locked->refresh(), $event];
        });

        [$orderResult, $event] = $result;

        SendOrderStatusUpdatedBuyerMail::dispatch($orderResult, $event);

        return $orderResult->load('statusEvents');
    }

    private function recordEvent(
        Order $order,
        OrderStatus $status,
        string $message,
        ?User $admin,
    ): OrderStatusEvent {
        return OrderStatusEvent::query()->create([
            'order_id' => $order->id,
            'status' => $status,
            'message' => $message,
            'user_id' => $admin?->id,
        ]);
    }

    private function assertTransitionAllowed(Order $order, OrderStatus $to): void
    {
        $from = $order->status;

        $allowed = match ($to) {
            OrderStatus::Processing => [OrderStatus::Paid],
            OrderStatus::Shipped => [OrderStatus::Paid, OrderStatus::Processing],
            OrderStatus::Delivered => [OrderStatus::Shipped],
            OrderStatus::Canceled => [OrderStatus::Incomplete],
            OrderStatus::Refunded => [
                OrderStatus::Paid,
                OrderStatus::Processing,
                OrderStatus::Shipped,
                OrderStatus::Delivered,
            ],
            default => [],
        };

        if (! in_array($from, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => __('Deze statuswijziging is niet toegestaan.'),
            ]);
        }
    }
}
