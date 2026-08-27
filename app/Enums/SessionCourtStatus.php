<?php

namespace App\Enums;

enum SessionCourtStatus: string
{
    case Booked = 'booked';
    case NotBooked = 'not_booked';

    /**
     * Dutch source label — the i18n key the frontend passes to t().
     */
    public function label(): string
    {
        return match ($this) {
            self::Booked => 'Baan al geboekt',
            self::NotBooked => 'Baan nog niet geboekt',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
