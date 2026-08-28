<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;

class OrderPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function summary(Order $order): array
    {
        $order->loadMissing('product');

        $amount = Money::format((string) $order->amount).' €';
        $name = $order->product?->translated('name') ?? __('Product');
        $number = $order->edition_number !== null
            ? str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT)
            : null;

        $label = $number !== null
            ? __(':product — No. :number', ['product' => $name, 'number' => $number])
            : $name;

        return [
            'id' => (string) $order->id,
            'reference' => $order->reference(),
            'label' => $label,
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
        $order->loadMissing(['product', 'statusEvents', 'latestPayment']);

        $summary = $this->summary($order);
        $amount = $summary['amount'];
        $name = $order->product?->translated('name') ?? __('Product');
        $total = (int) ($order->product?->edition_total ?? 0);
        $number = $order->edition_number !== null
            ? str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT)
            : '—';

        $editionSummary = $total > 0 && $order->edition_number !== null
            ? __(':product · No. :number / :total', [
                'product' => $name,
                'number' => $number,
                'total' => $total,
            ])
            : $name;

        $shipping = $order->shippingAddress();
        $payment = $order->latestPayment;

        return [
            ...$summary,
            'summary' => $editionSummary,
            'edition_number' => $order->edition_number,
            'items' => [
                [
                    'name' => $name,
                    'qty' => 1,
                    'price' => $amount,
                ],
            ],
            'billing' => [
                'name' => $order->name,
                'email' => $order->email,
                'phone' => $order->phone ?: '—',
            ],
            'shipping' => [
                'line1' => $shipping['line1'] !== '' ? $shipping['line1'] : '—',
                'line2' => $shipping['line2'] ?: null,
                'city' => $shipping['city'] !== '' ? $shipping['city'] : '—',
                'postal_code' => $shipping['postal_code'] !== '' ? $shipping['postal_code'] : '—',
                'country' => $shipping['country'] !== '' ? $shipping['country'] : '—',
            ],
            'payment' => $payment === null ? null : [
                'id' => (string) $payment->id,
                'status' => $this->paymentStatusLabel($payment->status),
                'status_key' => $payment->status->value,
                'provider' => $payment->provider,
                'stripe_checkout_session_id' => $payment->stripe_checkout_session_id,
                'stripe_payment_intent_id' => $payment->stripe_payment_intent_id,
                'amount' => Money::format((string) $payment->amount).' €',
            ],
            'timeline' => $this->timeline($order),
            'events' => $order->statusEvents
                ->map(fn ($event) => [
                    'id' => (string) $event->id,
                    'status' => $this->statusLabel($event->status),
                    'status_key' => $event->status->value,
                    'message' => $event->message,
                    'at' => $event->created_at?->toDateTimeString() ?? '',
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return list<array{label: string, at: string, done: bool, status_key: string}>
     */
    public function timeline(Order $order): array
    {
        $status = $order->status;

        $stages = [
            [
                'key' => OrderStatus::Paid,
                'label' => __('Aangemaakt'),
                'at' => $order->created_at?->toDateTimeString() ?? '',
            ],
            [
                'key' => OrderStatus::Processing,
                'label' => __('In verwerking'),
                'at' => $order->processing_at?->toDateTimeString() ?? '',
            ],
            [
                'key' => OrderStatus::Shipped,
                'label' => __('Verzonden'),
                'at' => $order->shipped_at?->toDateTimeString() ?? '',
            ],
            [
                'key' => OrderStatus::Delivered,
                'label' => __('Geleverd'),
                'at' => $order->delivered_at?->toDateTimeString() ?? '',
            ],
        ];

        $rank = match ($status) {
            OrderStatus::Paid => 0,
            OrderStatus::Processing => 1,
            OrderStatus::Shipped => 2,
            OrderStatus::Delivered => 3,
            default => $status->isFulfillment() ? 0 : -1,
        };

        return array_map(
            function (array $stage, int $index) use ($rank): array {
                return [
                    'label' => $stage['label'],
                    'at' => $stage['at'],
                    'done' => $rank >= $index,
                    'status_key' => $stage['key']->value,
                ];
            },
            $stages,
            array_keys($stages),
        );
    }

    public function statusLabel(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Incomplete => __('In behandeling'),
            OrderStatus::Paid => __('Aangemaakt'),
            OrderStatus::Processing => __('In verwerking'),
            OrderStatus::Canceled => __('Geannuleerd'),
            OrderStatus::Failed => __('Mislukt'),
            OrderStatus::Shipped => __('Verzonden'),
            OrderStatus::Delivered => __('Geleverd'),
            OrderStatus::Refunded => __('Terugbetaald'),
        };
    }

    public function paymentStatusLabel(PaymentStatus $status): string
    {
        return match ($status) {
            PaymentStatus::Pending => __('Betaling in behandeling'),
            PaymentStatus::Paid => __('Betaald'),
            PaymentStatus::Failed => __('Betaling mislukt'),
            PaymentStatus::Canceled => __('Betaling geannuleerd'),
            PaymentStatus::Refunded => __('Terugbetaald'),
        };
    }
}
