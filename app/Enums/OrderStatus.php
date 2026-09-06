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
     * Dutch source label — the i18n key the frontend passes to t().
     */
    public function label(): string
    {
        return match ($this) {
            self::Incomplete => 'Onvoltooid',
            self::Paid => 'Betaald',
            self::Processing => 'In behandeling',
            self::Canceled => 'Geannuleerd',
            self::Failed => 'Mislukt',
            self::Shipped => 'Verzonden',
            self::Delivered => 'Afgeleverd',
            self::Refunded => 'Terugbetaald',
        };
    }

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
