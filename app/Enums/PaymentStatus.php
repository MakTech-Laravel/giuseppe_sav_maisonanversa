<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Canceled = 'canceled';
    case Refunded = 'refunded';

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Paid,
            self::Failed,
            self::Canceled,
            self::Refunded,
        ], true);
    }

    public function isPaid(): bool
    {
        return $this === self::Paid;
    }
}
