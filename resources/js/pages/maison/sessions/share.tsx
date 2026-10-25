import { Link, router, usePage } from '@inertiajs/react';
import { CalendarDays, Clock } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import {
    CopyShareLinkButton,
    MatchCardShare,
} from '@/components/maison/community/sessions/match-card-share';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { useOptionalShellActions } from '@/components/maison/shell/shell-actions';
import { PageHero } from '@/components/maison/ui/page-hero';
import { Wrap } from '@/components/maison/ui/section';
import { useResumePendingSessionJoin } from '@/hooks/use-resume-pending-session-join';
import { storePendingSessionJoin } from '@/lib/pending-session-join';
import { formatSessionDateLong, formatSessionTime } from '@/lib/session-format';
import { cn } from '@/lib/utils';
import * as sessionRoutes from '@/routes/community/sessions';
import type {
    MatchCardLinks,
    MatchCardPlayer,
    SessionCard,
} from '@/types/session';

type SessionShareProps = {
    session: SessionCard;
    players: MatchCardPlayer[];
    result: string | null;
    card: MatchCardLinks | null;
    shareUrl: string | null;
    memberUrl: string | null;
};

/**
 * Public page behind the session share link. Everyone sees who plays and the
 * result; the organiser and confirmed players also get the match card.
 */
