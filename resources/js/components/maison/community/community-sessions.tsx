import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import {
    SESSION_LEVELS,
    SESSION_LOCATIONS,
    SESSION_PLAYERS_WANTED,
} from '@/components/maison/community/community-data';
import type {
    CommunitySessionPayload,
    SessionCardData,
} from '@/components/maison/community/community-data';
import { Monogram } from '@/components/maison/ui/monogram';
import { Wrap } from '@/components/maison/ui/section';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
    sessions: CommunitySessionPayload[];
    onJoin: () => void;
};

function toSessionCard(
    session: CommunitySessionPayload,
    t: (key: string) => string,
): SessionCardData {
    const spots =
        session.spots === null
            ? t('Open')
            : session.spots <= 0
              ? 'Vol'
              : session.spots === 1
                ? '1 plek vrij'
                : `${session.spots} plekken vrij`;

    return {
        id: session.id,
        title: session.location,
        spots,
        meta: [session.starts_at, session.location, session.level ?? ''].filter(
            Boolean,
        ),
        players: session.players,
        emptySlots: session.spots ?? 0,
        joined: session.joined,
    };
}

export function CommunitySessions({ sessions, onJoin }: CommunitySessionsProps) {
    const { t } = useTranslation();
    const { locale } = usePage().props;
    const [created, setCreated] = useState(false);
    const [plannerOpen, setPlannerOpen] = useState(false);

    function handleJoin(sessionId: string) {
        router.post(
            `/${locale}/community/sessions/${sessionId}/join`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => onJoin(),
            },
        );
    }

    function handleLeave(sessionId: string) {
        router.delete(`/${locale}/community/sessions/${sessionId}/leave`, {
            preserveScroll: true,
        });
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

                {sessions.length === 0 && (
                    <p className="mb-6 text-sm text-choc3">
                        {t('Nog geen open sessies. Plan de eerste.')}
                    </p>
                )}

                {sessions.map((session) => (
                    <SessionCard
                        key={session.id}
                        session={toSessionCard(session, t)}
                        onJoin={() => handleJoin(session.id)}
                        onLeave={() => handleLeave(session.id)}
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
                                    setCreated(true);
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
    onLeave: () => void;
};

function SessionCard({ session, onJoin, onLeave }: SessionCardProps) {
    const { t } = useTranslation();
    const full = session.spots === 'Vol';

    return (
        <article className="mb-4 border border-gold/15 bg-cream2 p-7">
            <div className="mb-3 flex items-start justify-between gap-3">
                <div className="font-serif text-[22px] font-medium text-choc">
                    {session.title}
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
                        {item}
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

            {session.joined ? (
                <button
                    type="button"
                    onClick={onLeave}
                    className="cursor-pointer border border-gold/30 bg-transparent px-7 py-3.5 font-sans text-[10px] font-medium tracking-[0.2em] text-choc uppercase transition-colors hover:border-gold hover:bg-gold/10"
                >
                    {t('Verlaat sessie')}
                </button>
            ) : (
                <button
                    type="button"
                    disabled={full}
                    onClick={onJoin}
                    className="cursor-pointer border-none bg-choc px-7 py-3.5 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase transition-colors hover:bg-gold2 disabled:cursor-default disabled:opacity-50"
                >
                    {t('Sluit aan bij deze sessie')}
                </button>
            )}
        </article>
    );
}

type SessionPlannerFormProps = {
    onCreate: () => void;
};

function SessionPlannerForm({ onCreate }: SessionPlannerFormProps) {
    const { t } = useTranslation();
    const { locale } = usePage().props;
    const [date, setDate] = useState('2027-02-08');
    const [time, setTime] = useState('10:00');
    const [location, setLocation] = useState(SESSION_LOCATIONS[0] ?? 'Antwerp');
    const [level, setLevel] = useState(SESSION_LEVELS[0] ?? '');
    const [capacity, setCapacity] = useState('4');
    const [notes, setNotes] = useState('');

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        router.post(
            `/${locale}/community/sessions`,
            {
                starts_at: `${date}T${time}`,
                location,
                level,
                capacity: Number(capacity) || null,
                notes: notes || null,
            },
            {
                preserveScroll: true,
                onSuccess: () => onCreate(),
            },
        );
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-4">
            <Field label={t('Club Corner locatie')}>
                <Select value={location} onValueChange={setLocation}>
                    <SelectTrigger
                        className={cn(
                            fieldClassName,
                            'h-auto w-full justify-between rounded-none shadow-none',
                        )}
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {SESSION_LOCATIONS.map((item) => (
                            <SelectItem key={item} value={item}>
                                {t(item)}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </Field>

            <Field label={t('Datum')}>
                <input
                    type="date"
                    value={date}
                    onChange={(event) => setDate(event.target.value)}
                    className={fieldClassName}
                />
            </Field>

            <Field label={t('Tijdstip')}>
                <input
                    type="time"
                    value={time}
                    onChange={(event) => setTime(event.target.value)}
                    className={fieldClassName}
                />
            </Field>

            <Field label={t('Niveau')}>
                <Select value={level} onValueChange={setLevel}>
                    <SelectTrigger
                        className={cn(
                            fieldClassName,
                            'h-auto w-full justify-between rounded-none shadow-none',
                        )}
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {SESSION_LEVELS.map((item) => (
                            <SelectItem key={item} value={item}>
                                {t(item)}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </Field>

            <Field label={t('Extra spelers gezocht')}>
                <Select value={capacity} onValueChange={setCapacity}>
                    <SelectTrigger
                        className={cn(
                            fieldClassName,
                            'h-auto w-full justify-between rounded-none shadow-none',
                        )}
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {SESSION_PLAYERS_WANTED.map((option) => {
                            const value = option.replace(/\D/g, '') || '4';

                            return (
                                <SelectItem key={option} value={value}>
                                    {t(option)}
                                </SelectItem>
                            );
                        })}
                    </SelectContent>
                </Select>
            </Field>

            <Field label={t('Notitie (optioneel)')}>
                <input
                    type="text"
                    value={notes}
                    onChange={(event) => setNotes(event.target.value)}
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
