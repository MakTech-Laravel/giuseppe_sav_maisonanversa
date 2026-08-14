<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;

class OrderPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function summary(Order $order): array
    {
        $amount = Money::format((string) $order->amount).' €';
        $number = $order->edition_number !== null
            ? str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT)
            : '—';

        return [
            'id' => (string) $order->id,
            'label' => __('Heritage No.001 — No. :number', ['number' => $number]),
            'date' => $order->created_at?->toDateString() ?? '',
            'amount' => $amount,
            'status' => $this->statusLabel($order->status),
            'status_key' => $order->status->value,
            'method' => 'Stripe',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(Order $order): array
    {
        $summary = $this->summary($order);
        $amount = $summary['amount'];
        $number = $order->edition_number !== null
            ? str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT)
            : '—';

        return [
            ...$summary,
            'summary' => __('Founding Edition · No. :number / 100', ['number' => $number]),
            'items' => [
                [
                    'name' => __('Heritage No.001 — Founding Edition'),
                    'qty' => 1,
                    'price' => $amount,
                ],
            ],
            'billing' => [
                'name' => $order->name,
                'email' => $order->email,
                'address' => $order->phone ?: '—',
            ],
            'timeline' => [
                ['label' => __('Geplaatst'), 'at' => $order->created_at?->toDateTimeString() ?? '', 'done' => true],
                ['label' => __('Betaald'), 'at' => $order->isPaid() ? ($order->updated_at?->toDateTimeString() ?? '') : '', 'done' => $order->isPaid()],
                ['label' => __('Verzonden'), 'at' => $order->shipped_at?->toDateTimeString() ?? '', 'done' => $order->shipped_at !== null],
                ['label' => __('Geleverd'), 'at' => $order->delivered_at?->toDateTimeString() ?? '', 'done' => $order->delivered_at !== null],
            ],
        ];
    }

    public function statusLabel(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Incomplete => __('In behandeling'),
            OrderStatus::Paid => __('Betaald'),
            OrderStatus::Canceled => __('Geannuleerd'),
            OrderStatus::Failed => __('Mislukt'),
            OrderStatus::Shipped => __('Verzonden'),
            OrderStatus::Delivered => __('Geleverd'),
            OrderStatus::Refunded => __('Terugbetaald'),
        };
    }
}
