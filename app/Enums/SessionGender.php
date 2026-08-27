<?php

namespace App\Enums;

enum SessionGender: string
{
    case Everyone = 'everyone';
    case Mixed = 'mixed';
    case Men = 'men';
    case Women = 'women';

    /**
     * Dutch source label — the i18n key the frontend passes to t().
     */
    public function label(): string
    {
        return match ($this) {
            self::Everyone => 'Iedereen',
            self::Mixed => 'Gemengd',
            self::Men => 'Mannen',
            self::Women => 'Vrouwen',
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
