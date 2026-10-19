<?php

namespace App\Enums;

/**
 * A participant row is a join request until the host decides. Pending rows
 * reserve a seat; only confirmed rows are players.
 */
enum SessionParticipantStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';

    /**
     * Dutch source label — the i18n key the frontend passes to t().
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Aanvraag in behandeling',
            self::Confirmed => 'Bevestigd',
            self::Rejected => 'Aanvraag geweigerd',
        };
    }

    /**
     * @return list<string>
     */
    public static function seatHolding(): array
    {
        return [self::Pending->value, self::Confirmed->value];
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
