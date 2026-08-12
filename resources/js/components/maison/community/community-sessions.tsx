import { useState, type ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import {
    SESSION_CARDS,
    SESSION_LEVELS,
    SESSION_LOCATIONS,
    SESSION_PLAYERS_WANTED,
    type SessionCardData,
} from '@/components/maison/community/community-data';
import { Monogram } from '@/components/maison/ui/monogram';
import { Wrap } from '@/components/maison/ui/section';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { cn } from '@/lib/utils';

type CommunitySessionsProps = {
    onJoin: () => void;
};

/**
 * Open sessions list in normal page flow. “Plan a session” lives in a fixed
 * sheet trigger so the form stays reachable no matter how long the list is.
 */
export function CommunitySessions({ onJoin }: CommunitySessionsProps) {
    const { t } = useTranslation();
    const [sessions, setSessions] = useState(SESSION_CARDS);
    const [created, setCreated] = useState(false);
    const [plannerOpen, setPlannerOpen] = useState(false);

    function handleJoin(sessionId: string) {
        setSessions((current) =>
            current.map((session) => {
                if (session.id !== sessionId || session.joined) {
                    return session;
                }

                const spotsMatch = session.spots.match(/^(\d+)/);
                const openSpots = spotsMatch
                    ? Number.parseInt(spotsMatch[1], 10)
                    : 0;
                const nextSpots = openSpots - 1;

                return {
                    ...session,
                    joined: true,
                    spots:
                        nextSpots <= 0
                            ? 'Vol'
                            : nextSpots === 1
                              ? '1 plek vrij'
                              : `${nextSpots} plekken vrij`,
                    players: [...session.players, 'YS'],
                    emptySlots: Math.max(0, session.emptySlots - 1),
                };
            }),
        );

        onJoin();
    }

    function handleCreateSession() {
        setCreated(true);
    }

    return (
        <>
            <Wrap className="mx-auto max-w-3xl px-6 py-12 md:px-10 lg:px-20">
                <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
                    <h2 className="font-serif text-[28px] font-medium text-choc">
                        {t('Open Sessies')}
                    </h2>
                    <p className="max-w-md text-sm text-choc3 md:hidden">
                        {t(
                            'Gebruik Plan een sessie linksonder om een nieuwe afspraak te maken.',
                        )}
                    </p>
                </div>

                {sessions.map((session) => (
                    <SessionCard
                        key={session.id}
                        session={session}
                        onJoin={() => handleJoin(session.id)}
                    />
                ))}
            </Wrap>

            <Sheet open={plannerOpen} onOpenChange={setPlannerOpen}>
                <SheetTrigger asChild>
                    <button
                        type="button"
                        className="fixed bottom-6 left-5 z-40 border border-gold/35 bg-choc px-5 py-3.5 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase shadow-[0_12px_40px_rgba(41,28,24,0.35)] transition-colors hover:border-gold hover:bg-gold2 md:bottom-8 md:left-8"
                    >
                        {t('Plan een sessie')}
                    </button>
                </SheetTrigger>

                <SheetContent
                    side="right"
                    className="w-full gap-0 overflow-y-auto border-gold/20 bg-cream p-0 sm:max-w-md"
                >
                    <SheetHeader className="border-b border-gold/15 bg-cream2 px-6 py-5 text-left">
                        <SheetTitle className="font-serif text-2xl font-medium text-choc">
                            {t('Plan een nieuwe sessie')}
                        </SheetTitle>
                        <SheetDescription className="font-sans text-[11px] tracking-[0.12em] text-stone uppercase">
                            {t('Zichtbaar voor Founding Circle leden')}
                        </SheetDescription>
                    </SheetHeader>

                    <div className="p-6">
                        {!created ? (
                            <SessionPlannerForm
                                onCreate={() => {
                                    handleCreateSession();
                                }}
                            />
                        ) : (
                            <div className="text-center">
                                <div className="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-gold/15 text-xl text-gold2">
                                    ✓
                                </div>
                                <h3 className="mb-2 font-serif text-xl text-choc">
                                    {t('Sessie aangemaakt.')}
                                </h3>
                                <p className="mb-6 text-sm text-choc3">
                                    {t('Andere leden kunnen nu aansluiten.')}
                                </p>
                                <button
                                    type="button"
                                    onClick={() => {
                                        setCreated(false);
                                        setPlannerOpen(false);
                                    }}
                                    className="w-full cursor-pointer bg-choc px-6 py-3.5 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase transition-colors hover:bg-gold2"
                                >
                                    {t('Sluiten')}
                                </button>
                            </div>
                        )}
                    </div>
                </SheetContent>
            </Sheet>
        </>
    );
}

type SessionCardProps = {
    session: SessionCardData & { joined?: boolean };
    onJoin: () => void;
};

function SessionCard({ session, onJoin }: SessionCardProps) {
    const { t } = useTranslation();
    const full = session.spots === 'Vol';

    return (
        <article className="mb-4 border border-gold/15 bg-cream2 p-7">
            <div className="mb-3 flex items-start justify-between gap-3">
                <div className="font-serif text-[22px] font-medium text-choc">
                    {t(session.title)}
                </div>
                <div
                    className={cn(
                        'shrink-0 border border-gold/20 bg-gold/8 px-3 py-1 font-sans text-[10px] tracking-[0.15em] uppercase',
                        full ? 'text-stone' : 'text-gold2',
                    )}
                >
                    {t(session.spots)}
                </div>
            </div>

            <div className="mb-4 flex flex-wrap gap-6">
                {session.meta.map((item) => (
                    <div
                        key={item}
                        className="font-sans text-[10px] tracking-[0.1em] text-stone"
                    >
                        {t(item)}
                    </div>
                ))}
            </div>

            <div className="mb-4 flex items-center gap-2">
                {session.players.map((player) => (
                    <Monogram key={player} initials={player} size="md" />
                ))}
                {Array.from({ length: session.emptySlots }).map((_, index) => (
                    <Monogram
                        key={`empty-${index}`}
                        initials="+"
                        size="md"
                        className="border-dashed bg-gold/4 text-gold/30"
                    />
                ))}
            </div>

            <button
                type="button"
                disabled={session.joined || full}
                onClick={onJoin}
                className={cn(
                    'cursor-pointer border-none px-7 py-3.5 font-sans text-[10px] font-medium tracking-[0.2em] uppercase transition-colors disabled:cursor-default',
                    session.joined
                        ? 'bg-gold2 text-choc'
                        : 'bg-choc text-cream hover:bg-gold2',
                )}
            >
                {session.joined
                    ? t('✓ Aangemeld')
                    : t('Sluit aan bij deze sessie')}
            </button>
        </article>
    );
}

type SessionPlannerFormProps = {
    onCreate: () => void;
};

function SessionPlannerForm({ onCreate }: SessionPlannerFormProps) {
    const { t } = useTranslation();

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                onCreate();
            }}
            className="space-y-4"
        >
            <Field label={t('Club Corner locatie')}>
                <select className={fieldClassName}>
                    {SESSION_LOCATIONS.map((location) => (
                        <option key={location}>{t(location)}</option>
                    ))}
                </select>
            </Field>

            <Field label={t('Datum')}>
                <input
                    type="date"
                    defaultValue="2027-02-08"
                    className={fieldClassName}
                />
            </Field>

            <Field label={t('Tijdstip')}>
                <input
                    type="time"
                    defaultValue="10:00"
                    className={fieldClassName}
                />
            </Field>

            <Field label={t('Niveau')}>
                <select className={fieldClassName}>
                    {SESSION_LEVELS.map((level) => (
                        <option key={level}>{t(level)}</option>
                    ))}
                </select>
            </Field>

            <Field label={t('Extra spelers gezocht')}>
                <select className={fieldClassName}>
                    {SESSION_PLAYERS_WANTED.map((option) => (
                        <option key={option}>{t(option)}</option>
                    ))}
                </select>
            </Field>

            <Field label={t('Notitie (optioneel)')}>
                <input
                    type="text"
                    placeholder={t('Bijv. niveau, taal, bijzonderheden...')}
                    className={fieldClassName}
                />
            </Field>

            <button
                type="submit"
                className="w-full cursor-pointer bg-choc px-6 py-4 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase transition-colors hover:bg-gold2"
            >
                {t('Sessie Aanmaken')}
            </button>
        </form>
    );
}

const fieldClassName =
    'w-full border border-gold/20 bg-cream px-4 py-3 font-serif text-base text-choc outline-none transition-colors focus:border-gold2';

function Field({ label, children }: { label: string; children: ReactNode }) {
    return (
        <label className="flex flex-col gap-1.5">
            <span className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase">
                {label}
            </span>
            {children}
        </label>
    );
}
