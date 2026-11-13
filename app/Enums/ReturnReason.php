<?php

namespace App\Enums;

enum ReturnReason: string
{
    case NotExpected = 'not_expected';
    case WrongItem = 'wrong_item';
    case Damaged = 'damaged';
    case Other = 'other';
    case PreferNotToSay = 'prefer_not_to_say';

    /**
     * Dutch source label — the i18n key the frontend passes to t().
     */
    public function label(): string
    {
        return match ($this) {
            self::NotExpected => 'Not what I expected',
            self::WrongItem => 'Ordered wrong item',
            self::Damaged => 'Arrived damaged or defective',
            self::Other => 'Andere',
            self::PreferNotToSay => 'Prefer not to say',
        };
    }

    public function maisonPaysReturnShipping(): bool
    {
        return $this === self::Damaged;
    }

    /**
     * English label for staff-facing mail (A1) — not routed through NL source keys.
     */
    public function englishLabel(): string
    {
        return match ($this) {
            self::NotExpected => 'Not what I expected',
            self::WrongItem => 'Wrong item ordered',
            self::Damaged => 'Arrived damaged or defective',
            self::Other => 'Other',
            self::PreferNotToSay => 'Prefer not to say',
        };
    }
}
