<?php

namespace App\Services\Returns;

use App\Enums\OrderStatus;
use App\Enums\ReturnStatus;
use App\Models\Order;
use App\Models\OrderReturn;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class ReturnEligibility
{
    /**
     * @return array{
     *     can_cancel: bool,
     *     can_return: bool,
     *     period_ended: bool,
     *     delivery_date: string|null,
     *     request_deadline: string|null,
     *     open_return: array{id: int, number: string, status: string, status_label: string}|null
     * }
     */
    public function forOrder(Order $order): array
    {
        $order->loadMissing('returns');
        $open = $order->returns
            ->first(fn (OrderReturn $return): bool => $return->status->isOpen());

        $canCancel = $this->canCancel($order);
        $deliveryDate = $this->deliveryDate($order);
        $deadline = $this->requestDeadline($order);
        $withinWindow = $deadline !== null && now()->lessThanOrEqualTo($deadline->copy()->endOfDay());
        $shipped = $order->isShipped();
        $returnableLeft = $this->hasReturnableQuantity($order);
        $canReturn = $shipped
            && ! $canCancel
            && $withinWindow
            && $returnableLeft
            && $open === null
            && $order->status !== OrderStatus::Refunded;
        $periodEnded = $shipped
            && ! $canCancel
            && $open === null
            && $deadline !== null
            && ! $withinWindow
            && $order->status !== OrderStatus::Refunded;

        return [
            'can_cancel' => $canCancel,
            'can_return' => $canReturn,
            'period_ended' => $periodEnded,
            'delivery_date' => $deliveryDate?->toDateString(),
            'request_deadline' => $deadline?->toDateString(),
            'open_return' => $open === null ? null : [
                'id' => $open->id,
                'number' => $open->number,
                'status' => $open->status->value,
                'status_label' => $open->status->label(),
            ],
        ];
    }

    public function canCancel(Order $order): bool
    {
        if (! $order->status->isFulfillment()) {
            return false;
        }

        if ($order->status === OrderStatus::Refunded) {
            return false;
        }

        return ! $order->isShipped();
    }

    public function canRequestReturn(Order $order): bool
    {
        return $this->forOrder($order)['can_return'];
    }

    public function deliveryDate(Order $order): ?CarbonInterface
    {
        if ($order->delivered_on !== null) {
            return Carbon::parse($order->delivered_on)->startOfDay();
        }

        if ($order->delivered_at !== null) {
            return $order->delivered_at->copy()->startOfDay();
        }

        if ($order->shipped_at !== null) {
            return $order->shipped_at
                ->copy()
                ->addDays((int) config('maison.returns.delivery_fallback_days'))
                ->startOfDay();
        }

        return null;
    }

    public function requestDeadline(Order $order): ?CarbonInterface
    {
        if ($order->return_deadline_at !== null) {
            return $order->return_deadline_at->copy()->startOfDay();
        }

        $delivery = $this->deliveryDate($order);

        if ($delivery === null) {
            return null;
        }

        // Period starts the day after delivery and lasts withdrawal_days.
        return $delivery
            ->copy()
            ->addDays((int) config('maison.returns.withdrawal_days'))
            ->startOfDay();
    }

    public function hasReturnableQuantity(Order $order): bool
    {
        $consumed = OrderReturn::query()
            ->where('order_id', $order->id)
            ->whereIn('status', array_map(
                fn (ReturnStatus $status): string => $status->value,
                ReturnStatus::consuming(),
            ))
            ->exists();

        // v1: one product line per order, quantity 1.
        return ! $consumed;
    }
}
