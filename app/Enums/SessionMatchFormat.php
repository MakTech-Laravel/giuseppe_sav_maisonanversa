<?php

namespace App\Enums;

/**
 * Match formats from the FIP Rules of Padel and the ITF Rules of Tennis
 * (Appendix V alternative scoring). Sets are won by two games with a
 * 7-point tie-break at games-all; a super tie-break is first to 10 by two.
 */
enum SessionMatchFormat: string
{
    case BestOfThree = 'best_of_3';
    case SuperTiebreak = 'super_tiebreak';
    case OneSet = 'one_set';
    case ShortSets = 'short_sets';

    /**
     * Dutch source label — the i18n key the frontend passes to t().
     */
    public function label(): string
    {
        return match ($this) {
            self::BestOfThree => 'Beste van 3 sets',
            self::SuperTiebreak => '2 sets + super tie-break',
            self::OneSet => 'Eén set',
            self::ShortSets => 'Korte sets',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::BestOfThree => 'Wie eerst 2 sets wint. Tie-break bij 6-6.',
            self::SuperTiebreak => 'Bij 1-1 in sets beslist een super tie-break tot 10.',
            self::OneSet => 'Eén set tot 6 games. Tie-break bij 6-6.',
            self::ShortSets => 'Beste van 3 sets tot 4 games. Tie-break bij 4-4.',
        };
    }

    /**
     * ITF Tennis Open Data Standards MatchUp format code.
     */
    public function code(): string
    {
        return match ($this) {
            self::BestOfThree => 'SET3-S:6/TB7',
            self::SuperTiebreak => 'SET3-S:6/TB7-F:TB10',
            self::OneSet => 'SET1-S:6/TB7',
            self::ShortSets => 'SET3-S:4/TB7',
        };
    }

    public function setsToWin(): int
    {
        return $this === self::OneSet ? 1 : 2;
    }

    public function maxSets(): int
    {
        return $this === self::OneSet ? 1 : 3;
    }

    public function gamesPerSet(): int
    {
        return $this === self::ShortSets ? 4 : 6;
    }

    public function decidesWithSuperTiebreak(): bool
    {
        return $this === self::SuperTiebreak;
    }

    /**
     * Whether the set at this zero-based position is a super tie-break.
     */
    public function isSuperTiebreak(int $index): bool
    {
        return $this->decidesWithSuperTiebreak() && $index === 2;
    }

    /**
     * @return list<array{value: string, label: string, description: string, sets_to_win: int, max_sets: int, games_per_set: int, super_tiebreak: bool}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
                'description' => $case->description(),
                'sets_to_win' => $case->setsToWin(),
                'max_sets' => $case->maxSets(),
                'games_per_set' => $case->gamesPerSet(),
                'super_tiebreak' => $case->decidesWithSuperTiebreak(),
            ],
            self::cases(),
        );
    }
}
