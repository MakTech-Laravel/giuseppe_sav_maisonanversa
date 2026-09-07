<?php

namespace App\Enums;

enum EditionPieceStatus: string
{
    case Archive = 'archive';
    case Available = 'available';
    case Reserved = 'reserved';
    case Allocated = 'allocated';

    /**
     * Dutch source label — the i18n key the frontend passes to t().
     */
    public function label(): string
    {
        return match ($this) {
            self::Archive => 'Gearchiveerd',
            self::Available => 'Beschikbaar',
            self::Reserved => 'Gereserveerd',
            self::Allocated => 'Toegewezen',
        };
    }
}
