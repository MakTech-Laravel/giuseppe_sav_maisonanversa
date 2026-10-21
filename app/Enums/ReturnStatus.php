<?php

namespace App\Enums;

enum ReturnStatus: string
{
    case Requested = 'requested';
    case InTransit = 'in_transit';
    case Received = 'received';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';
    case Declined = 'declined';
    case Cancelled = 'cancelled';

    /**
     * Dutch source label — the i18n key the frontend passes to t().
     */
    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Aangevraagd',
            self::InTransit => 'Onderweg',
            self::Received => 'Ontvangen',
            self::Refunded => 'Terugbetaald',
            self::PartiallyRefunded => 'Gedeeltelijk terugbetaald',
            self::Declined => 'Afgewezen',
            self::Cancelled => 'Geannuleerd',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [
            self::Requested,
            self::InTransit,
            self::Received,
        ], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Refunded,
            self::PartiallyRefunded,
            self::Declined,
            self::Cancelled,
        ], true);
    }

    /**
     * Statuses that still block the returned line from a second return.
     *
     * @return list<self>
     */
    public static function consuming(): array
    {
        return [
            self::Requested,
            self::InTransit,
            self::Received,
            self::Refunded,
            self::PartiallyRefunded,
        ];
    }
}
