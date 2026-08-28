<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Incomplete = 'incomplete';
    case Paid = 'paid';
    case Processing = 'processing';
    case Canceled = 'canceled';
    case Failed = 'failed';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Refunded = 'refunded';

    /**
     * Post-payment fulfillment statuses (buyer-facing pipeline).
     *
     * @return list<self>
     */
    public static function fulfillment(): array
    {
        return [
            self::Paid,
            self::Processing,
            self::Shipped,
            self::Delivered,
        ];
    }

    public function isFulfillment(): bool
    {
        return in_array($this, self::fulfillment(), true);
    }
}
