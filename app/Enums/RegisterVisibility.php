<?php

namespace App\Enums;

enum RegisterVisibility: string
{
    case Private = 'private';
    case Initial = 'initial';
    case Full = 'full';

    public function isPublic(): bool
    {
        return $this !== self::Private;
    }

    /**
     * Dutch source label — the i18n key the frontend passes to t().
     */
    public function label(): string
    {
        return match ($this) {
            self::Private => 'Privé',
            self::Initial => 'Voornaam en initiaal',
            self::Full => 'Volledige naam',
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
