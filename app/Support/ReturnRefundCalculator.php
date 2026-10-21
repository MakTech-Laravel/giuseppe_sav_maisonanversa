<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Collection;

class ReturnRefundCalculator
{
    /**
     * Prefill refund in major currency units (EUR).
     *
     * v1: single-line orders — whole-order return = order.amount (shipping bundled).
     * Express shipping cents are never included when configured later.
     *
     * @param  Collection<int, array{unit_amount: string|float|int, quantity: int}>  $lines
     */
    public function expectedAmount(Order $order, Collection $lines, bool $wholeOrder): string
    {
        $itemsTotal = $lines->sum(
            fn (array $line): float => (float) $line['unit_amount'] * (int) $line['quantity'],
        );

        $express = ((int) config('maison.returns.express_shipping_cents')) / 100;

        if ($wholeOrder) {
            $amount = (float) $order->amount - $express;

            return number_format(max(0, $amount), 2, '.', '');
        }

        return number_format(max(0, $itemsTotal), 2, '.', '');
    }

    public function toCents(string|float $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