export default function SessionShare({
    session,
    players,
    result,
    card,
    shareUrl,
    memberUrl,
}: SessionShareProps) {
    const { t } = useTranslation();
    const { auth, locale } = usePage().props;
    const shellActions = useOptionalShellActions();
    const title = session.club?.name ?? t('Sessie');
    const isUpcoming = !session.is_past && !session.is_cancelled;
    const pendingCount = isUpcoming ? (session.pending_count ?? 0) : 0;
    const openSlots = isUpcoming ? session.open_slots : 0;
    const guestCanJoin =
        !auth?.user &&
        isUpcoming &&
        !session.is_full &&
        !session.registration_closed &&
        openSlots > 0;
    const hasActions =
        session.can_join ||
        guestCanJoin ||
        memberUrl ||
        (!card && shareUrl);

    function freePlacesLabel(): string {
        if (session.is_full || openSlots <= 0) {
            return t('Volzet');
        }

        if (openSlots === 1) {
            return t('1 plaats vrij');
        }

        return t('{{count}} plaatsen vrij', { count: openSlots });
    }

    useResumePendingSessionJoin([session]);

    function handleJoin() {
        if (!auth?.user) {
            storePendingSessionJoin(session.id);
            shellActions?.openAuth('register');

            return;
        }

        router.post(
            sessionRoutes.join({ locale, communitySession: Number(session.id) })
                .url,
            {},
            { preserveScroll: true },
        );
    }

    function handleCreateAccount() {
        storePendingSessionJoin(session.id);
        shellActions?.openAuth('register');
    }

    return (
        <>
            <MaisonSeoHead />

            <PageHero
                eyebrow={`${t('Sessie')} · ${session.sport_label}`}
                title={title}
                subtitle={session.club?.address ?? undefined}
            />

            <div className="bg-cream py-12">
                <Wrap className="px-6 md:px-10 lg:px-20">
                    <div
                        className={cn(
                            'border border-gold/15 bg-cream2',
                            card
                                ? 'lg:grid lg:grid-cols-[minmax(0,1fr)_380px]'
                                : 'mx-auto max-w-3xl',
                        )}
                    >
                        <div className="min-w-0">
                            <div className="flex flex-wrap gap-x-8 gap-y-4 border-b border-gold/15 px-7 py-6 font-sans text-[11px] tracking-[0.08em] text-stone">
                                <span className="inline-flex items-center gap-2">
                                    <CalendarDays
                                        className="size-3.5"
                                        aria-hidden="true"
                                    />
                                    {formatSessionDateLong(
                                        session.starts_at,
                                        locale,
                                    )}
                                </span>
                                <span className="inline-flex items-center gap-2">
                                    <Clock
                                        className="size-3.5"
                                        aria-hidden="true"
                                    />
                                    {formatSessionTime(
                                        session.starts_at,
                                        locale,
                                    )}
                                </span>
                            </div>

                            <div className="px-7 py-7">
                                <h2 className="mb-1 font-sans text-[11px] font-medium tracking-[0.22em] text-choc uppercase">
                                    {t('Spelers')}
                                </h2>
                                <p className="mb-5 font-sans text-[11px] text-stone">
                                    {session.participants_count} /{' '}
                                    {session.capacity}
                                    {isUpcoming && (
                                        <>
                                            {' · '}
                                            <span
                                                className={cn(
                                                    openSlots > 0 &&
                                                        'text-gold2',
                                                )}
                                            >
                                                {freePlacesLabel()}
                                            </span>
                                        </>
                                    )}
                                    {pendingCount > 0 &&
                                        ` · ${t('{{count}} in afwachting', { count: pendingCount })}`}
                                    {isUpcoming &&
                                        session.registration_closed &&
                                        !session.is_full &&
                                        ` · ${t('Inschrijving gesloten')}`}
                                </p>

                                <ul className="flex flex-wrap gap-6">
                                    {players.map((player, index) => (
                                        <li
                                            key={`${player.initials}-${index}`}
                                            className="flex w-20 flex-col items-center text-center"
                                        >
                                            <span
                                                className={cn(
                                                    'flex size-14 items-center justify-center rounded-full border bg-choc2 font-serif text-lg text-cream',
                                                    player.edition
                                                        ? 'border-gold'
                                                        : 'border-gold/25',
                                                )}
                                            >
                                                {player.initials}
                                            </span>
                                            {player.first_name && (
                                                <span className="mt-2 font-serif text-sm text-choc">
                                                    {player.first_name}
                                                </span>
                                            )}
                                            {player.edition && (
                                                <span className="mt-1 font-sans text-[9px] tracking-[0.18em] text-gold2 uppercase">
                                                    {player.edition}
                                                </span>
                                            )}
                                        </li>
                                    ))}

                                    {Array.from({ length: pendingCount }).map(
                                        (_, index) => (
                                            <li
                                                key={`pending-${index}`}
                                                className="flex w-20 flex-col items-center text-center"
                                            >
                                                <span className="flex size-14 items-center justify-center rounded-full border border-dotted border-gold/40 bg-gold/10 font-serif text-lg text-gold2">
                                                    …
                                                </span>
                                                <span className="mt-2 font-sans text-[9px] tracking-[0.16em] text-stone uppercase">
                                                    {t('In afwachting')}
                                                </span>
                                            </li>
                                        ),
                                    )}

                                    {Array.from({ length: openSlots }).map(
                                        (_, index) => (
                                            <li
                                                key={`open-${index}`}
                                                className="flex w-20 flex-col items-center text-center"
                                            >
                                                {session.can_join ? (
                                                    <button
                                                        type="button"
                                                        onClick={handleJoin}
                                                        aria-label={t(
                                                            'Vraag om mee te spelen',
                                                        )}
                                                        className="flex size-14 cursor-pointer items-center justify-center rounded-full border border-dashed border-gold/50 bg-gold/5 font-serif text-xl text-gold2 transition-colors hover:border-gold hover:bg-gold/15"
                                                    >
                                                        +
                                                    </button>
                                                ) : (
                                                    <span className="flex size-14 items-center justify-center rounded-full border border-dashed border-gold/30 bg-gold/5 font-serif text-xl text-gold/40">
                                                        +
                                                    </span>
                                                )}
                                                <span className="mt-2 font-sans text-[9px] tracking-[0.16em] text-stone uppercase">
                                                    {t('Vrij')}
                                                </span>
                                            </li>
                                        ),
                                    )}
                                </ul>

                                {result && (
                                    <p className="mt-7 font-sans text-[10px] tracking-[0.22em] text-stone uppercase">
                                        {t('Uitslag')}
                                        <span className="ml-3 font-serif text-xl tracking-normal text-choc normal-case">
                                            {result}
                                        </span>
                                    </p>
                                )}

                                {session.is_cancelled && (
                                    <p className="mt-7 font-serif text-base text-choc3 italic">
                                        {t('Deze sessie is geannuleerd.')}
                                    </p>
                                )}
                            </div>

                            {hasActions && (
                                <div className="flex flex-wrap gap-3 border-t border-gold/15 px-7 py-6">
                                    {guestCanJoin && (
                                        <button
                                            type="button"
                                            onClick={handleCreateAccount}
                                            className="cursor-pointer border-none bg-choc px-7 py-3.5 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase transition-colors hover:bg-gold2"
                                        >
                                            {t(
                                                'Maak een account om mee te doen',
                                            )}
                                        </button>
                                    )}

                                    {session.can_join && (
                                        <button
                                            type="button"
                                            onClick={handleJoin}
                                            className="cursor-pointer border-none bg-choc px-7 py-3.5 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase transition-colors hover:bg-gold2"
                                        >
                                            {session.requests_required
                                                ? t('Vraag om mee te spelen')
                                                : t('Doe mee')}
                                        </button>
                                    )}

                                    {!card && shareUrl && (
                                        <CopyShareLinkButton url={shareUrl} />
                                    )}

                                    {memberUrl && (
                                        <Link
                                            href={memberUrl}
                                            className="cursor-pointer border border-gold/30 bg-transparent px-7 py-3.5 font-sans text-[10px] font-medium tracking-[0.2em] text-choc uppercase transition-colors hover:border-gold hover:bg-gold/10"
                                        >
                                            {t('Bekijk sessie')}
                                        </Link>
                                    )}
                                </div>
                            )}
                        </div>

                        {card && (
                            <aside className="border-t border-gold/15 px-7 py-7 lg:border-t-0 lg:border-l">
                                <div className="lg:sticky lg:top-28">
                                    <MatchCardShare card={card} title={title} />
                                </div>
                            </aside>
                        )}
                    </div>
                </Wrap>
            </div>
        </>
    );
}
