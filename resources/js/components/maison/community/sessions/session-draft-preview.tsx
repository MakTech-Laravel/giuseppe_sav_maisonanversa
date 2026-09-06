import { CalendarDays, Clock, Gauge, Timer } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { PlayerSlots } from '@/components/maison/community/sessions/player-slots';
import { PartnerBadge } from '@/components/maison/community/partner-badge';
import {
    formatDuration,
    formatSessionDate,
    formatSessionTime,
} from '@/lib/session-format';
import type {
    ClubCard,
    SessionFormOptions,
    SessionPlayer,
} from '@/types/session';

type SessionDraftPreviewProps = {
    sport: string;
    club: ClubCard | null;
    startsAt: string;
    durationMinutes: number;
    level: string;
    courtStatus: string;
    gender: string;
    capacity: number;
    host: SessionPlayer;
    options: SessionFormOptions;
    locale: string;
};

export function SessionDraftPreview({
    sport,
    club,
    startsAt,
    durationMinutes,
    level,
    courtStatus,
    gender,
    capacity,
    host,
    options,
    locale,
}: SessionDraftPreviewProps) {
    const { t } = useTranslation();
    const openSlots = Math.max(0, capacity - 1);

    return (
        <aside className="sticky top-[calc(var(--topbar-h)+var(--nav-h)+4.5rem)] hidden border border-gold/15 bg-cream2 p-6 lg:block">
            <p className="font-sans text-[10px] tracking-[0.22em] text-stone uppercase">
                {t('Voorbeeld')}
            </p>

            <div className="mt-4 mb-2 flex flex-wrap items-center gap-2 font-sans text-[10px] tracking-[0.2em] text-stone uppercase">
                <span className="text-choc">
                    {t(labelFor(options.sports, sport))}
                </span>
                {club && (
                    <>
                        <span aria-hidden="true">·</span>
                        <span>{club.city}</span>
                    </>
                )}
            </div>

            <h3 className="font-serif text-[22px] font-medium text-choc">
                {club?.name ?? t('Kies een club of corner.')}
            </h3>

            {club?.is_partner && <PartnerBadge className="mt-2" />}

            <div className="mt-5 flex flex-col gap-2 font-sans text-[11px] tracking-[0.08em] text-stone">
                <span className="inline-flex items-center gap-1.5">
                    <CalendarDays className="size-3.5" aria-hidden="true" />
                    {formatSessionDate(startsAt, locale)}
                </span>
                <span className="inline-flex items-center gap-1.5">
                    <Clock className="size-3.5" aria-hidden="true" />
                    {formatSessionTime(startsAt, locale)}
                </span>
                <span className="inline-flex items-center gap-1.5">
                    <Timer className="size-3.5" aria-hidden="true" />
                    {formatDuration(durationMinutes)}
                </span>
                <span className="inline-flex items-center gap-1.5">
                    <Gauge className="size-3.5" aria-hidden="true" />
                    {t(labelFor(options.levels, level))}
                </span>
            </div>

            <p className="mt-4 font-sans text-[11px] text-stone">
                {t(labelFor(options.court_statuses, courtStatus))}
                {' · '}
                {t(labelFor(options.genders, gender))}
            </p>

            <div className="mt-6 border-t border-gold/15 pt-5">
                <PlayerSlots
                    players={[host]}
                    openSlots={openSlots}
                    withLabels
                />
            </div>
        </aside>
    );
}

function labelFor(
    options: { value: string | number; label: string }[],
    value: string | number,
): string {
    return (
        options.find((option) => String(option.value) === String(value))
            ?.label ?? String(value)
    );
}
