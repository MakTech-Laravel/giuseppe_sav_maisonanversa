import { Link, router, usePage } from '@inertiajs/react';
import { CalendarDays, Clock, Gauge, Timer } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { PlayerSlots } from '@/components/maison/community/sessions/player-slots';
import * as sessionRoutes from '@/routes/community/sessions';
import {
    formatDuration,
    formatSessionDate,
    formatSessionTime,
} from '@/lib/session-format';
import { cn } from '@/lib/utils';
import type { SessionCard as SessionCardData } from '@/types/session';

type SessionCardProps = {
    session: SessionCardData;
};

export function SessionCard({ session }: SessionCardProps) {
    const { t } = useTranslation();
    const { locale } = usePage().props;
    const args = { locale, communitySession: Number(session.id) };

    function handleJoin() {
        router.post(sessionRoutes.join(args).url, {}, { preserveScroll: true });
    }

    function handleLeave() {
        router.delete(sessionRoutes.leave(args).url, { preserveScroll: true });
    }

    const showUrl = sessionRoutes.show(args).url;

    return (
        <article className="flex flex-col gap-5 border border-gold/15 bg-cream2 p-6 transition-colors hover:border-gold/35 md:flex-row md:items-center md:justify-between md:p-7">
            <div className="min-w-0 flex-1">
                <div className="mb-2 flex flex-wrap items-center gap-2 font-sans text-[10px] tracking-[0.2em] text-stone uppercase">
                    <span className="text-choc">{t(session.sport_label)}</span>
                    {session.club && (
                        <>
                            <span aria-hidden="true">·</span>
                            <span>{session.club.city}</span>
                        </>
                    )}
                </div>

                <Link
                    href={showUrl}
                    className="flex flex-wrap items-center gap-2 font-serif text-[20px] font-medium text-choc hover:text-gold2 md:text-[22px]"
                >
                    {session.club?.name ?? t('Onbekende club')}
                </Link>

                {session.club?.is_partner && (
                    <span className="mt-2 inline-flex items-center gap-1.5 border border-gold/25 bg-gold/8 px-2.5 py-1 font-sans text-[9px] tracking-[0.16em] text-gold2 uppercase">
                        {t('Partner Club')}
                    </span>
                )}

                <div className="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 font-sans text-[11px] tracking-[0.08em] text-stone">
                    <span className="inline-flex items-center gap-1.5">
                        <CalendarDays className="size-3.5" aria-hidden="true" />
                        {formatSessionDate(session.starts_at, locale)}
                    </span>
                    <span className="inline-flex items-center gap-1.5">
                        <Clock className="size-3.5" aria-hidden="true" />
                        {formatSessionTime(session.starts_at, locale)}
                    </span>
                    <span className="inline-flex items-center gap-1.5">
                        <Timer className="size-3.5" aria-hidden="true" />
                        {formatDuration(session.duration_minutes)}
                    </span>
                    <span className="inline-flex items-center gap-1.5">
                        <Gauge className="size-3.5" aria-hidden="true" />
                        {t(session.level_label)}
                    </span>
                </div>
            </div>

            <div className="flex flex-col items-start gap-4 md:items-end">
                <PlayerSlots
                    players={session.players}
                    openSlots={session.open_slots}
                />

                <span className="font-sans text-[10px] tracking-[0.16em] text-stone uppercase">
                    {session.participants_count} / {session.capacity}{' '}
                    {t('spelers')}
                </span>

                <SessionAction
                    session={session}
                    onJoin={handleJoin}
                    onLeave={handleLeave}
                    showUrl={showUrl}
                />
            </div>
        </article>
    );
}

type SessionActionProps = {
    session: SessionCardData;
    showUrl: string;
    onJoin: () => void;
    onLeave: () => void;
};

function SessionAction({
    session,
    showUrl,
    onJoin,
    onLeave,
}: SessionActionProps) {
    const { t } = useTranslation();

    if (session.is_cancelled) {
        return <StatusPill label={t('Sessie geannuleerd')} />;
    }

    if (session.is_past) {
        return <StatusPill label={t('Sessie afgerond')} />;
    }

    if (session.is_host) {
        return (
            <Link href={showUrl} className={cn(outlineButton)}>
                {t('Sessie beheren')}
            </Link>
        );
    }

    if (session.can_leave) {
        return (
            <button type="button" onClick={onLeave} className={cn(outlineButton)}>
                {t('Sessie verlaten')}
            </button>
        );
    }

    if (session.is_full) {
        return <StatusPill label={t('Sessie compleet')} />;
    }

    return (
        <button type="button" onClick={onJoin} className={cn(solidButton)}>
            {t('Sluit aan')}
        </button>
    );
}

function StatusPill({ label }: { label: string }) {
    return (
        <span className="border border-gold/20 bg-gold/8 px-5 py-3 font-sans text-[10px] tracking-[0.2em] text-stone uppercase">
            {label}
        </span>
    );
}

const solidButton =
    'cursor-pointer border-none bg-choc px-7 py-3.5 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase transition-colors hover:bg-gold2';

const outlineButton =
    'cursor-pointer border border-gold/30 bg-transparent px-7 py-3.5 font-sans text-[10px] font-medium tracking-[0.2em] text-choc uppercase transition-colors hover:border-gold hover:bg-gold/10';

export { solidButton, outlineButton };
