<?php

namespace App\Support;

use App\Enums\SessionMatchFormat;

/**
 * Padel and tennis scoring rules (FIP Rules of Padel, ITF Rules of Tennis).
 * The single source of truth for what a valid set and a complete match are;
 * resources/js/lib/match-score.ts mirrors it for live feedback only.
 *
 * A set entry holds games ("a", "b") and optional tie-break points. In the
 * super tie-break format the third entry holds points instead of games.
 */
class MatchScore
{
    public const TIEBREAK_POINTS = 7;

    public const SUPER_TIEBREAK_POINTS = 10;

    /**
     * @param  array{a: int, b: int, tiebreak_a?: int|null, tiebreak_b?: int|null}  $set
     */
    public static function setError(SessionMatchFormat $format, int $index, array $set): ?string
    {
        $a = (int) $set['a'];
        $b = (int) $set['b'];
        $tiebreakA = $set['tiebreak_a'] ?? null;
        $tiebreakB = $set['tiebreak_b'] ?? null;

        if ($format->isSuperTiebreak($index)) {
            return self::isRaceWon(max($a, $b), min($a, $b), self::SUPER_TIEBREAK_POINTS)
                ? null
                : __('Geen geldige super tie-break (tot 10, met 2 punten verschil).');
        }

        $games = $format->gamesPerSet();
        $winner = max($a, $b);
        $loser = min($a, $b);
        $isTiebreakSet = $winner === $games + 1 && $loser === $games;

        $isValidSet = $a !== $b && (
            ($winner === $games && $loser <= $games - 2)
            || ($winner === $games + 1 && $loser === $games - 1)
            || $isTiebreakSet
        );

        if (! $isValidSet) {
            return __('Geen geldige setscore.');
        }

        if ($tiebreakA === null && $tiebreakB === null) {
            return null;
        }

        if (! $isTiebreakSet) {
            return __('Tie-breakpunten horen alleen bij een set die op een tie-break eindigde.');
        }

        if ($tiebreakA === null || $tiebreakB === null) {
            return __('Geen geldige tie-break.');
        }

        [$setWinnerPoints, $setLoserPoints] = $a > $b
            ? [(int) $tiebreakA, (int) $tiebreakB]
            : [(int) $tiebreakB, (int) $tiebreakA];

        return self::isRaceWon($setWinnerPoints, $setLoserPoints, self::TIEBREAK_POINTS)
            ? null
            : __('Geen geldige tie-break.');
    }

    /**
     * Checks the match as a whole: it must be decided, and nothing may be
     * played after the deciding set.
     *
     * @param  list<array{a: int, b: int}>  $sets
     */
    public static function matchError(SessionMatchFormat $format, array $sets): ?string
    {
        if ($sets === []) {
            return __('Vul minstens één set in.');
        }

        if (count($sets) > $format->maxSets()) {
            return __('Na de beslissende set kan geen set meer volgen.');
        }

        $won = ['a' => 0, 'b' => 0];

        foreach (array_values($sets) as $set) {
            if (max($won) >= $format->setsToWin()) {
                return __('Na de beslissende set kan geen set meer volgen.');
            }

            $side = self::setWinner($set);

            if ($side !== null) {
                $won[$side]++;
            }
        }

        return max($won) >= $format->setsToWin()
            ? null
            : __('De wedstrijd is nog niet beslist.');
    }

    /**
     * Every validation message for a submission, keyed like the request
     * fields ("sets.0", "sets").
     *
     * @param  list<array{a: int, b: int, tiebreak_a?: int|null, tiebreak_b?: int|null}>  $sets
     * @return array<string, string>
     */
    public static function errors(SessionMatchFormat $format, array $sets): array
    {
        $errors = [];

        foreach (array_values($sets) as $index => $set) {
            $message = self::setError($format, $index, $set);

            if ($message !== null) {
                $errors["sets.{$index}"] = $message;
            }
        }

        if ($errors === []) {
            $message = self::matchError($format, array_values($sets));

            if ($message !== null) {
                $errors['sets'] = $message;
            }
        }

        return $errors;
    }

    /**
     * @param  list<array{a: int, b: int}>  $sets
     * @return 'a'|'b'|null
     */
    public static function winner(SessionMatchFormat $format, array $sets): ?string
    {
        $won = ['a' => 0, 'b' => 0];

        foreach ($sets as $set) {
            $side = self::setWinner($set);

            if ($side !== null) {
                $won[$side]++;
            }
        }

        return match (true) {
            $won['a'] > $won['b'] => 'a',
            $won['b'] > $won['a'] => 'b',
            default => null,
        };
    }

    /**
     * "6-4, 6-7(5), [10-7]": a tie-break set carries the loser's tie-break
     * points, a super tie-break is wrapped in brackets.
     *
     * @param  list<array{a: int, b: int, tiebreak_a?: int|null, tiebreak_b?: int|null}>  $sets
     */
    public static function line(SessionMatchFormat $format, array $sets): string
    {
        return collect(array_values($sets))
            ->map(function (array $set, int $index) use ($format): string {
                $score = $set['a'].'-'.$set['b'];

                if ($format->isSuperTiebreak($index)) {
                    return '['.$score.']';
                }

                $tiebreakA = $set['tiebreak_a'] ?? null;
                $tiebreakB = $set['tiebreak_b'] ?? null;

                if ($tiebreakA !== null && $tiebreakB !== null) {
                    $score .= '('.min((int) $tiebreakA, (int) $tiebreakB).')';
                }

                return $score;
            })
            ->implode(', ');
    }

    /**
     * @param  array{a: int, b: int}  $set
     * @return 'a'|'b'|null
     */
    private static function setWinner(array $set): ?string
    {
        return match (true) {
            (int) $set['a'] > (int) $set['b'] => 'a',
            (int) $set['b'] > (int) $set['a'] => 'b',
            default => null,
        };
    }

    /**
     * First to the target with a two-point margin, stopping as soon as the
     * margin is reached (12-10 is valid, 13-10 is not).
     */
    private static function isRaceWon(int $winner, int $loser, int $target): bool
    {
        if ($winner < $target || $winner - $loser < 2) {
            return false;
        }

        return $winner === $target || $winner - $loser === 2;
    }
}
