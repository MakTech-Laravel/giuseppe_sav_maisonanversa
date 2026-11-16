<?php

namespace App\Enums;

enum EditionPieceStatus: string
{
    case Archive = 'archive';
    case Available = 'available';
    case Reserved = 'reserved';
    case Allocated = 'allocated';
    case Returning = 'returning';

    /**
     * Dutch source label — the i18n key the frontend passes to t().
     */
    public function label(): string
    {
        return match ($this) {
            self::Archive => 'Archived',
            self::Available => 'Available',
            self::Reserved => 'Reserved',
            self::Allocated => 'Allocated',
            self::Returning => 'Return',
        };
    }
}
