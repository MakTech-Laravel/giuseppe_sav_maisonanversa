import { Link, router, usePage } from '@inertiajs/react';
import { CalendarDays, Clock, Gauge, Timer, Users } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { CommunityTabs } from '@/components/maison/community/community-tabs';
import { PartnerBadge } from '@/components/maison/community/partner-badge';
import { CancelSessionDialog } from '@/components/maison/community/sessions/cancel-session-dialog';
import { SessionChatButton } from '@/components/maison/community/sessions/chat/session-chat-button';
import { JoinRequests } from '@/components/maison/community/sessions/join-requests';
import {
    CopyShareLinkButton,
    MatchCardShare,
} from '@/components/maison/community/sessions/match-card-share';
import { PlayerSlots } from '@/components/maison/community/sessions/player-slots';
import { SessionScoreboard } from '@/components/maison/community/sessions/session-scoreboard';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { PageHero } from '@/components/maison/ui/page-hero';
import { Wrap } from '@/components/maison/ui/section';
import {
    formatDuration,
    formatSessionDateLong,
    formatSessionTime,
} from '@/lib/session-format';
import { cn } from '@/lib/utils';
import * as sessionRoutes from '@/routes/community/sessions';
import * as participantRoutes from '@/routes/community/sessions/participants';
import type {
    JoinRequest,
    MatchCardLinks,
    SessionCard,
    SessionPlayer,
    SessionScoreboard as SessionScoreboardData,
} from '@/types/session';

type SessionShowProps = {
    session: SessionCard;
    joinRequests?: JoinRequest[];
    scoreboard?: SessionScoreboardData | null;
    matchCard?: MatchCardLinks | null;
    shareUrl?: string | null;
};

