<?php

namespace App\Enums;

enum CornerPipelineStatus: string
{
    case InDiscussion = 'in_discussion';
    case Open = 'open';
    case Active = 'active';

    /**
     * English i18n key the frontend passes to t().
     * OPEN is distinct from the button key "Open" (FR verb "Ouvrir").
     */
    public function label(): string
    {
        return match ($this) {
            self::InDiscussion => 'In discussion',
            self::Open => 'OPEN',
            self::Active => 'Active',
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
