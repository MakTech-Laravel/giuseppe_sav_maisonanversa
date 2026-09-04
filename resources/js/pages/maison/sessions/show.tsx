import { Link, router, usePage } from '@inertiajs/react';
import { CalendarDays, Clock, Gauge, Timer, Users } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { CommunityTabs } from '@/components/maison/community/community-tabs';
import { PartnerBadge } from '@/components/maison/community/partner-badge';
import { PlayerSlots } from '@/components/maison/community/sessions/player-slots';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { PageHero } from '@/components/maison/ui/page-hero';
import { Wrap } from '@/components/maison/ui/section';
import {
    formatDuration,
    formatSessionDateLong,
    formatSessionTime,
} from '@/lib/session-format';
import * as sessionRoutes from '@/routes/community/sessions';
import * as participantRoutes from '@/routes/community/sessions/participants';
import type { SessionCard, SessionPlayer } from '@/types/session';

type SessionShowProps = {
    session: SessionCard;
};

export default function SessionShow({ session }: SessionShowProps) {
    const { t } = useTranslation();
    const { locale } = usePage().props;
    const args = { locale, communitySession: Number(session.id) };

    function handleJoin() {
        router.post(sessionRoutes.join(args).url, {}, { preserveScroll: true });
    }

    function handleLeave() {
        router.delete(sessionRoutes.leave(args).url, { preserveScroll: true });
    }

    function handleCancel() {
        if (!window.confirm(t('Deze sessie annuleren?'))) {
            return;
        }

        router.delete(sessionRoutes.destroy(args).url);
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
                    <div className="border border-gold/15 bg-cream2">
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
                                value={formatDuration(session.duration_minutes)}
                            />
                            <Meta icon={Gauge} value={t(session.level_label)} />
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
                        </div>

                        <div className="px-7 py-7">
                            <h2 className="mb-1 font-sans text-[11px] font-medium tracking-[0.22em] text-choc uppercase">
                                {t('Spelers')}
                            </h2>
                            <p className="mb-5 font-sans text-[11px] text-stone">
                                {session.participants_count} /{' '}
                                {session.capacity}
                            </p>

                            <PlayerSlots
                                players={session.players}
                                openSlots={session.open_slots}
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

                        <div className="flex flex-wrap gap-3 border-t border-gold/15 px-7 py-6">
                            {session.can_join && (
                                <button
                                    type="button"
                                    onClick={handleJoin}
                                    className={solidButton}
                                >
                                    {t('Sluit aan bij deze sessie')}
                                </button>
                            )}

                            {session.can_leave && (
                                <button
                                    type="button"
                                    onClick={handleLeave}
                                    className={outlineButton}
                                >
                                    {t('Sessie verlaten')}
                                </button>
                            )}

                            {session.can_manage && (
                                <button
                                    type="button"
                                    onClick={handleCancel}
                                    className={outlineButton}
                                >
                                    {t('Sessie annuleren')}
                                </button>
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