export default function SessionShow({
    session,
    joinRequests = [],
    scoreboard = null,
    matchCard = null,
    shareUrl = null,
}: SessionShowProps) {
    const { t } = useTranslation();
    const page = usePage();
    const { locale } = page.props;
    const args = { locale, communitySession: Number(session.id) };
    const pendingCount = session.pending_count ?? 0;
    const isRejected = session.viewer_status === 'rejected';
    const wantsCancel =
        new URLSearchParams(page.url.split('?')[1] ?? '').get('cancel') === '1';
    const seatsAllRequested =
        !session.can_join &&
        !session.is_joined &&
        !session.is_pending &&
        !isRejected &&
        !session.is_full &&
        !session.is_past &&
        !session.is_cancelled &&
        !session.registration_closed;

    function handleJoin() {
        router.post(sessionRoutes.join(args).url, {}, { preserveScroll: true });
    }

    function handleLeave() {
        router.delete(sessionRoutes.leave(args).url, { preserveScroll: true });
    }

    function handleRemove(player: SessionPlayer) {
        router.delete(
            participantRoutes.destroy({ ...args, user: player.id }).url,
            { preserveScroll: true },
        );
    }

    return (
        <>
            <MaisonSeoHead />

            <PageHero
                eyebrow={`${t(session.sport_label)}${session.club ? ` · ${session.club.city}` : ''}`}
                title={session.club?.name ?? t('Sessie')}
                subtitle={
                    session.club
                        ? session.club.address
                        : t('Deze club is niet langer beschikbaar.')
                }
            />

            <CommunityTabs />

            <div className="bg-cream py-12">
                <Wrap className="px-6 md:px-10 lg:px-20">
                    <div
                        className={cn(
                            'border border-gold/15 bg-cream2',
                            matchCard &&
                                'lg:grid lg:grid-cols-[minmax(0,1fr)_380px] lg:grid-rows-[auto_1fr]',
                        )}
                    >
                        <div className="min-w-0 lg:col-start-1 lg:row-start-1">
                            <div className="flex flex-wrap gap-x-8 gap-y-4 border-b border-gold/15 px-7 py-6 font-sans text-[11px] tracking-[0.08em] text-stone">
                                <Meta
                                    icon={CalendarDays}
                                    value={formatSessionDateLong(
                                        session.starts_at,
                                        locale,
                                    )}
                                />
                                <Meta
                                    icon={Clock}
                                    value={formatSessionTime(
                                        session.starts_at,
                                        locale,
                                    )}
                                />
                                <Meta
                                    icon={Timer}
                                    value={formatDuration(
                                        session.duration_minutes,
                                    )}
                                />
                                <Meta
                                    icon={Gauge}
                                    value={t(session.level_label)}
                                />
                                <Meta
                                    icon={Users}
                                    value={t(session.gender_label)}
                                />
                            </div>

                            <div className="flex flex-wrap gap-3 border-b border-gold/15 px-7 py-5">
                                <Pill label={t(session.court_status_label)} />
                                {session.club?.is_partner && <PartnerBadge />}
                                {session.is_cancelled && (
                                    <Pill label={t('Geannuleerd')} />
                                )}
                                {session.lifecycle_label &&
                                    (session.lifecycle === 'held' ||
                                        session.lifecycle === 'finished') && (
                                        <Pill
                                            label={t(session.lifecycle_label)}
                                            accent
                                        />
                                    )}
                                {session.is_locked &&
                                    session.lifecycle === 'held' && (
                                        <Pill
                                            label={t('Scorebord vergrendeld')}
                                        />
                                    )}
                                {session.is_pending && (
                                    <Pill
                                        label={t('Aanvraag in behandeling')}
                                        accent
                                    />
                                )}
                                {isRejected && (
                                    <Pill label={t('Aanvraag geweigerd')} />
                                )}
                            </div>

                            <div className="px-7 py-7">
                                <h2 className="mb-1 font-sans text-[11px] font-medium tracking-[0.22em] text-choc uppercase">
                                    {t('Spelers')}
                                </h2>
                                <p className="mb-5 font-sans text-[11px] text-stone">
                                    {session.participants_count} /{' '}
                                    {session.capacity}
                                    {pendingCount > 0 &&
                                        ` · ${t('{{count}} in afwachting', { count: pendingCount })}`}
                                </p>

                                <PlayerSlots
                                    players={session.players}
                                    openSlots={session.open_slots}
                                    pendingSlots={pendingCount}
                                    size="lg"
                                    withLabels
                                    onRemove={
                                        session.can_manage
                                            ? handleRemove
                                            : undefined
                                    }
                                />

                                {session.notes && (
                                    <p className="mt-7 border-l-2 border-gold/30 pl-4 font-serif text-base text-choc3 italic">
                                        {session.notes}
                                    </p>
                                )}
                            </div>

                            <JoinRequests
                                sessionId={Number(session.id)}
                                requests={joinRequests}
                            />

                            {scoreboard && (
                                <SessionScoreboard
                                    sessionId={Number(session.id)}
                                    scoreboard={scoreboard}
                                />
                            )}
                        </div>

                        {matchCard && (
                            <aside className="border-t border-gold/15 px-7 py-7 lg:col-start-2 lg:row-span-2 lg:row-start-1 lg:border-t-0 lg:border-l">
                                <div className="lg:sticky lg:top-28">
                                    <MatchCardShare
                                        card={matchCard}
                                        title={
                                            session.club?.name ?? t('Sessie')
                                        }
                                    />
                                </div>
                            </aside>
                        )}

                        <div className="min-w-0 lg:col-start-1 lg:row-start-2">
                            <div className="flex flex-wrap gap-3 border-t border-gold/15 px-7 py-6">
                                {session.can_join && (
                                    <button
                                        type="button"
                                        onClick={handleJoin}
                                        className={solidButton}
                                    >
                                        {t('Vraag om mee te spelen')}
                                    </button>
                                )}

                                {session.can_withdraw && (
                                    <button
                                        type="button"
                                        onClick={handleLeave}
                                        className={outlineButton}
                                    >
                                        {t('Aanvraag intrekken')}
                                    </button>
                                )}

                                {seatsAllRequested && (
                                    <span className="border border-gold/20 bg-gold/8 px-6 py-3.5 font-sans text-[10px] tracking-[0.2em] text-stone uppercase">
                                        {t('Alle plaatsen aangevraagd')}
                                    </span>
                                )}

                                <SessionChatButton
                                    session={session}
                                    withLabel
                                />

                                {session.can_leave && (
                                    <button
                                        type="button"
                                        onClick={handleLeave}
                                        className={outlineButton}
                                    >
                                        {t('Sessie verlaten')}
                                    </button>
                                )}

                                {session.registration_closed &&
                                    !session.is_joined &&
                                    !session.is_host &&
                                    !session.is_pending &&
                                    !session.is_full && (
                                        <span className="border border-gold/20 bg-gold/8 px-6 py-3.5 font-sans text-[10px] tracking-[0.2em] text-stone uppercase">
                                            {t('Inschrijving gesloten')}
                                        </span>
                                    )}

                                {session.can_manage && (
                                    <CancelSessionDialog
                                        sessionId={Number(session.id)}
                                        triggerClassName={outlineButton}
                                        defaultOpen={wantsCancel}
                                    />
                                )}

                                {session.is_full &&
                                    !session.is_joined &&
                                    !session.is_past && (
                                        <span className="border border-gold/20 bg-gold/8 px-6 py-3.5 font-sans text-[10px] tracking-[0.2em] text-stone uppercase">
                                            {t('Sessie compleet')}
                                        </span>
                                    )}

                                {session.is_past && (
                                    <span className="border border-gold/20 bg-gold/8 px-6 py-3.5 font-sans text-[10px] tracking-[0.2em] text-stone uppercase">
                                        {t('Sessie afgerond')}
                                    </span>
                                )}

                                {!matchCard && shareUrl && (
                                    <CopyShareLinkButton url={shareUrl} />
                                )}

                                <Link
                                    href={sessionRoutes.index.url(locale)}
                                    className={outlineButton}
                                >
                                    {t('Terug naar sessies')}
                                </Link>
                            </div>

                            {session.is_full && !session.is_past && (
                                <p className="border-t border-gold/15 bg-gold/6 px-7 py-5 text-center font-sans text-[11px] tracking-[0.08em] text-choc3">
                                    {t('Uw groep is compleet. Tot op de baan!')}
                                </p>
                            )}
                        </div>
                    </div>
                </Wrap>
            </div>
        </>
    );
}

function Meta({
    icon: Icon,
    value,
}: {
    icon: typeof CalendarDays;
    value: string;
}) {
    return (
        <span className="inline-flex items-center gap-2">
            <Icon className="size-3.5" aria-hidden="true" />
            {value}
        </span>
    );
}

function Pill({ label, accent = false }: { label: string; accent?: boolean }) {
    return (
        <span
            className={
                accent
                    ? 'border border-gold/25 bg-gold/8 px-3 py-1.5 font-sans text-[9px] tracking-[0.16em] text-gold2 uppercase'
                    : 'border border-gold/20 px-3 py-1.5 font-sans text-[9px] tracking-[0.16em] text-stone uppercase'
            }
        >
            {label}
        </span>
    );
}

const solidButton =
    'cursor-pointer border-none bg-choc px-7 py-3.5 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase transition-colors hover:bg-gold2';

const outlineButton =
    'cursor-pointer border border-gold/30 bg-transparent px-7 py-3.5 font-sans text-[10px] font-medium tracking-[0.2em] text-choc uppercase transition-colors hover:border-gold hover:bg-gold/10';
