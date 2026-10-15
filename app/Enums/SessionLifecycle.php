<?php

namespace App\Enums;

/**
 * Derived from timestamps, never stored: Scheduled until `ends_at`, Held
 * afterwards, Finished once the host closes the scoreboard. Cancelled wins.
 */
enum SessionLifecycle: string
{
    case Scheduled = 'scheduled';
    case Held = 'held';
    case Finished = 'finished';
    case Cancelled = 'cancelled';

    /**
     * Dutch source label — the i18n key the frontend passes to t().
     */
    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Gepland',
            self::Held => 'Gespeeld',
            self::Finished => 'Afgesloten',
            self::Cancelled => 'Geannuleerd',
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
