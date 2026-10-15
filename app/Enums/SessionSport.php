<?php

namespace App\Enums;

enum SessionSport: string
{
    case Padel = 'padel';
    case Tennis = 'tennis';

    /**
     * Dutch source label — the i18n key the frontend passes to t().
     */
    public function label(): string
    {
        return match ($this) {
            self::Padel => 'Padel',
            self::Tennis => 'Tennis',
        };
    }

    /**
     * Padel is always played in pairs (FIP); tennis allows singles and
     * doubles (ITF).
     *
     * @return list<SessionMatchType>
     */
    public function matchTypes(): array
    {
        return match ($this) {
            self::Padel => [SessionMatchType::Doubles],
            self::Tennis => [SessionMatchType::Singles, SessionMatchType::Doubles],
        };
    }

    /**
     * Fewest confirmed players needed to record any match in this sport.
     */
    public function minimumPlayers(): int
    {
        return min(array_map(
            static fn (SessionMatchType $type): int => $type->playersPerSide() * 2,
            $this->matchTypes(),
        ));
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
