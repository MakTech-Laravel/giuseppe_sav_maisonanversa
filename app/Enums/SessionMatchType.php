<?php

namespace App\Enums;

enum SessionMatchType: string
{
    case Singles = 'singles';
    case Doubles = 'doubles';

    /**
     * Dutch source label — the i18n key the frontend passes to t().
     */
    public function label(): string
    {
        return match ($this) {
            self::Singles => 'Enkel',
            self::Doubles => 'Dubbel',
        };
    }

    public function playersPerSide(): int
    {
        return match ($this) {
            self::Singles => 1,
            self::Doubles => 2,
        };
    }

    /**
     * @return list<array{value: string, label: string, players_per_side: int}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
                'players_per_side' => $case->playersPerSide(),
            ],
            self::cases(),
        );
    }
}
