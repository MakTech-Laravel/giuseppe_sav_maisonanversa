import { router, usePage } from '@inertiajs/react';
import type { MouseEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { useOptionalShellActions } from '@/components/maison/shell/shell-actions';
import { Monogram } from '@/components/maison/ui/monogram';
import { storePendingSessionJoin } from '@/lib/pending-session-join';
import {
    formatSessionDateParts,
    formatSessionTime,
} from '@/lib/session-format';
import * as sessionRoutes from '@/routes/community/sessions';
import type { SessionCard as SessionCardData } from '@/types/session';

/**
 * Homepage session row from the Phase 1 briefing: date block, club/time,
 * initials + open slots, and Doe mee / Je speelt mee.
 */
export function HomeSessionCard({ session }: { session: SessionCardData }) {
    const { t } = useTranslation();
    const { auth, locale } = usePage().props;
    const shellActions = useOptionalShellActions();
    const args = { locale, communitySession: Number(session.id) };
    const showUrl = sessionRoutes.show(args).url;
    const date = formatSessionDateParts(session.starts_at, locale);
    const playing = session.is_joined || session.is_host;

    function openDetail() {
        router.visit(showUrl);
    }

    function handleJoin(event: MouseEvent<HTMLButtonElement>) {
        event.stopPropagation();

        if (!auth?.user) {
            storePendingSessionJoin(session.id);
            shellActions?.openAuth('login');

            return;
        }

        router.post(sessionRoutes.join(args).url, {}, { preserveScroll: true });
    }

    function freePlacesLabel(): string {
        if (session.is_full || session.open_slots <= 0) {
            return t('Volzet');
        }

        if (session.open_slots === 1) {
            return t('1 plaats vrij');
        }

        return t('{{count}} plaatsen vrij', { count: session.open_slots });
    }

    return (
        <article
            role="link"
            tabIndex={0}
            onClick={openDetail}
            onKeyDown={(event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openDetail();
                }
            }}
            className="flex cursor-pointer flex-col gap-4 border border-[#DDD0C2] bg-[#FBF8F4] px-5 py-5 transition-colors hover:border-gold/50 md:flex-row md:items-center md:gap-6 md:px-6 md:py-5"
        >
            <div className="flex min-w-0 flex-1 items-start gap-4 md:items-center md:gap-5">
                <div className="flex w-14 shrink-0 flex-col items-center text-center font-serif text-choc">
                    <span className="text-[11px] tracking-[0.08em] text-[#6E5646] uppercase">
                        {date.weekday}
                    </span>
                    <span className="text-[32px] leading-none font-normal">
                        {date.day}
                    </span>
                    <span className="mt-0.5 text-[11px] tracking-[0.08em] text-[#6E5646] uppercase">
                        {date.month}
                    </span>
                </div>

                <div className="min-w-0 flex-1">
                    <h3 className="truncate font-serif text-[20px] leading-tight font-medium text-choc md:text-[22px]">
                        {session.club?.name ?? t('Onbekende club')}
                    </h3>
                    <p className="mt-1 font-sans text-[12px] tracking-[0.12em] text-[#4A3A30]">
                        {formatSessionTime(session.starts_at, locale)}
                    </p>
                </div>
            </div>

            <div className="flex flex-col gap-3 md:items-end">
                <div className="flex flex-wrap items-center gap-2">
                    {session.players.map((player) => (
                        <Monogram
                            key={player.id}
                            size="md"
                            emphasis={player.is_host}
                            initials={player.initials}
                            title={player.name || undefined}
                        />
                    ))}
                    {Array.from({
                        length: Math.max(0, session.open_slots),
                    }).map((_, index) => (
                        <Monogram
                            key={`open-${index}`}
                            size="md"
                            initials="+"
                            className="border-dashed border-[#8D705A] bg-transparent text-[#8D705A]"
                        />
                    ))}
                </div>

                <p className="font-sans text-[10px] tracking-[0.16em] text-[#6E5646] uppercase">
                    {freePlacesLabel()}
                </p>

                {playing ? (
                    <span className="inline-flex min-h-11 items-center justify-center border border-choc bg-choc px-6 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase">
                        {t('Je speelt mee ✓')}
                    </span>
                ) : session.is_full ? (
                    <span className="inline-flex min-h-11 items-center justify-center border border-[#DDD0C2] px-6 font-sans text-[10px] tracking-[0.2em] text-[#6E5646] uppercase">
                        {t('Volzet')}
                    </span>
                ) : (
                    <button
                        type="button"
                        onClick={handleJoin}
                        className="inline-flex min-h-11 cursor-pointer items-center justify-center border border-choc bg-choc px-6 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase transition-colors hover:border-gold hover:bg-gold hover:text-choc"
                    >
                        {t('Doe mee')}
                    </button>
                )}
            </div>
        </article>
    );
}
