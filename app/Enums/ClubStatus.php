<?php

namespace App\Enums;

enum ClubStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Merged = 'merged';

    /**
     * Dutch source label — the i18n key the frontend passes to t().
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'In afwachting',
            self::Approved => 'Goedgekeurd',
            self::Rejected => 'Afgewezen',
            self::Merged => 'Samengevoegd',
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
