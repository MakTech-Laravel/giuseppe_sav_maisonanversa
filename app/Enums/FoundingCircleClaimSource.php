<?php

namespace App\Enums;

enum FoundingCircleClaimSource: string
{
    case Order = 'order';
    case Manual = 'manual';

    /**
     * Dutch source label — the i18n key the frontend passes to t().
     */
    public function label(): string
    {
        return match ($this) {
            self::Order => 'Bestelling',
            self::Manual => 'Handmatig',
        };
    }
}
