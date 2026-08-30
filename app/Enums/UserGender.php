<?php

namespace App\Enums;

enum UserGender: string
{
    case Male = 'male';
    case Female = 'female';
    case Mixed = 'mixed';

    /**
     * Dutch source label — the i18n key the frontend passes to t().
     */
    public function label(): string
    {
        return match ($this) {
            self::Male => 'Man',
            self::Female => 'Vrouw',
            self::Mixed => 'Gemengd',
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
