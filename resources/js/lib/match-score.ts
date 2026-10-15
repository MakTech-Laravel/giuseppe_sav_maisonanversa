import type { MatchFormatOption, ScoreSet } from '@/types/session';

/**
 * Mirrors app/Support/MatchScore.php for live feedback while typing. The
 * server stays authoritative; messages are Dutch source keys for t().
 */
export const TIEBREAK_POINTS = 7;
export const SUPER_TIEBREAK_POINTS = 10;

export type Side = 'a' | 'b';

export function isSuperTiebreak(
    format: MatchFormatOption,
    index: number,
): boolean {
    return format.super_tiebreak && index === 2;
}

export function setWinner(set: ScoreSet): Side | null {
    if (set.a > set.b) {
        return 'a';
    }

    if (set.b > set.a) {
        return 'b';
    }

    return null;
}

/** A set that went to a tie-break at games-all (7-6, or 5-4 in short sets). */
export function isTiebreakSet(
    format: MatchFormatOption,
    set: ScoreSet,
): boolean {
    const games = format.games_per_set;

    return (
        Math.max(set.a, set.b) === games + 1 && Math.min(set.a, set.b) === games
    );
}

function isRaceWon(winner: number, loser: number, target: number): boolean {
    if (winner < target || winner - loser < 2) {
        return false;
    }

    return winner === target || winner - loser === 2;
}

export function setError(
    format: MatchFormatOption,
    index: number,
    set: ScoreSet,
): string | null {
    const winner = Math.max(set.a, set.b);
    const loser = Math.min(set.a, set.b);

    if (isSuperTiebreak(format, index)) {
        return isRaceWon(winner, loser, SUPER_TIEBREAK_POINTS)
            ? null
            : 'Geen geldige super tie-break (tot 10, met 2 punten verschil).';
    }

    const games = format.games_per_set;
    const tiebreakSet = isTiebreakSet(format, set);
    const validSet =
        set.a !== set.b &&
        ((winner === games && loser <= games - 2) ||
            (winner === games + 1 && loser === games - 1) ||
            tiebreakSet);

    if (!validSet) {
        return 'Geen geldige setscore.';
    }

    const tiebreakA = set.tiebreak_a ?? null;
    const tiebreakB = set.tiebreak_b ?? null;

    if (tiebreakA === null && tiebreakB === null) {
        return null;
    }

    if (!tiebreakSet) {
        return 'Tie-breakpunten horen alleen bij een set die op een tie-break eindigde.';
    }

    if (tiebreakA === null || tiebreakB === null) {
        return 'Geen geldige tie-break.';
    }

    const [setWinnerPoints, setLoserPoints] =
        set.a > set.b ? [tiebreakA, tiebreakB] : [tiebreakB, tiebreakA];

    return isRaceWon(setWinnerPoints, setLoserPoints, TIEBREAK_POINTS)
        ? null
        : 'Geen geldige tie-break.';
}

export function setsWon(sets: ScoreSet[]): Record<Side, number> {
    return sets.reduce(
        (won, set) => {
            const side = setWinner(set);

            return side ? { ...won, [side]: won[side] + 1 } : won;
        },
        { a: 0, b: 0 } as Record<Side, number>,
    );
}

/**
 * How many set rows the form should show for the scores entered so far:
 * the next set appears only while the match is still undecided.
 */
export function visibleSetCount(
    format: MatchFormatOption,
    sets: ScoreSet[],
): number {
    let count = 0;
    const won: Record<Side, number> = { a: 0, b: 0 };

    for (let index = 0; index < format.max_sets; index++) {
        count = index + 1;

        const set = sets[index];

        if (!set) {
            break;
        }

        const side = setWinner(set);

        if (side === null || setError(format, index, set) !== null) {
            break;
        }

        won[side]++;

        if (won[side] >= format.sets_to_win) {
            break;
        }
    }

    return Math.max(count, Math.min(format.sets_to_win, format.max_sets));
}

export function matchWinner(
    format: MatchFormatOption,
    sets: ScoreSet[],
): Side | null {
    const won = setsWon(sets);

    if (won.a >= format.sets_to_win) {
        return 'a';
    }

    if (won.b >= format.sets_to_win) {
        return 'b';
    }

    return null;
}

/** "6-4, 6-7(5), [10-7]", matching MatchScore::line(). */
export function scoreLine(format: MatchFormatOption, sets: ScoreSet[]): string {
    return sets
        .map((set, index) => {
            const score = `${set.a}-${set.b}`;

            if (isSuperTiebreak(format, index)) {
                return `[${score}]`;
            }

            if (set.tiebreak_a != null && set.tiebreak_b != null) {
                return `${score}(${Math.min(set.tiebreak_a, set.tiebreak_b)})`;
            }

            return score;
        })
        .join(', ');
}
