import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { HomeSessionCard } from '@/components/maison/home/home-session-card';
import { Reveal } from '@/components/maison/ui/reveal';
import { useLocale } from '@/hooks/use-locale';
import { useResumePendingSessionJoin } from '@/hooks/use-resume-pending-session-join';
import { cn } from '@/lib/utils';
import * as sessionRoutes from '@/routes/community/sessions';
import type { SessionCard as SessionCardData } from '@/types/session';

const INTRO_DESKTOP =
    'Vind een open sessie bij een van onze partnerclubs, of plan er zelf een en nodig je vrienden uit. De baan boek je bij de club; wij brengen de spelers samen.';

const INTRO_MOBILE =
    'Vind een open sessie bij een van onze partnerclubs, of plan er zelf een en nodig je vrienden uit.';

const EMPTY_BODY =
    'Kies een club en een tijdstip, deel de link met je vrienden, en andere leden kunnen aansluiten.';

const primaryClass =
    'inline-flex min-h-11 items-center justify-center border border-choc bg-choc px-6 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase transition-colors hover:border-gold hover:bg-gold hover:text-choc w-full md:w-auto';

const secondaryClass =
    'inline-flex min-h-11 items-center justify-center border border-choc bg-transparent px-6 font-sans text-[10px] font-medium tracking-[0.2em] text-choc uppercase transition-colors hover:bg-choc hover:text-cream w-full md:w-auto';

/**
 * Homepage sessions block (Phase 1 briefing): Warm Ivory split layout,
 * joinable cards, plan/browse CTAs, and empty state.
 */
export function HomeSessions({
    sessions,
    openSessionsThisWeek,
}: {
    sessions: SessionCardData[];
    openSessionsThisWeek: number;
}) {
    const { t } = useTranslation();
    const { locale } = useLocale();
    const { auth } = usePage().props;

    useResumePendingSessionJoin(sessions);

    const createUrl = sessionRoutes.create.url(locale);
    const indexUrl = sessionRoutes.index.url(locale);
    const hasSessions = sessions.length > 0;
    const isAuthed = Boolean(auth?.user);

    function visitAuthed(url: string) {
        window.location.assign(url);
    }

    const planButton: ReactNode = isAuthed ? (
        <Link href={createUrl} className={primaryClass}>
            {t('+ Plan een sessie')}
        </Link>
    ) : (
        <button
            type="button"
            onClick={() => visitAuthed(createUrl)}
            className={cn(primaryClass, 'cursor-pointer')}
        >
            {t('+ Plan een sessie')}
        </button>
    );

    const browseButton: ReactNode = isAuthed ? (
        <Link href={indexUrl} className={secondaryClass}>
            {t('Bekijk alle sessies')}
        </Link>
    ) : (
        <button
            type="button"
            onClick={() => visitAuthed(indexUrl)}
            className={cn(secondaryClass, 'cursor-pointer')}
        >
            {t('Bekijk alle sessies')}
        </button>
    );

    const weekCounter = (
        <p className="font-sans text-[10px] tracking-[0.22em] text-[#6E5646] uppercase">
            {t('{{count}} open sessies deze week', {
                count: openSessionsThisWeek,
            })}
        </p>
    );

    return (
        <section className="bg-cream px-8 py-32 text-choc md:px-[120px] md:py-36">
            <div
                className={cn(
                    'mx-auto flex max-w-320 flex-col gap-12',
                    hasSessions && 'md:flex-row md:items-start md:gap-[90px]',
                )}
            >
                <Reveal
                    className={cn(
                        'w-full shrink-0',
                        hasSessions
                            ? 'md:max-w-[440px]'
                            : 'mx-auto max-w-[520px] text-center',
                    )}
                >
                    <p className="mb-4 font-sans text-[9px] font-medium tracking-[0.35em] text-[#7A5C2E] uppercase">
                        {t('Community · Sessies')}
                    </p>
                    <div
                        aria-hidden="true"
                        className={cn(
                            'mb-6 h-px w-12 bg-[#B2915C]',
                            !hasSessions && 'mx-auto',
                        )}
                    />

                    {hasSessions ? (
                        <>
                            <h2 className="mb-5 font-serif text-[clamp(32px,4vw,48px)] leading-[1.12] font-normal text-choc">
                                {t('Na de koffie, de baan.')}
                            </h2>
                            <p className="mb-8 text-[15px] leading-[1.85] text-[#4A3A30] md:hidden">
                                {t(INTRO_MOBILE)}
                            </p>
                            <p className="mb-8 hidden text-[15px] leading-[1.85] text-[#4A3A30] md:block">
                                {t(INTRO_DESKTOP)}
                            </p>

                            <div className="mb-5 hidden flex-col gap-3 md:flex md:flex-row md:flex-wrap">
                                {planButton}
                                {browseButton}
                            </div>

                            <div className="hidden md:block">{weekCounter}</div>
                        </>
                    ) : (
                        <>
                            <p className="mb-3 font-sans text-[10px] tracking-[0.22em] text-[#6E5646] uppercase">
                                {t('Geen open sessies op dit moment')}
                            </p>
                            <h2 className="mb-5 font-serif text-[clamp(28px,4vw,42px)] leading-[1.15] font-normal text-choc">
                                {t('Plan de eerste sessie van de week.')}
                            </h2>
                            <p className="mb-8 text-[15px] leading-[1.85] text-[#4A3A30]">
                                {t(EMPTY_BODY)}
                            </p>
                            <div className="flex justify-center">{planButton}</div>
                        </>
                    )}
                </Reveal>

                {hasSessions ? (
                    <div className="flex min-w-0 flex-1 flex-col gap-3.5">
                        {sessions.map((session) => (
                            <Reveal key={session.id}>
                                <HomeSessionCard session={session} />
                            </Reveal>
                        ))}

                        <div className="mt-4 flex flex-col gap-3 md:hidden">
                            {planButton}
                            {browseButton}
                            <div className="text-center">{weekCounter}</div>
                        </div>
                    </div>
                ) : null}
            </div>
        </section>
    );
}
