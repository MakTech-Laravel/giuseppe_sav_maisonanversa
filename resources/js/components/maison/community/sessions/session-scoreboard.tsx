import { router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import {
    isSuperTiebreak,
    isTiebreakSet,
    matchWinner,
    scoreLine,
    setError,
    setWinner,
    visibleSetCount,
} from '@/lib/match-score';
import type { Side } from '@/lib/match-score';
import { formatSessionDateLong, formatSessionTime } from '@/lib/session-format';
import { cn } from '@/lib/utils';
import * as sessionRoutes from '@/routes/community/sessions';
import * as scoreboardRoutes from '@/routes/community/sessions/scoreboard';
import * as scoreRoutes from '@/routes/community/sessions/scores';
import type {
    MatchFormat,
    MatchFormatOption,
    MatchType,
    MatchTypeOption,
    ScoreSet,
    ScoreSubmission,
    SessionScoreboard as SessionScoreboardData,
} from '@/types/session';

type SessionScoreboardProps = {
    sessionId: number;
    scoreboard: SessionScoreboardData;
};

/**
 * Scoreboard of a held session, following the FIP padel and ITF tennis
 * scoring formats. Player submissions wait for the host; the host locks,
 * unlocks and finally closes the session.
 */
export function SessionScoreboard({
    sessionId,
    scoreboard,
}: SessionScoreboardProps) {
    const { t } = useTranslation();
    const { locale } = usePage().props;
    const [busy, setBusy] = useState<string | null>(null);
    const args = { locale, communitySession: sessionId };

    function patch(key: string, url: string) {
        setBusy(key);
        router.patch(
            url,
            {},
            { preserveScroll: true, onFinish: () => setBusy(null) },
        );
    }

    return (
        <div className="border-t border-gold/15 px-7 py-7">
            <h2 className="mb-1 font-sans text-[11px] font-medium tracking-[0.22em] text-choc uppercase">
                {t('Scorebord')}
            </h2>
            <p className="mb-5 font-sans text-[11px] text-stone">
                <WindowNote scoreboard={scoreboard} />
            </p>

            {scoreboard.official ? (
                <ResultCard score={scoreboard.official} accent />
            ) : (
                <p className="border border-dashed border-gold/25 bg-gold/5 px-5 py-4 font-sans text-[11px] tracking-[0.08em] text-stone">
                    {scoreboard.can_record_match
                        ? t('Nog geen officiële uitslag.')
                        : t(
                              'Voor {{sport}} zijn minstens {{count}} bevestigde spelers nodig om een uitslag te registreren.',
                              {
                                  sport: t(scoreboard.sport_label),
                                  count: scoreboard.min_players,
                              },
                          )}
                </p>
            )}

            {scoreboard.submissions.length > 0 && (
                <div className="mt-6 flex flex-col gap-3">
                    <h3 className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase">
                        {scoreboard.can_review
                            ? t('Ingediende scores')
                            : t('Uw ingediende score')}
                    </h3>

                    {scoreboard.submissions.map((submission) => (
                        <ResultCard key={submission.id} score={submission}>
                            {scoreboard.can_review &&
                                submission.status === 'pending' && (
                                    <span className="flex gap-2">
                                        <button
                                            type="button"
                                            disabled={busy !== null}
                                            onClick={() =>
                                                patch(
                                                    `approve-${submission.id}`,
                                                    scoreRoutes.approve({
                                                        ...args,
                                                        score: submission.id,
                                                    }).url,
                                                )
                                            }
                                            className={smallSolidButton}
                                        >
                                            {t('Goedkeuren')}
                                        </button>
                                        <button
                                            type="button"
                                            disabled={busy !== null}
                                            onClick={() =>
                                                patch(
                                                    `reject-${submission.id}`,
                                                    scoreRoutes.reject({
                                                        ...args,
                                                        score: submission.id,
                                                    }).url,
                                                )
                                            }
                                            className={smallOutlineButton}
                                        >
                                            {t('Weigeren')}
                                        </button>
                                    </span>
                                )}
                        </ResultCard>
                    ))}
                </div>
            )}

            {scoreboard.can_submit && scoreboard.can_record_match && (
                <ScoreForm sessionId={sessionId} scoreboard={scoreboard} />
            )}

            {(scoreboard.can_lock ||
                scoreboard.can_unlock ||
                scoreboard.can_review) && (
                <div className="mt-7 flex flex-wrap gap-3">
                    {scoreboard.can_lock && (
                        <button
                            type="button"
                            disabled={busy !== null}
                            onClick={() =>
                                patch('lock', scoreboardRoutes.lock(args).url)
                            }
                            className={outlineButton}
                        >
                            {t('Scorebord vergrendelen')}
                        </button>
                    )}

                    {scoreboard.can_unlock && (
                        <button
                            type="button"
                            disabled={busy !== null}
                            onClick={() =>
                                patch(
                                    'unlock',
                                    scoreboardRoutes.unlock(args).url,
                                )
                            }
                            className={outlineButton}
                        >
                            {t('Scorebord ontgrendelen')}
                        </button>
                    )}

                    {scoreboard.can_review && (
                        <button
                            type="button"
                            disabled={busy !== null || !scoreboard.can_finish}
                            onClick={() =>
                                patch('finish', sessionRoutes.finish(args).url)
                            }
                            className={solidButton}
                        >
                            {t('Sessie afsluiten')}
                        </button>
                    )}
                </div>
            )}

            {scoreboard.can_review && !scoreboard.can_finish && (
                <p className="mt-3 font-sans text-[10px] tracking-[0.08em] text-stone">
                    {scoreboard.can_record_match
                        ? t(
                              'Afsluiten kan zodra er een goedgekeurde uitslag is, er geen scores meer wachten en het scorebord vergrendeld is.',
                          )
                        : t(
                              'Te weinig spelers voor een uitslag: vergrendel het scorebord om de sessie zonder uitslag af te sluiten.',
                          )}
                </p>
            )}
        </div>
    );
}

function WindowNote({ scoreboard }: { scoreboard: SessionScoreboardData }) {
    const { t } = useTranslation();
    const { locale } = usePage().props;

    if (scoreboard.is_finished) {
        return <>{t('Sessie afgesloten. Dit is de officiële uitslag.')}</>;
    }

    if (scoreboard.is_locked) {
        return <>{t('Het scorebord is vergrendeld.')}</>;
    }

    if (scoreboard.window_open && scoreboard.window_closes_at) {
        return (
            <>
                {t('Scores indienen kan tot {{date}} om {{time}}.', {
                    date: formatSessionDateLong(
                        scoreboard.window_closes_at,
                        locale,
                    ),
                    time: formatSessionTime(
                        scoreboard.window_closes_at,
                        locale,
                    ),
                })}
            </>
        );
    }

    return <>{t('De termijn om een score in te dienen is verstreken.')}</>;
}

function useSideLabel() {
    const { t } = useTranslation();

    return (matchType: MatchType, side: Side): string => {
        if (matchType === 'singles') {
            return side === 'a' ? t('Speler A') : t('Speler B');
        }

        return side === 'a' ? t('Team A') : t('Team B');
    };
}

/**
 * The classic scoreboard grid: one row per side, one column per set, the
 * loser's tie-break points as a superscript and the super tie-break as STB.
 */
function ResultCard({
    score,
    accent = false,
    children,
}: {
    score: ScoreSubmission;
    accent?: boolean;
    children?: ReactNode;
}) {
    const { t } = useTranslation();
    const sideLabel = useSideLabel();

    return (
        <div
            className={cn(
                'flex flex-col gap-4 border px-5 py-4',
                accent ? 'border-gold/30 bg-gold/6' : 'border-gold/15 bg-cream',
            )}
        >
            <div className="flex flex-wrap items-center justify-between gap-3">
                <span
                    className={
                        accent
                            ? 'border border-gold/25 bg-gold/8 px-3 py-1.5 font-sans text-[9px] tracking-[0.16em] text-gold2 uppercase'
                            : 'border border-gold/20 px-3 py-1.5 font-sans text-[9px] tracking-[0.16em] text-stone uppercase'
                    }
                >
                    {accent ? t('Officiële uitslag') : t(score.status_label)}
                </span>
                <span className="font-sans text-[10px] tracking-[0.08em] text-stone">
                    {t(score.match_type_label)} · {t(score.format_label)}
                    {score.submitted_by &&
                        ` · ${t('Ingediend door {{name}}', { name: score.submitted_by })}`}
                </span>
            </div>

            <div className="overflow-x-auto">
                <table className="w-full min-w-[300px] border-collapse">
                    <thead>
                        <tr className="border-b border-gold/15">
                            <th className="py-2 pr-4 text-left font-sans text-[9px] font-normal tracking-[0.22em] text-stone uppercase">
                                {t('Uitslag')}
                            </th>
                            {score.sets.map((set, index) => (
                                <th
                                    key={index}
                                    className="w-12 py-2 text-center font-sans text-[9px] font-normal tracking-[0.16em] text-stone uppercase"
                                >
                                    {set.is_super_tiebreak ? 'STB' : index + 1}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {(['a', 'b'] as const).map((side) => {
                            const players =
                                side === 'a' ? score.team_a : score.team_b;
                            const isWinner = score.winner === side;

                            return (
                                <tr
                                    key={side}
                                    className="border-b border-gold/10 last:border-b-0"
                                >
                                    <td className="py-2.5 pr-4">
                                        <span className="flex flex-col gap-0.5">
                                            <span className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase">
                                                {sideLabel(
                                                    score.match_type,
                                                    side,
                                                )}
                                                {isWinner && (
                                                    <span className="ml-2 text-gold2">
                                                        {t('Winnaar')}
                                                    </span>
                                                )}
                                            </span>
                                            <span
                                                className={cn(
                                                    'font-sans text-[11px] tracking-[0.08em] text-choc',
                                                    isWinner && 'font-medium',
                                                )}
                                            >
                                                {players
                                                    .map(
                                                        (player) => player.name,
                                                    )
                                                    .join(' · ') || '—'}
                                            </span>
                                        </span>
                                    </td>
                                    {score.sets.map((set, index) => (
                                        <SetCell
                                            key={index}
                                            set={set}
                                            side={side}
                                        />
                                    ))}
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>

            {children}
        </div>
    );
}

function SetCell({ set, side }: { set: ScoreSet; side: Side }) {
    const wonSet = setWinner(set) === side;
    const tiebreakPoints = side === 'a' ? set.tiebreak_a : set.tiebreak_b;
    const showsTiebreak =
        !wonSet &&
        tiebreakPoints != null &&
        set.tiebreak_a != null &&
        set.tiebreak_b != null;

    return (
        <td
            className={cn(
                'py-2.5 text-center font-serif text-lg',
                wonSet ? 'text-choc' : 'text-stone',
            )}
        >
            {side === 'a' ? set.a : set.b}
            {showsTiebreak && (
                <sup className="ml-0.5 font-sans text-[9px]">
                    {tiebreakPoints}
                </sup>
            )}
        </td>
    );
}

type ScoreFormData = {
    match_type: MatchType;
    format: MatchFormat;
    team_a: number[];
    team_b: number[];
    sets: ScoreSet[];
};

const EMPTY_SET: ScoreSet = { a: 0, b: 0, tiebreak_a: null, tiebreak_b: null };

function defaultMatchType(
    types: MatchTypeOption[],
    playerCount: number,
): MatchType {
    const doubles = types.find(
        (type) => type.value === 'doubles' && playerCount >= 4,
    );
    const playable = types.find(
        (type) => playerCount >= type.players_per_side * 2,
    );

    return (doubles ?? playable ?? types[0]).value;
}

function ScoreForm({
    sessionId,
    scoreboard,
}: {
    sessionId: number;
    scoreboard: SessionScoreboardData;
}) {
    const { t } = useTranslation();
    const { locale } = usePage().props;
    const sideLabel = useSideLabel();
    const { players, match_types: matchTypes, formats, official } = scoreboard;

    const form = useForm<ScoreFormData>({
        match_type:
            official?.match_type ??
            defaultMatchType(matchTypes, players.length),
        format: official?.format ?? scoreboard.default_format,
        team_a: official?.team_a.map((player) => player.id) ?? [],
        team_b: official?.team_b.map((player) => player.id) ?? [],
        sets: [0, 1, 2].map((index) => {
            const set = official?.sets[index];

            return set
                ? {
                      a: set.a,
                      b: set.b,
                      tiebreak_a: set.tiebreak_a ?? null,
                      tiebreak_b: set.tiebreak_b ?? null,
                  }
                : EMPTY_SET;
        }),
    });

    const matchType =
        matchTypes.find((type) => type.value === form.data.match_type) ??
        matchTypes[0];
    const format =
        formats.find((option) => option.value === form.data.format) ??
        formats[0];
    const perSide = matchType.players_per_side;
    const setCount = visibleSetCount(format, form.data.sets);
    const enteredSets = form.data.sets.slice(0, setCount);
    const winner = enteredSets.every(
        (set, index) => setError(format, index, set) === null,
    )
        ? matchWinner(format, enteredSets)
        : null;
    const errors = form.errors as Record<string, string | undefined>;

    function chooseMatchType(type: MatchTypeOption) {
        form.setData((data) => ({
            ...data,
            match_type: type.value,
            team_a: data.team_a.slice(0, type.players_per_side),
            team_b: data.team_b.slice(0, type.players_per_side),
        }));
    }

    function assign(playerId: number, side: Side | null) {
        form.setData((data) => {
            const teamA = data.team_a.filter((id) => id !== playerId);
            const teamB = data.team_b.filter((id) => id !== playerId);

            return {
                ...data,
                team_a: side === 'a' ? [...teamA, playerId] : teamA,
                team_b: side === 'b' ? [...teamB, playerId] : teamB,
            };
        });
    }

    function updateSet(
        index: number,
        field: keyof Omit<ScoreSet, 'is_super_tiebreak'>,
        value: string,
    ) {
        const isTiebreakField =
            field === 'tiebreak_a' || field === 'tiebreak_b';
        const parsed =
            value === ''
                ? isTiebreakField
                    ? null
                    : 0
                : Math.max(0, Math.min(99, Number(value) || 0));

        form.setData(
            'sets',
            form.data.sets.map((set, current) => {
                if (current !== index) {
                    return set;
                }

                const next = { ...set, [field]: parsed };

                return isTiebreakSet(format, next) || isTiebreakField
                    ? next
                    : { ...next, tiebreak_a: null, tiebreak_b: null };
            }),
        );
    }

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            sets: data.sets
                .slice(0, setCount)
                .map((set, index) =>
                    isSuperTiebreak(format, index) ||
                    !isTiebreakSet(format, set)
                        ? {
                              a: set.a,
                              b: set.b,
                              tiebreak_a: null,
                              tiebreak_b: null,
                          }
                        : set,
                ),
        }));

        form.post(
            scoreRoutes.store({ locale, communitySession: sessionId }).url,
            { preserveScroll: true },
        );
    }

    const sideCount = (side: Side) =>
        (side === 'a' ? form.data.team_a : form.data.team_b).length;
    const sideNames = (side: Side) =>
        (side === 'a' ? form.data.team_a : form.data.team_b)
            .map((id) => players.find((player) => player.id === id)?.name)
            .filter(Boolean)
            .join(' · ');
    const generalErrors = Array.from(
        new Set(
            ['match_type', 'format', 'team_a', 'team_b', 'sets']
                .map((key) => errors[key])
                .concat(
                    Object.entries(errors)
                        .filter(([key]) => /^team_[ab]\./.test(key))
                        .map(([, message]) => message),
                )
                .filter((message): message is string => Boolean(message)),
        ),
    );

    return (
        <form
            onSubmit={handleSubmit}
            className="mt-7 flex flex-col gap-6 border border-gold/15 bg-cream px-5 py-5"
        >
            <h3 className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase">
                {t('Score indienen')}
            </h3>

            <FieldGroup label={t('Wedstrijd')}>
                {matchTypes.length > 1 ? (
                    <span className="flex flex-wrap gap-1.5">
                        {matchTypes.map((type) => {
                            const playable =
                                players.length >= type.players_per_side * 2;

                            return (
                                <button
                                    key={type.value}
                                    type="button"
                                    disabled={!playable}
                                    aria-pressed={
                                        form.data.match_type === type.value
                                    }
                                    onClick={() => chooseMatchType(type)}
                                    className={cn(
                                        choiceButton,
                                        form.data.match_type === type.value &&
                                            choiceButtonActive,
                                    )}
                                >
                                    {t(type.label)} ·{' '}
                                    {type.players_per_side === 1
                                        ? t('1 tegen 1')
                                        : t('2 tegen 2')}
                                </button>
                            );
                        })}
                    </span>
                ) : (
                    <span className="font-sans text-[11px] tracking-[0.08em] text-choc">
                        {t(scoreboard.sport_label)} · {t(matchType.label)} ·{' '}
                        {perSide === 1 ? t('1 tegen 1') : t('2 tegen 2')}
                    </span>
                )}
            </FieldGroup>

            <FieldGroup label={t('Formaat')}>
                <span className="grid gap-2 sm:grid-cols-2">
                    {formats.map((option) => (
                        <button
                            key={option.value}
                            type="button"
                            aria-pressed={form.data.format === option.value}
                            onClick={() => form.setData('format', option.value)}
                            className={cn(
                                'flex cursor-pointer flex-col gap-1 border border-gold/30 bg-transparent px-4 py-3 text-left transition-colors hover:border-gold hover:bg-gold/10',
                                form.data.format === option.value &&
                                    'border-choc bg-choc hover:border-choc hover:bg-choc',
                            )}
                        >
                            <span
                                className={cn(
                                    'font-sans text-[10px] font-medium tracking-[0.16em] uppercase',
                                    form.data.format === option.value
                                        ? 'text-cream'
                                        : 'text-choc',
                                )}
                            >
                                {t(option.label)}
                            </span>
                            <span
                                className={cn(
                                    'font-sans text-[10px] tracking-[0.04em]',
                                    form.data.format === option.value
                                        ? 'text-cream/80'
                                        : 'text-stone',
                                )}
                            >
                                {t(option.description)}
                            </span>
                        </button>
                    ))}
                </span>
            </FieldGroup>

            <FieldGroup
                label={t('Wie speelde er?')}
                hint={`${sideLabel(matchType.value, 'a')} ${sideCount('a')}/${perSide} · ${sideLabel(matchType.value, 'b')} ${sideCount('b')}/${perSide}`}
            >
                <span className="flex flex-col gap-2">
                    {players.map((player) => {
                        const side: Side | null = form.data.team_a.includes(
                            player.id,
                        )
                            ? 'a'
                            : form.data.team_b.includes(player.id)
                              ? 'b'
                              : null;

                        return (
                            <span
                                key={player.id}
                                className="flex flex-wrap items-center justify-between gap-3"
                            >
                                <span className="flex items-baseline gap-2 font-sans text-[11px] tracking-[0.08em] text-choc">
                                    {player.name}
                                    {side === null && (
                                        <span className="text-[9px] tracking-[0.16em] text-stone uppercase">
                                            {t('Zit uit')}
                                        </span>
                                    )}
                                </span>
                                <span className="flex gap-1.5">
                                    {(['a', 'b'] as const).map((option) => (
                                        <button
                                            key={option}
                                            type="button"
                                            aria-pressed={side === option}
                                            disabled={
                                                side !== option &&
                                                sideCount(option) >= perSide
                                            }
                                            onClick={() =>
                                                assign(
                                                    player.id,
                                                    side === option
                                                        ? null
                                                        : option,
                                                )
                                            }
                                            className={cn(
                                                choiceButton,
                                                side === option &&
                                                    choiceButtonActive,
                                            )}
                                        >
                                            {sideLabel(matchType.value, option)}
                                        </button>
                                    ))}
                                </span>
                            </span>
                        );
                    })}
                </span>
            </FieldGroup>

            <FieldGroup
                label={
                    format.games_per_set === 4
                        ? t('Sets (tot 4 games)')
                        : t('Sets (tot 6 games)')
                }
            >
                <span className="flex flex-col gap-3">
                    {enteredSets.map((set, index) => (
                        <SetRow
                            key={index}
                            index={index}
                            set={set}
                            format={format}
                            matchType={matchType.value}
                            serverError={errors[`sets.${index}`]}
                            onChange={(field, value) =>
                                updateSet(index, field, value)
                            }
                        />
                    ))}
                </span>
            </FieldGroup>

            <p className="border-t border-gold/15 pt-4 font-sans text-[11px] tracking-[0.08em] text-choc">
                {winner ? (
                    <>
                        <span className="font-serif text-base">
                            {scoreLine(format, enteredSets)}
                        </span>
                        {' · '}
                        {t('Winnaar: {{side}}', {
                            side: `${sideLabel(matchType.value, winner)}${sideNames(winner) ? ` (${sideNames(winner)})` : ''}`,
                        })}
                    </>
                ) : (
                    <span className="text-stone">
                        {t('De wedstrijd is nog niet beslist.')}
                    </span>
                )}
            </p>

            {generalErrors.length > 0 && (
                <ul className="flex flex-col gap-1">
                    {generalErrors.map((error) => (
                        <li
                            key={error}
                            className="font-sans text-[11px] text-red-800"
                        >
                            {error}
                        </li>
                    ))}
                </ul>
            )}

            <button
                type="submit"
                disabled={form.processing}
                className={cn(solidButton, 'w-fit')}
            >
                {t('Score indienen')}
            </button>
        </form>
    );
}

function FieldGroup({
    label,
    hint,
    children,
}: {
    label: string;
    hint?: string;
    children: ReactNode;
}) {
    return (
        <div className="flex flex-col gap-2.5">
            <span className="flex flex-wrap items-baseline justify-between gap-2">
                <span className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase">
                    {label}
                </span>
                {hint && (
                    <span className="font-sans text-[9px] tracking-[0.16em] text-stone uppercase">
                        {hint}
                    </span>
                )}
            </span>
            {children}
        </div>
    );
}

function SetRow({
    index,
    set,
    format,
    matchType,
    serverError,
    onChange,
}: {
    index: number;
    set: ScoreSet;
    format: MatchFormatOption;
    matchType: MatchType;
    serverError?: string;
    onChange: (
        field: keyof Omit<ScoreSet, 'is_super_tiebreak'>,
        value: string,
    ) => void;
}) {
    const { t } = useTranslation();
    const sideLabel = useSideLabel();
    const superTiebreak = isSuperTiebreak(format, index);
    const touched = set.a > 0 || set.b > 0;
    const liveError = touched ? setError(format, index, set) : null;
    const message = liveError ?? serverError ?? null;
    const showsTiebreak = !superTiebreak && isTiebreakSet(format, set);
    const unit = superTiebreak ? t('Punten') : t('Games');

    return (
        <div className="flex flex-col gap-2">
            <div className="flex flex-wrap items-center gap-2">
                <span className="w-32 font-sans text-[10px] tracking-[0.08em] text-stone">
                    {superTiebreak
                        ? t('Super tie-break (tot 10)')
                        : t('Set {{number}}', { number: index + 1 })}
                </span>
                <input
                    type="number"
                    min={0}
                    max={99}
                    inputMode="numeric"
                    aria-label={`${unit} ${sideLabel(matchType, 'a')}`}
                    value={set.a}
                    onChange={(event) => onChange('a', event.target.value)}
                    className={cn(fieldClassName, 'w-20 text-center')}
                />
                <span className="text-stone">–</span>
                <input
                    type="number"
                    min={0}
                    max={99}
                    inputMode="numeric"
                    aria-label={`${unit} ${sideLabel(matchType, 'b')}`}
                    value={set.b}
                    onChange={(event) => onChange('b', event.target.value)}
                    className={cn(fieldClassName, 'w-20 text-center')}
                />
            </div>

            {showsTiebreak && (
                <div className="flex flex-wrap items-center gap-2 sm:pl-34">
                    <span className="font-sans text-[9px] tracking-[0.16em] text-stone uppercase">
                        {t('Punten tie-break (optioneel)')}
                    </span>
                    <input
                        type="number"
                        min={0}
                        max={99}
                        inputMode="numeric"
                        aria-label={`${t('Tie-break')} ${sideLabel(matchType, 'a')}`}
                        value={set.tiebreak_a ?? ''}
                        onChange={(event) =>
                            onChange('tiebreak_a', event.target.value)
                        }
                        className={cn(fieldClassName, 'w-16 py-1 text-center')}
                    />
                    <span className="text-stone">–</span>
                    <input
                        type="number"
                        min={0}
                        max={99}
                        inputMode="numeric"
                        aria-label={`${t('Tie-break')} ${sideLabel(matchType, 'b')}`}
                        value={set.tiebreak_b ?? ''}
                        onChange={(event) =>
                            onChange('tiebreak_b', event.target.value)
                        }
                        className={cn(fieldClassName, 'w-16 py-1 text-center')}
                    />
                </div>
            )}

            {message && (
                <p className="font-sans text-[10px] text-red-800 sm:pl-34">
                    {t(message)}
                </p>
            )}
        </div>
    );
}

const fieldClassName =
    'border border-gold/20 bg-cream px-3 py-2 font-serif text-base text-choc outline-none transition-colors focus:border-gold2';

const choiceButton =
    'cursor-pointer border border-gold/30 bg-transparent px-3 py-1.5 font-sans text-[9px] font-medium tracking-[0.16em] text-choc uppercase transition-colors hover:border-gold hover:bg-gold/10 disabled:cursor-not-allowed disabled:opacity-40';

const choiceButtonActive = 'border-choc bg-choc text-cream hover:bg-choc';

const solidButton =
    'cursor-pointer border-none bg-choc px-7 py-3.5 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase transition-colors hover:bg-gold2 disabled:cursor-not-allowed disabled:opacity-50';

const outlineButton =
    'cursor-pointer border border-gold/30 bg-transparent px-7 py-3.5 font-sans text-[10px] font-medium tracking-[0.2em] text-choc uppercase transition-colors hover:border-gold hover:bg-gold/10 disabled:cursor-not-allowed disabled:opacity-50';

const smallSolidButton =
    'cursor-pointer border-none bg-choc px-5 py-2.5 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase transition-colors hover:bg-gold2 disabled:cursor-not-allowed disabled:opacity-50';

const smallOutlineButton =
    'cursor-pointer border border-gold/30 bg-transparent px-5 py-2.5 font-sans text-[10px] font-medium tracking-[0.2em] text-choc uppercase transition-colors hover:border-gold hover:bg-gold/10 disabled:cursor-not-allowed disabled:opacity-50';
