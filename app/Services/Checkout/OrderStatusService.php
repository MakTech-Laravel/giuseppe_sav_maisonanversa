<?php

namespace App\Services\Checkout;

use App\Enums\NotificationType;
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
                'message' => __('Please add a message for the buyer.'),
            ]);
        }

        if ($status === OrderStatus::Refunded) {
            $refunded = $this->fulfillment->refund($order)['order'];
            $event = $this->recordEvent($refunded, $status, $message, $admin);
            SendOrderStatusUpdatedBuyerMail::dispatch($refunded, $event);
            $this->notifyBuyer($refunded, 'Order refunded', 'Your order has been refunded.');

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

            if ($status === OrderStatus::Delivered && $locked->delivered_on === null) {
                $locked->delivered_on = now()->toDateString();
            }

            $locked->save();

            $event = $this->recordEvent($locked, $status, $message, $admin);

            return [$locked->refresh(), $event];
        });

        [$orderResult, $event] = $result;

        SendOrderStatusUpdatedBuyerMail::dispatch($orderResult, $event);

        if ($status === OrderStatus::Shipped) {
            $this->notifyBuyer($orderResult, 'Order shipped', 'Your order has been shipped.');
        }

        if ($status === OrderStatus::Delivered) {
            $this->notifyBuyer($orderResult, 'Order delivered', 'Your order has been delivered.');
        }

        return $orderResult->load('statusEvents');
    }

    private function notifyBuyer(Order $order, string $title, string $message): void
    {
        if ($order->user_id === null) {
            return;
        }

        notify_user(
            $order->user_id,
            NotificationType::Order,
            $title,
            $message,
            [
                'link_url' => route('member.orders.show', [
                    'locale' => $order->locale ?? config('app.locale'),
                    'order' => $order->id,
                ]),
                'link_label' => 'View order',
            ],
        );
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
                'status' => __('This status change is not allowed.'),
            ]);
        }
    }
}
